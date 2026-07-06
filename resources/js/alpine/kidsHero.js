/**
 * Kids-lineup hero: subtle mouse parallax. Sets --px/--py (-1..1) on the section
 * from the pointer position; CSS translates each child by its depth. Desktop /
 * fine-pointer only and skipped for prefers-reduced-motion (entrance + float are
 * pure CSS and always run). A dummy showcase until real child photography lands.
 */
export default function kidsHero() {
  return {
    init() {
      const fine = window.matchMedia('(pointer: fine)').matches;
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (!fine || reduce) return;

      const el = this.$el;
      const onMove = (e) => {
        const r = el.getBoundingClientRect();
        el.style.setProperty('--px', (((e.clientX - r.left) / r.width - 0.5) * 2).toFixed(3));
        el.style.setProperty('--py', (((e.clientY - r.top) / r.height - 0.5) * 2).toFixed(3));
      };
      const reset = () => { el.style.setProperty('--px', '0'); el.style.setProperty('--py', '0'); };
      el.addEventListener('pointermove', onMove);
      el.addEventListener('pointerleave', reset);
    },
  };
}
