import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

/** Initialise every [data-swiper] carousel on the page. */
export function initCarousels() {
  document.querySelectorAll('[data-swiper]').forEach((el) => {
    new Swiper(el, {
      modules: [Navigation, Pagination, Autoplay, EffectFade],
      slidesPerView: 1.15,
      spaceBetween: 20,
      grabCursor: true,
      pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
      navigation: {
        nextEl: el.querySelector('.swiper-button-next'),
        prevEl: el.querySelector('.swiper-button-prev'),
      },
      breakpoints: {
        640: { slidesPerView: 2.2 },
        1024: { slidesPerView: 3.2 },
        1280: { slidesPerView: 4 },
      },
    });
  });
}
