const API_BASE = FEEDWALL_CONFIG.api_url;

const App = {
    token: localStorage.getItem("fw_token") || null,

    scale: 1,
    offset: { x: 0, y: 0 },

    posts: [],
    elements: new Map(),
    viewport: { width: window.innerWidth, height: window.innerHeight },

    velocity: { x: 0, y: 0 },

    init() {
        this.root = document.getElementById("feedwall-root");
        if (!this.root) return;

        if (this.token) this.loadApp();
        else this.showAuth?.();

        window.addEventListener("resize", () => {
            this.viewport.width = window.innerWidth;
            this.viewport.height = window.innerHeight;
        });
    },

    async loadApp() {
        this.root.innerHTML = `
            <div id="fw-topbar">
                <button id="fw-logout">Logout</button>
                <button id="fw-new-post">+ Post</button>
            </div>

            <div id="fw-canvas-wrapper">
                <div id="fw-canvas"></div>
            </div>
        `;

        this.canvas = document.getElementById("fw-canvas");
        document.getElementById("fw-logout").onclick = () => this.logout?.();

        this.initPanZoom();
        await this.loadWall();

        this.animateLoop(); // continuous animation loop
    },

    async loadWall() {
        const res = await fetch(API_BASE + "get-wall");
        this.posts = await res.json();
        this.renderVisible();
    },

    // ---------------- RADIAL ----------------

    radialPosition(i) {
        const angle = i * 2.399963;
        const radius = 40 + i * 18;

        return {
            x: radius * Math.cos(angle),
            y: radius * Math.sin(angle)
        };
    },

    isVisible(x, y) {
        const margin = 200;

        return (
            x > -this.viewport.width/2 - margin &&
            x < this.viewport.width/2 + margin &&
            y > -this.viewport.height/2 - margin &&
            y < this.viewport.height/2 + margin
        );
    },

    renderVisible() {
        this.posts.forEach((p, i) => {
            const pos = this.radialPosition(i);

            const screenX = pos.x * this.scale + this.offset.x;
            const screenY = pos.y * this.scale + this.offset.y;

            if (!this.isVisible(screenX, screenY)) {
                if (this.elements.has(p.post_id)) {
                    this.elements.get(p.post_id).remove();
                    this.elements.delete(p.post_id);
                }
                return;
            }

            if (!this.elements.has(p.post_id)) {
                const el = document.createElement("div");
                el.className = "fw-post";
                el.onclick = () => this.openComments(p.post_id);

                this.canvas.appendChild(el);
                this.elements.set(p.post_id, el);
            }

            const el = this.elements.get(p.post_id);

            const ageHrs = (Date.now() - new Date(p.created_at)) / 3600000;

            // Glow strength (strong when new)
            const glow = Math.max(0, 1 - ageHrs / 6);

            // Pulse animation
            const pulse = 1 + Math.sin(Date.now() / 400 + i) * 0.05 * glow;

            // LOD
            const imgSrc = this.scale > 1.5
                ? `/wp-content/uploads/feedwall_media/${p.image_path}_wall.jpg`
                : `/wp-content/uploads/feedwall_media/${p.image_path}_thumb.jpg`;

            el.innerHTML = `
                ${p.image_path ? `<img src="${imgSrc}">` : ""}
                <p>${p.content_text}</p>
            `;

            el.style.transform = `
                translate(${pos.x}px, ${pos.y}px)
                scale(${pulse})
            `;

            // Glow + highlight
            el.style.boxShadow = glow > 0
                ? `0 0 ${20 * glow}px rgba(255,100,150,${0.6 * glow})`
                : "none";

            el.style.border = glow > 0.7
                ? "2px solid rgba(255,120,180,0.8)"
                : "none";

            el.style.opacity = Math.max(0.2, 1 - ageHrs / 24);
        });
    },

    // ---------------- ANIMATION LOOP ----------------

    animateLoop() {
        const loop = () => {
            this.renderVisible();
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);
    },

    // ---------------- PAN + ZOOM ----------------

    initPanZoom() {
        let dragging = false;
        let start = { x: 0, y: 0 };

        this.canvas.addEventListener("mousedown", e => {
            dragging = true;
            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener("mousemove", e => {
            if (!dragging) return;

            this.velocity.x = e.clientX - start.x;
            this.velocity.y = e.clientY - start.y;

            this.offset.x += this.velocity.x;
            this.offset.y += this.velocity.y;

            this.updateTransform();
            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener("mouseup", () => {
            dragging = false;
            this.applyMomentum();
        });

        // touch
        let lastTouch = null;

        this.canvas.addEventListener("touchstart", e => {
            lastTouch = e.touches[0];
        });

        this.canvas.addEventListener("touchmove", e => {
            const touch = e.touches[0];

            this.offset.x += touch.clientX - lastTouch.clientX;
            this.offset.y += touch.clientY - lastTouch.clientY;

            this.updateTransform();
            lastTouch = touch;
        });

        // zoom
        this.canvas.addEventListener("wheel", e => {
            e.preventDefault();
            const target = this.scale + e.deltaY * -0.001;
            this.animateZoom(target);
        });
    },

    applyMomentum() {
        const decay = 0.95;

        const step = () => {
            this.velocity.x *= decay;
            this.velocity.y *= decay;

            this.offset.x += this.velocity.x;
            this.offset.y += this.velocity.y;

            this.updateTransform();

            if (Math.abs(this.velocity.x) > 0.5 || Math.abs(this.velocity.y) > 0.5) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    },

    animateZoom(target) {
        target = Math.min(Math.max(0.4, target), 3);

        const start = this.scale;
        const duration = 200;
        const startTime = performance.now();

        const animate = (time) => {
            const t = (time - startTime) / duration;
            if (t >= 1) {
                this.scale = target;
                this.updateTransform();
                return;
            }

            const eased = 1 - Math.pow(1 - t, 3);

            this.scale = start + (target - start) * eased;
            this.updateTransform();

            requestAnimationFrame(animate);
        };

        requestAnimationFrame(animate);
    },

    updateTransform() {
        this.canvas.style.transform = `
            translate(${this.offset.x}px, ${this.offset.y}px)
            scale(${this.scale})
        `;
    },

    // ---------------- COMMENTS ----------------

    async openComments(postId) {
        const res = await fetch(API_BASE + "get-comments?post_id=" + postId);
        const comments = await res.json();

        const modal = document.createElement("div");
        modal.className = "fw-modal";

        modal.innerHTML = `
            <div class="fw-modal-box">
                ${comments.map(c => `<p>${c.content_text}</p>`).join("")}
                <input id="fw-comment-input">
                <button id="fw-send">Send</button>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById("fw-send").onclick = async () => {
            const text = document.getElementById("fw-comment-input").value;

            await fetch(API_BASE + "add-comment", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": "Bearer " + this.token
                },
                body: JSON.stringify({ post_id: postId, content_text: text })
            });

            modal.remove();
            this.openComments(postId);
        };
    }
};

document.addEventListener("DOMContentLoaded", () => App.init());
