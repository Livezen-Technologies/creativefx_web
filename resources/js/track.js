/**
 * Reports the handful of interactions the server cannot see for itself.
 *
 * A page view is recorded server-side, where it needs no script and cannot be
 * blocked. These three cannot be: a WhatsApp button is an outbound link, and
 * opening the booking dialog or playing the film never leaves the page.
 *
 * sendBeacon, so the request survives the navigation that follows it and never
 * delays it. If the browser has no sendBeacon the event is simply not reported
 * — a missing row in a chart is not worth holding up a click.
 *
 * Nothing is sent when the visitor has asked not to be measured. That check is
 * repeated on the server, which is where it counts; doing it here as well means
 * a request that will be discarded is never made in the first place.
 */
const optedOut = () =>
  navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl === true;

function report(event) {
  if (optedOut() || !navigator.sendBeacon) return;
  const body = new FormData();
  body.append('event', event);
  body.append('path', window.location.pathname);
  try {
    navigator.sendBeacon('/api/track', body);
  } catch (e) {
    /* a failed measurement is not an error the visitor should ever meet */
  }
}

export function initTracking() {
  // Outbound WhatsApp links, wherever they are: the floating button, the
  // footer icon row, the booking dialog's own WhatsApp option.
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href*="wa.me"], a[href*="api.whatsapp.com"]');
    if (link) report('whatsapp_click');
  }, { capture: true });

  window.addEventListener('booking-open', () => report('booking_open'));
  window.addEventListener('film-open', () => report('film_play'));
}
