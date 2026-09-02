import * as THREE from 'three';

/**
 * Mounts a full-bleed ray-marched nebula shader into `container`.
 * Vanilla-JS port (no React) of a nebula background effect, with a single
 * fixed palette tuned for the site's brand and center-dimming left on so
 * text overlaid in the middle of the container stays legible.
 *
 * Returns a cleanup function; does nothing (and returns a no-op) when the
 * viewer prefers reduced motion, leaving the container's CSS background visible.
 */
export function mountNebulaShader(container) {
    if (!container || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return () => {};
    }

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);
    renderer.domElement.style.position = 'absolute';
    renderer.domElement.style.inset = '0';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';

    const scene = new THREE.Scene();
    const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
    const clock = new THREE.Clock();

    const vertexShader = `
        varying vec2 vUv;
        void main() {
            vUv = uv;
            gl_Position = vec4(position, 1.0);
        }
    `;

    const fragmentShader = `
        precision mediump float;
        uniform vec2 iResolution;
        uniform float iTime;
        varying vec2 vUv;

        #define t iTime
        mat2 m(float a){ float c=cos(a), s=sin(a); return mat2(c,-s,s,c); }
        float map(vec3 p){
            p.xz *= m(t*0.4);
            p.xy *= m(t*0.3);
            vec3 q = p*2. + t;
            return length(p + vec3(sin(t*0.7))) * log(length(p)+1.0)
                 + sin(q.x + sin(q.z + sin(q.y))) * 0.5 - 1.0;
        }

        void mainImage(out vec4 O, in vec2 fragCoord) {
            vec2 uv = (fragCoord - 0.5 * iResolution) / min(iResolution.x, iResolution.y);
            vec3 col = vec3(0.0);
            float d = 2.5;

            for (int i = 0; i <= 5; i++) {
                vec3 p = vec3(0,0,5.) + normalize(vec3(uv, -1.)) * d;
                float rz = map(p);
                float f  = clamp((rz - map(p + 0.1)) * 0.5, -0.1, 1.0);

                vec3 base = vec3(0.02,0.08,0.1) + vec3(0.9,2.6,3.2) * f;

                col = col * base + smoothstep(2.5, 0.0, rz) * 0.7 * base;
                d += min(rz, 1.0);
            }

            float dist   = distance(fragCoord, iResolution*0.5);
            float radius = min(iResolution.x, iResolution.y) * 0.5;
            float dim    = smoothstep(radius*0.3, radius*0.5, dist);

            O = vec4(col, 1.0);
            O.rgb = mix(O.rgb * 0.3, O.rgb, dim);
        }

        void main() {
            mainImage(gl_FragColor, vUv * iResolution);
        }
    `;

    const uniforms = {
        iTime: { value: 0 },
        iResolution: { value: new THREE.Vector2() },
    };

    const material = new THREE.ShaderMaterial({ vertexShader, fragmentShader, uniforms });
    const mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
    scene.add(mesh);

    const onResize = () => {
        const w = container.clientWidth;
        const h = container.clientHeight;
        renderer.setSize(w, h);
        uniforms.iResolution.value.set(w, h);
    };
    window.addEventListener('resize', onResize);
    onResize();

    renderer.setAnimationLoop(() => {
        uniforms.iTime.value = clock.getElapsedTime();
        renderer.render(scene, camera);
    });

    return () => {
        window.removeEventListener('resize', onResize);
        renderer.setAnimationLoop(null);
        container.removeChild(renderer.domElement);
        material.dispose();
        mesh.geometry.dispose();
        renderer.dispose();
    };
}

window.mountNebulaShader = mountNebulaShader;
