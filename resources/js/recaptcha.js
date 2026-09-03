/**
 * Mints reCAPTCHA v3 tokens for the forms that ask for one.
 *
 * v3 has no visible challenge: it scores the visit in the background and the
 * server decides. The only client-side job is to produce a fresh token at the
 * moment of submit — they expire after two minutes, and a booking form is often
 * open longer than that while somebody picks dates.
 *
 * Exposed as a function rather than wired to form submits, because both forms
 * here post with fetch from an Alpine component: intercepting the submit event
 * would produce a hidden field that the request body — built from the
 * component's own state — never reads.
 *
 * Nothing is loaded unless the page says reCAPTCHA is configured, so a site
 * that does not use it never contacts Google and never receives its cookie.
 *
 * Failure never blocks a submission: no script, no token, and the server treats
 * a missing token as "not evidence of a bot". A guest with an ad blocker should
 * still be able to ask for a room.
 */
let loading = null;

function siteKey() {
  const meta = document.querySelector('meta[name="recaptcha-site-key"]');
  return (meta && meta.content) || '';
}

function loadScript(key) {
  if (loading) return loading;
  loading = new Promise((resolve) => {
    const s = document.createElement('script');
    s.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(key)}`;
    s.async = true;
    s.onload = () => resolve(true);
    s.onerror = () => resolve(false);
    document.head.appendChild(s);
  });
  return loading;
}

export function initRecaptcha() {
  // Always defined, so callers need no feature test of their own. Returns an
  // empty string whenever a token cannot be produced, which is exactly what the
  // server expects when reCAPTCHA is not in play.
  window.recaptchaToken = async (action) => {
    const key = siteKey();
    if (!key) return '';
    try {
      const ok = await loadScript(key);
      if (!ok || !window.grecaptcha) return '';
      await new Promise((r) => window.grecaptcha.ready(r));
      return await window.grecaptcha.execute(key, { action });
    } catch (e) {
      return '';
    }
  };
}
