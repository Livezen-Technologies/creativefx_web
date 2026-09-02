import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

/**
 * Initialise every [data-swiper] carousel on the page.
 *
 * The defaults suit a strip of thumbnails — a peek of the next card, widening
 * to four across on a large screen. A carousel of paragraphs (the guest reviews
 * on the home page) wants none of that: it shows one at a time at every width.
 * `data-swiper-per-view="1"` asks for that, and the breakpoints that would
 * otherwise put four reviews side by side are dropped with it.
 */
export function initCarousels() {
  document.querySelectorAll('[data-swiper]').forEach((el) => {
    const perView = parseFloat(el.dataset.swiperPerView) || 1.15;
    const single = perView === 1;

    new Swiper(el, {
      modules: [Navigation, Pagination, Autoplay, EffectFade],
      slidesPerView: perView,
      spaceBetween: single ? 0 : 20,
      grabCursor: true,
      autoHeight: single,
      loop: single && el.querySelectorAll('.swiper-slide').length > 1,
      pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
      // The review panel styles its own arrows, so they carry a -c suffix and
      // never pick up Swiper's default chevron sprite. Fall back to the plain
      // classes for every other carousel on the site.
      navigation: {
        nextEl: el.querySelector('.swiper-button-next-c, .swiper-button-next'),
        prevEl: el.querySelector('.swiper-button-prev-c, .swiper-button-prev'),
      },
      breakpoints: single ? undefined : {
        640: { slidesPerView: 2.2 },
        1024: { slidesPerView: 3.2 },
        1280: { slidesPerView: 4 },
      },
    });
  });
}
