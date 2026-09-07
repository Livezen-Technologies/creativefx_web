import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const run = promisify(execFile);

/**
 * Can two people buy the same last seat?
 *
 * This is the check the whole commerce side stands on. Everywhere else on this
 * site a read followed by a write is fine; here it is the bug, and it is a bug
 * that never appears in development because development is one request at a
 * time. Two buyers get the last chair, both are charged, and on the morning of
 * the class there are thirteen names for twelve seats.
 *
 * So: eight genuinely separate processes, all reaching for the last seat at the
 * same moment. Exactly one must win.
 *
 * They are separate PROCESSES rather than promises or forks on purpose. Node
 * promises are one thread and would serialise; a PHP fork shares the parent's
 * database handle, which is the one thing the lock is meant to contend on. Only
 * independent processes with independent connections test what production does.
 *
 *     node scripts/test-seat-inventory.mjs
 *     node scripts/test-seat-inventory.mjs --prove   (negative-test: no lock)
 *
 * The --prove run is not decoration. A concurrency test that has never failed
 * is a test nobody knows works, and this one is easy to write in a way that
 * passes against the bug: `--prove` bypasses InventoryService and does the
 * naive read-then-write, and the run is expected to oversell.
 */

const CONTENDERS = 8;
const PROVE = process.argv.includes('--prove');

let pass = 0;
let fail = 0;
const check = (label, ok, detail = '') => {
  if (ok) { pass++; console.log(`  ok    ${label}`); }
  else { fail++; console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
};

const spark = (args) =>
  run('php', ['spark', ...args], { cwd: process.cwd(), timeout: 60_000 })
    .then((r) => r.stdout.trim().split('\n').pop().trim())
    .catch((e) => 'ERROR:' + String(e.message).slice(0, 120));

// ── Set up a session with exactly one seat ──────────────────────────────────
// Its own row rather than an existing one, so a failed run cannot leave the
// seeded catalogue holding phantom seats.
const setup = await spark(['seats:fixture', 'create', PROVE ? 'naive' : 'locked']);
const sessionId = Number(setup);
if (!Number.isInteger(sessionId) || sessionId <= 0) {
  console.log(`Could not create the fixture session: ${setup}`);
  process.exit(1);
}

try {
  // ── Everybody reaches at once ─────────────────────────────────────────────
  const results = await Promise.all(
    Array.from({ length: CONTENDERS }, (_, i) =>
      spark(['seats:manage', PROVE ? 'hold-naive' : 'hold', String(sessionId), String(900000 + i)])
    )
  );

  const won = results.filter((r) => r === 'ok').length;
  const lost = results.filter((r) => r === 'gone' || r === 'short').length;

  console.log(`\n  ${CONTENDERS} processes, 1 seat → ${results.join(', ')}\n`);

  check('exactly one process gets the seat', won === 1, `${won} succeeded`);
  check('everybody else is told it has gone', lost === CONTENDERS - 1, `${lost} refused`);
  check('nobody got an error instead of an answer',
    results.every((r) => !r.startsWith('ERROR')), results.filter((r) => r.startsWith('ERROR')).join(' | '));

  // The counts must also agree afterwards. A lock that admits one writer but
  // leaves the cached number wrong has moved the bug rather than fixed it.
  const state = await spark(['seats:fixture', 'state', String(sessionId)]);
  check('the seat count matches the holds that exist', state === 'consistent', state);
} finally {
  await spark(['seats:fixture', 'destroy', String(sessionId)]);
}

console.log(`\n${pass} passed, ${fail} failed.`);

if (PROVE) {
  // Inverted on purpose: the naive path is expected to oversell, and a --prove
  // run that comes back clean means the harness is not creating real contention
  // and the ordinary run proves nothing either.
  console.log(fail > 0
    ? '\nExpected: the unlocked path oversold, so the check bites.'
    : '\nWARNING: the unlocked path did NOT oversell. The test is not producing real contention.');
  process.exit(fail > 0 ? 0 : 1);
}

process.exit(fail === 0 ? 0 : 1);
