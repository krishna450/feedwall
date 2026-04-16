const API_BASE = FEEDWALL_CONFIG.api_url;

const App = {
    token: localStorage.getItem("fw_token") || null,

    scale: 1,
    offset: { x: 0, y: 0 },

    posts: [],
    lastFetch: null,
    pendingNew: [],

    init() {
        this.root = document.getElementById("feedwall-root");
        if (!this.root) return;

        if (this.token) this.loadApp();
        else this.showAuth();
    },

    // ---------------- AUTH ----------------

    showAuth() {
        this.root.innerHTML = `
            <div class="fw-auth">
                <h2>Feedwall</h2>
                <input id="fw-username" placeholder="Username" />
                <input id="fw-passcode" placeholder="6-digit PIN" maxlength="6" />
                <div class="fw-actions">
                    <button id="fw-login">Login</button>
                    <button id="fw-register">Register</button>
                </div>
                <p id="fw-msg"></p>
            </div>
        `;

        document.getElementById("fw-login").onclick = () => this.login();
        document.getElementById("fw-register").onclick = () => this.register();
    },

    async login() {
        const username = fwVal("fw-username");
        const passcode = fwVal("fw-passcode");

        const res = await fetch(API_BASE + "login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, passcode })
        });

        const data = await res.json();

        if (data.token) {
            localStorage.setItem("fw_token", data.token);
            this.token = data.token;
            this.loadApp();
        } else fwMsg(data.error);
    },

    async register() {
        const username = fwVal("fw-username");
        const passcode = fwVal("fw-passcode");

        const res = await fetch(API_BASE + "register", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, passcode })
        });

        const data = await res.json();
        fwMsg(data.success ? "Registered!" : data.error);
    },

    logout() {
        localStorage.removeItem("fw_token");
        this.token = null;
        this.showAuth();
    },

    // ---------------- APP ----------------

    async loadApp() {
        this.root.innerHTML = `
            <div id="fw-topbar">
                <button id="fw-logout">Logout</button>
                <button id="fw-new-post">+ Post</button>
                <button id="fw-new-indicator" class="hidden">↑ New Posts</button>
            </div>

            <div id="fw-canvas-wrapper">
                <div id="fw-canvas"></div>
            </div>

            <div id="fw-post-box" class="hidden">
                <textarea id="fw-post-text"></textarea>
                <input type="file" id="fw-post-image" />
                <button id="fw-submit-post">Submit</button>
            </div>
        `;

        document.getElementById("fw-logout").onclick = () => this.logout();
        document.getElementById("fw-new-post").onclick = () => this.togglePostBox();
        document.getElementById("fw-submit-post").onclick = () => this.submitPost();
        document.getElementById("fw-new-indicator").onclick = () => this.injectNewPosts();

        this.initPanZoom();
        await this.loadWall();
        this.startPolling();
    },

    async loadWall() {
        const res = await fetch(API_BASE + "get-wall");
        const posts = await res.json();

        this.posts = posts;
        this.lastFetch = new Date().toISOString();

        this.renderPosts();
    },

    // ---------------- REAL-TIME ----------------

    startPolling() {
        setInterval(async () => {
            const res = await fetch(API_BASE + "get-wall");
            const latest = await res.json();

            const newPosts = latest.filter(p =>
                new Date(p.created_at) > new Date(this.lastFetch)
            );

            if (newPosts.length) {
                this.pendingNew = newPosts;
                document.getElementById("fw-new-indicator").classList.remove("hidden");
            }
        }, 30000);
    },

    injectNewPosts() {
        this.posts = [...this.pendingNew, ...this.posts];
        this.pendingNew = [];

        document.getElementById("fw-new-indicator").classList.add("hidden");

        this.renderPosts(true); // animate center injection
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

    renderPosts(animate = false) {
        const canvas = document.getElementById("fw-canvas");
        canvas.innerHTML = "";

        this.posts.forEach((p, i) => {
            const pos = this.radialPosition(i);

            const ageHrs = (Date.now() - new Date(p.created_at)) / 3600000;
            const opacity = Math.max(0.2, 1 - ageHrs / 24);

            const el = document.createElement("div");
            el.className = "fw-post";

            // LOD swap
            const imgSrc = this.scale > 1.5
                ? `/wp-content/uploads/feedwall_media/${p.image_path}_wall.jpg`
                : `/wp-content/uploads/feedwall_media/${p.image_path}_thumb.jpg`;

            el.innerHTML = `
                ${p.image_path ? `<img src="${imgSrc}">` : ""}
                <p>${p.content_text}</p>
            `;

            if (animate && i < this.pendingNew.length) {
                el.style.transform = `translate(0px, 0px)`;
                setTimeout(() => {
                    el.style.transform = `translate(${pos.x}px, ${pos.y}px)`;
                }, 50);
            } else {
                el.style.transform = `translate(${pos.x}px, ${pos.y}px)`;
            }

            el.style.opacity = opacity;

            el.onclick = () => this.openComments(p.post_id);

            canvas.appendChild(el);
        });
    },

    // ---------------- PAN + ZOOM ----------------

    initPanZoom() {
        const canvas = document.getElementById("fw-canvas");

        let dragging = false;
        let start = { x: 0, y: 0 };

        canvas.addEventListener("mousedown", e => {
            dragging = true;
            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener("mousemove", e => {
            if (!dragging) return;

            this.offset.x += e.clientX - start.x;
            this.offset.y += e.clientY - start.y;

            this.updateTransform();
            start = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener("mouseup", () => dragging = false);

        canvas.addEventListener("wheel", e => {
            e.preventDefault();

            this.scale += e.deltaY * -0.001;
            this.scale = Math.min(Math.max(0.4, this.scale), 3);

            this.updateTransform();
            this.renderPosts(); // LOD refresh
        });
    },

    updateTransform() {
        const canvas = document.getElementById("fw-canvas");
        canvas.style.transform = `
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
            const text = fwVal("fw-comment-input");

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

function fwVal(id) { return document.getElementById(id).value; }
function fwMsg(msg) { document.getElementById("fw-msg").innerText = msg; }

document.addEventListener("DOMContentLoaded", () => App.init());
