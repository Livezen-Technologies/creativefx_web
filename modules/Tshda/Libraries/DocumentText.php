<?php

namespace Modules\Tshda\Libraries;

/**
 * Pull the readable text out of an uploaded document, once, at upload.
 *
 * Clause 3.12 asks for full-text search *inside* PDFs — "a word or phrase
 * inside a circular or annual report is found, not merely the file name". That
 * is only affordable if the text is extracted when the file arrives and stored
 * beside it; reading every PDF on the server on every search is not a search,
 * it is a denial of service the Authority runs against itself.
 *
 * Three routes, in order of how good the answer is:
 *
 *   1. `pdftotext` (poppler-utils), if the server has it. It understands
 *      encodings, ligatures and column order, and it is what this is for.
 *   2. A pure-PHP reader for the PDF's own text operators. It handles the
 *      ordinary case — a PDF produced by a word processor, with FlateDecode
 *      content streams — and gets enough out of it to search. It will not
 *      handle every encoding, and it says so rather than pretending.
 *   3. Plain-text formats, read directly.
 *
 * A scanned PDF has no text layer at all, and no amount of parsing invents
 * one: those need OCR, which is a server-side tool (`ocrmypdf` or `tesseract`)
 * rather than something to attempt in PHP. When one is detected — a PDF whose
 * extraction comes back empty — the caller is told, so the console can say so
 * instead of quietly indexing nothing.
 */
final class DocumentText
{
    /** Anything longer than this is more index than the search needs. */
    private const MAX_LENGTH = 400000;

    /**
     * @return array{text: string, method: string}
     *         method ∈ pdftotext | php | plain | none | scanned
     */
    public static function extract(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return ['text' => '', 'method' => 'none'];
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['txt', 'csv', 'md'], true)) {
            return ['text' => self::tidy((string) file_get_contents($path)), 'method' => 'plain'];
        }

        if ($extension !== 'pdf') {
            return ['text' => '', 'method' => 'none'];
        }

        $text = self::viaPdftotext($path);
        if ($text !== '') {
            return ['text' => $text, 'method' => 'pdftotext'];
        }

        $text = self::viaPhp($path);
        if ($text !== '') {
            return ['text' => $text, 'method' => 'php'];
        }

        // A PDF with no extractable text is, almost always, a scan.
        return ['text' => '', 'method' => 'scanned'];
    }

    private static function viaPdftotext(string $path): string
    {
        if (! function_exists('exec')) {
            return '';
        }

        // Resolve the binary rather than trusting PATH, and refuse to run
        // anything that is not an ordinary executable file.
        $binary = '';
        foreach (['/usr/bin/pdftotext', '/usr/local/bin/pdftotext'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                $binary = $candidate;
                break;
            }
        }
        if ($binary === '') {
            return '';
        }

        $out    = [];
        $status = 0;
        // -enc UTF-8 so Sinhala and Tamil survive; -q so a warning on stderr
        // does not end up in the index.
        @exec(escapeshellcmd($binary) . ' -q -enc UTF-8 ' . escapeshellarg($path) . ' -', $out, $status);

        return $status === 0 ? self::tidy(implode("\n", $out)) : '';
    }

    /**
     * Read the PDF's content streams and pull the strings out of its text
     * operators.
     *
     * A PDF page's text is a sequence of `(string) Tj` and `[(a) -250 (b)] TJ`
     * operators inside a content stream, which is usually Flate-compressed.
     * Decompress every stream, then take what is inside the parentheses. This
     * is not a PDF renderer and does not try to be one — word order across
     * columns can come out wrong, and a font with a custom encoding will come
     * out as nonsense — but for the circulars and reports an Authority
     * publishes it finds the phrase somebody is searching for.
     */
    private static function viaPhp(string $path): string
    {
        $raw = (string) @file_get_contents($path, false, null, 0, 32 * 1024 * 1024);
        if ($raw === '') {
            return '';
        }

        $pieces = [];

        // Every stream ... endstream span in the file.
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams) === 0) {
            return '';
        }

        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded === false) {
                // Not compressed, or compressed with a filter we do not read.
                $decoded = $stream;
            }
            if (! is_string($decoded) || $decoded === '') {
                continue;
            }

            // (…) Tj  and  [(…) … (…)] TJ
            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $decoded, $strings) === 0) {
                continue;
            }

            foreach ($strings[0] as $s) {
                $s = substr($s, 1, -1);
                // PDF string escapes: \n \r \t \( \) \\ and \ddd octal.
                $s = preg_replace_callback(
                    '/\\\\(n|r|t|b|f|\(|\)|\\\\|[0-7]{1,3})/',
                    static function (array $m): string {
                        return match ($m[1]) {
                            'n' => "\n", 'r' => "\r", 't' => "\t",
                            'b' => "\x08", 'f' => "\x0C",
                            '(' => '(', ')' => ')', '\\' => '\\',
                            default => chr(octdec($m[1])),
                        };
                    },
                    $s
                );
                if (trim((string) $s) !== '') {
                    $pieces[] = $s;
                }
            }
        }

        $text = self::tidy(implode(' ', $pieces));

        // Binary that happened to sit inside parentheses produces long runs of
        // unprintable bytes. If most of what came out is not text, it is not
        // text — return nothing rather than poison the index with it.
        if ($text === '' || ! self::mostlyPrintable($text)) {
            return '';
        }

        return $text;
    }

    private static function mostlyPrintable(string $text): bool
    {
        $sample    = mb_substr($text, 0, 4000);
        $printable = preg_replace('/[^\P{C}\n\t]+/u', '', $sample) ?? '';

        return $sample !== '' && mb_strlen($printable) >= mb_strlen($sample) * 0.85;
    }

    private static function tidy(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return mb_substr(trim($text), 0, self::MAX_LENGTH);
    }
}
