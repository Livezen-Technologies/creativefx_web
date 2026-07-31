import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { KTX2Loader } from 'three/examples/jsm/loaders/KTX2Loader.js';
import { MeshoptDecoder } from 'three/examples/jsm/libs/meshopt_decoder.module.js';

// One KTX2Loader is enough for the whole page; the transcoder is a WASM module
// worth loading once. detectSupport() is still per-renderer (see below).
let ktx2 = null;

const SPEC_GLOSS = 'KHR_materials_pbrSpecularGlossiness';

/**
 * Minimal stand-in for the spec/gloss material extension three.js removed in
 * r155. Browzwear/CLO exports (our garment scans) still write it, and they put
 * the base colour *only* inside the extension — with no pbrMetallicRoughness
 * block to fall back to — so without this the garment loads as flat white with
 * just its normal map, losing the entire colourway.
 *
 * The conversion is deliberately approximate. Real spec/gloss carries per-texel
 * glossiness in the alpha of specularGlossinessTexture, which MeshStandardMaterial
 * cannot consume without patching the shader. Garment fabric is dielectric and
 * uniformly matte, so metalness 0 with a constant roughness is a close and much
 * cheaper approximation than round-tripping the textures.
 */
class GLTFSpecGlossFallback {
    constructor(parser) {
        this.parser = parser;
        this.name   = SPEC_GLOSS;
    }

    getMaterialType(materialIndex) {
        const def = this.parser.json.materials?.[materialIndex];
        return def?.extensions?.[SPEC_GLOSS] ? THREE.MeshStandardMaterial : null;
    }

    extendMaterialParams(materialIndex, params) {
        const ext = this.parser.json.materials?.[materialIndex]?.extensions?.[SPEC_GLOSS];
        if (! ext) return Promise.resolve();

        const pending = [];

        if (Array.isArray(ext.diffuseFactor)) {
            params.color = new THREE.Color().setRGB(
                ext.diffuseFactor[0], ext.diffuseFactor[1], ext.diffuseFactor[2], THREE.SRGBColorSpace,
            );
            if (ext.diffuseFactor[3] !== undefined && ext.diffuseFactor[3] < 1) {
                params.opacity     = ext.diffuseFactor[3];
                params.transparent = true;
            }
        }

        if (ext.diffuseTexture) {
            pending.push(this.parser.assignTexture(params, 'map', ext.diffuseTexture, THREE.SRGBColorSpace));
        }

        params.metalness = 0;
        // With a specularGlossinessTexture the real gloss varies per texel and we
        // cannot sample it here, so pick a matte fabric value. Only the scalar
        // factor can be converted faithfully.
        params.roughness = ext.specularGlossinessTexture
            ? 0.8
            : 1 - (ext.glossinessFactor ?? 1);

        return Promise.all(pending);
    }
}

/**
 * GLTFLoader wired for the compressed exports our product models use.
 *
 * gltfpack writes EXT_meshopt_compression (geometry) and KHR_texture_basisu
 * (KTX2/Basis textures) into `extensionsRequired`, so a bare GLTFLoader does
 * not degrade gracefully on these files — it rejects them outright. Both
 * decoders have to be attached before .load().
 */
export function createGLTFLoader(renderer) {
    if (ktx2 === null) {
        // Transcoder is copied out of three/examples into public/vendor/basis.
        ktx2 = new KTX2Loader().setTranscoderPath('/vendor/basis/');
    }
    // Picks the GPU's supported compressed-texture format (ASTC/BC7/ETC2/…),
    // so it must run against the renderer that will draw the result.
    ktx2.detectSupport(renderer);

    return new GLTFLoader()
        .setKTX2Loader(ktx2)
        .setMeshoptDecoder(MeshoptDecoder)
        .register((parser) => new GLTFSpecGlossFallback(parser));
}
