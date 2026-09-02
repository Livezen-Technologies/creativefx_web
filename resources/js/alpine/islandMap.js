/**
 * Sri Lanka locator map.
 *
 * The island is drawn once as SVG paths in a fixed viewBox; selecting an outlet
 * zooms to it by transforming a group rather than animating the viewBox, so the
 * browser can hand the whole thing to the compositor and pins stay crisp.
 *
 * Coordinates arrive as latitude/longitude and are projected with the same
 * bounds the outline was generated from, so a pin lands where it belongs
 * without the view needing to know anything about geography.
 */
export default function islandMap({ geo, points, zoom = 3 }) {
    return {
        geo,
        points,
        zoom,
        selected: null,

        /** Latitude/longitude to viewBox units — the same maths the view uses
         *  to place each pin, kept here so the zoom lands on the same spot. */
        project(lat, lon) {
            const b = this.geo.bounds;
            const scale = this.geo.width / (b.east - b.west);

            return {
                x: (lon - b.west) * scale,
                y: (b.north - lat) * scale / this.geo.lonSqueeze,
            };
        },

        init() {
            this.points = this.points.map((p) => ({ ...p, ...this.project(p.lat, p.lon) }));
        },

        select(index) {
            this.selected = this.selected === index ? null : index;
        },

        /**
         * Zoom about the selected pin. Scaling happens around the origin, so
         * the translation has to put the pin back in the middle of the frame
         * afterwards — hence the (centre - k * point) term rather than a plain
         * offset.
         */
        get transform() {
            if (this.selected === null) {
                return 'translate(0px, 0px) scale(1)';
            }

            const p = this.points[this.selected];
            if (!p) {
                return 'translate(0px, 0px) scale(1)';
            }

            const k = this.zoom;
            const tx = this.geo.width / 2 - k * p.x;
            const ty = this.geo.height / 2 - k * p.y;

            return `translate(${tx.toFixed(1)}px, ${ty.toFixed(1)}px) scale(${k})`;
        },

        /** Pins and strokes are drawn inside the zoomed group, so undo the scale. */
        get counterScale() {
            return this.selected === null ? 1 : 1 / this.zoom;
        },

        isSelected(index) {
            return this.selected === index;
        },

        reset() {
            this.selected = null;
        },
    };
}
