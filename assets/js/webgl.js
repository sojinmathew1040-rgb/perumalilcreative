/* ==========================================================================
   PERUMALIL CREATIVE - WebGL Three.js Particle Background Pipeline
   ========================================================================== */

class WebGLBackground {
    constructor() {
        this.container = document.getElementById('webgl-canvas');
        if (!this.container) return;

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.particles = null;
        this.particleCount = window.innerWidth < 768 ? 500 : 1200; // Optimized count for seamless 60fps

        // Mouse interaction vectors
        this.mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
        this.windowHalf = { x: window.innerWidth / 2, y: window.innerHeight / 2 };

        this.init();
        this.createParticles();
        this.addEventListeners();
        this.animate();
    }

    init() {
        // Read dynamic theme background color
        let parsedBg = 0x050505;
        try {
            const rootStyle = getComputedStyle(document.documentElement);
            const bgDarkHex = (rootStyle.getPropertyValue('--bg-dark') || '#050505').trim();
            parsedBg = parseInt(bgDarkHex.replace('#', ''), 16) || 0x050505;
        } catch (e) {}

        // Scene setup
        this.scene = new THREE.Scene();
        this.scene.fog = new THREE.FogExp2(parsedBg, 0.0015);

        // Camera setup
        this.camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
        this.camera.position.z = 500;

        // Renderer setup
        this.renderer = new THREE.WebGLRenderer({
            canvas: this.container,
            antialias: true,
            alpha: true,
            powerPreference: "high-performance"
        });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setClearColor(parsedBg, 1);
    }

    createParticles() {
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(this.particleCount * 3);
        const initialPositions = new Float32Array(this.particleCount * 3);
        const scales = new Float32Array(this.particleCount);

        // Distribute particles in a premium floating grid/sphere shape
        for (let i = 0; i < this.particleCount; i++) {
            const i3 = i * 3;

            // Spherical distribution
            const r = 200 + Math.random() * 300;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos((Math.random() * 2) - 1);

            positions[i3] = r * Math.sin(phi) * Math.cos(theta);
            positions[i3 + 1] = r * Math.sin(phi) * Math.sin(theta);
            positions[i3 + 2] = r * Math.cos(phi);

            // Store initial positions to calculate ripples
            initialPositions[i3] = positions[i3];
            initialPositions[i3 + 1] = positions[i3 + 1];
            initialPositions[i3 + 2] = positions[i3 + 2];

            scales[i] = 1 + Math.random() * 3;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('initialPosition', new THREE.BufferAttribute(initialPositions, 3));
        geometry.setAttribute('scale', new THREE.BufferAttribute(scales, 1));

        // High-end particles material with dynamic accent color
        let parsedAccent = 0x0066ff;
        try {
            const rootStyle = getComputedStyle(document.documentElement);
            const accentHex = (rootStyle.getPropertyValue('--accent') || '#0066ff').trim();
            parsedAccent = parseInt(accentHex.replace('#', ''), 16) || 0x0066ff;
        } catch (e) {}

        const material = new THREE.PointsMaterial({
            color: parsedAccent, // Dynamic Accent Color
            size: 2.2,
            transparent: true,
            opacity: 0.65,
            blending: THREE.AdditiveBlending,
            depthWrite: false
        });

        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);
    }

    addEventListeners() {
        window.addEventListener('mousemove', (e) => this.onMouseMove(e));
        window.addEventListener('resize', () => this.onWindowResize());
        window.addEventListener('touchmove', (e) => this.onTouchMove(e));
    }

    onMouseMove(e) {
        // Normalise coordinate between -1 and 1
        this.mouse.targetX = (e.clientX - this.windowHalf.x) * 0.4;
        this.mouse.targetY = (e.clientY - this.windowHalf.y) * 0.4;
    }

    onTouchMove(e) {
        if (e.touches.length > 0) {
            this.mouse.targetX = (e.touches[0].clientX - this.windowHalf.x) * 0.4;
            this.mouse.targetY = (e.touches[0].clientY - this.windowHalf.y) * 0.4;
        }
    }

    onWindowResize() {
        this.windowHalf.x = window.innerWidth / 2;
        this.windowHalf.y = window.innerHeight / 2;

        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(window.innerWidth, window.innerHeight);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Butter-smooth mouse inertia (LERP)
        this.mouse.x += (this.mouse.targetX - this.mouse.x) * 0.05;
        this.mouse.y += (this.mouse.targetY - this.mouse.y) * 0.05;

        // Subtle scene rotation based on cursor
        this.particles.rotation.y += 0.001;
        this.particles.rotation.x = this.mouse.y * 0.0003;
        this.particles.rotation.z = this.mouse.x * 0.0003;

        // Dynamic Wave Ripples inside particles positions array
        const positions = this.particles.geometry.attributes.position.array;
        const initial = this.particles.geometry.attributes.initialPosition.array;
        const time = Date.now() * 0.0008;

        for (let i = 0; i < this.particleCount; i++) {
            const i3 = i * 3;

            // Multi-frequency wave formula
            const px = initial[i3];
            const py = initial[i3 + 1];
            const pz = initial[i3 + 2];

            const waveX = Math.sin(time + py * 0.01) * 8;
            const waveY = Math.cos(time + px * 0.01) * 8;
            const waveZ = Math.sin(time + pz * 0.01) * 5;

            // Apply mathematical wave noise + cursor gravitational pull
            positions[i3] = px + waveX + (this.mouse.x * 0.15);
            positions[i3 + 1] = py + waveY + (-this.mouse.y * 0.15);
            positions[i3 + 2] = pz + waveZ;
        }

        this.particles.geometry.attributes.position.needsUpdate = true;
        this.renderer.render(this.scene, this.camera);
    }

    updateColors(bgColorHex, accentColorHex) {
        if (bgColorHex && this.scene && this.renderer) {
            const cleanBg = String(bgColorHex).trim().replace('#', '');
            const parsedBg = parseInt(cleanBg, 16);
            if (!isNaN(parsedBg)) {
                if (this.scene.fog) {
                    this.scene.fog.color.setHex(parsedBg);
                }
                this.renderer.setClearColor(parsedBg, 1);
            }
        }
        if (accentColorHex && this.particles && this.particles.material) {
            const cleanAccent = String(accentColorHex).trim().replace('#', '');
            const parsedAccent = parseInt(cleanAccent, 16);
            if (!isNaN(parsedAccent)) {
                this.particles.material.color.setHex(parsedAccent);
            }
        }
    }
}

// Instantiate WebGL Background when page layers settle
window.addEventListener('DOMContentLoaded', () => {
    window.webGLBackgroundInstance = new WebGLBackground();
});
