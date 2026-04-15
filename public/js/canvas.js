import { radialPosition } from './layout.js';
import { State } from './state.js';

export class Canvas {

    constructor(root) {
        this.root = root;
        this.scale = 1;
        this.offset = { x: 0, y: 0 };

        this.init();
    }

    init() {
        this.canvas = document.createElement('div');
        this.canvas.id = "fw-canvas";

        this.root.appendChild(this.canvas);

        this.enablePanZoom();
    }

    render() {
        this.canvas.innerHTML = '';

        State.posts.forEach((post, i) => {
            const pos = radialPosition(i);

            const el = document.createElement('div');
            el.className = 'fw-post';

            el.style.transform = `
                translate(${pos.x}px, ${pos.y}px)
            `;

            el.innerHTML = this.renderPost(post);

            this.canvas.appendChild(el);
        });
    }

    renderPost(post) {
        const age = (Date.now() - new Date(post.created_at)) / (1000 * 60 * 60);

        const opacity = 1 - (age / 24);

        const img = post.image_path
            ? `<img src="/wp-content/uploads/feedwall_media/${post.image_path}_thumb.jpg" />`
            : '';

        return `
            <div style="opacity:${opacity}">
                ${img}
                <p>${post.content_text}</p>
            </div>
        `;
    }

    enablePanZoom() {
        let isDragging = false;
        let start = { x: 0, y: 0 };

        this.canvas.addEventListener('mousedown', (e) => {
            isDragging = true;
            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            this.offset.x += e.clientX - start.x;
            this.offset.y += e.clientY - start.y;

            this.updateTransform();

            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
        });

        this.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();

            this.scale += e.deltaY * -0.001;
            this.scale = Math.min(Math.max(0.5, this.scale), 3);

            this.updateTransform();
        });
    }

    updateTransform() {
        this.canvas.style.transform = `
            translate(${this.offset.x}px, ${this.offset.y}px)
            scale(${this.scale})
        `;
    }
}
