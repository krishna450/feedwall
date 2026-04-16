const API_BASE = FEEDWALL_CONFIG.api_url;

const App = {
    token: localStorage.getItem("fw_token") || null,

    scale: 1,
    offset: { x: 0, y: 0 },

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
        } else {
            fwMsg(data.error || "Login failed");
        }
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
        fwMsg(data.success ? "Registered! Now login." : data.error);
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

        this.initPanZoom();
        this.loadWall();
    },

    togglePostBox() {
        document.getElementById("fw-post-box").classList.toggle("hidden");
    },

    async submitPost() {
        const text = fwVal("fw-post-text");
        const file = document.getElementById("fw-post-image").files[0];

        const fd = new FormData();
        fd.append("content_text", text);
        if (file) fd.append("image", file);

        const res = await fetch(API_BASE + "submit-post", {
            method: "POST",
            headers: { "Authorization": "Bearer " + this.token },
            body: fd
        });

        const data = await res.json();

        if (data.success) {
            this.togglePostBox();
            this.loadWall();
        }
    },

    async loadWall() {
        const res = await fetch(API_BASE + "get-wall");
        const posts = await res.json();

        this.renderPosts(posts);
    },

    // ---------------- RADIAL ENGINE ----------------

    radialPosition(i) {
        const angle = i * 2.399963; // golden angle
        const radius = 40 + i * 18;

        return {
            x: radius * Math.cos(angle),
            y: radius * Math.sin(angle)
        };
    },

    renderPosts(posts) {
        const canvas = document.getElementById("fw-canvas");
        canvas.innerHTML = "";

        posts.forEach((p, i) => {
            const pos = this.radialPosition(i);

            const ageHrs = (Date.now() - new Date(p.created_at)) / 3600000;
            const opacity = Math.max(0.2, 1 - ageHrs / 24);

            const el = document.createElement("div");
            el.className = "fw-post";

            el.style.transform = `translate(${pos.x}px, ${pos.y}px)`;
            el.style.opacity = opacity;

            el.innerHTML = `
                ${p.image_path ? `<img src="/wp-content/uploads/feedwall_media/${p.image_path}_thumb.jpg">` : ""}
                <p>${p.content_text}</p>
            `;

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

// helpers
function fwVal(id) { return document.getElementById(id).value; }
function fwMsg(msg) { document.getElementById("fw-msg").innerText = msg; }

document.addEventListener("DOMContentLoaded", () => App.init());
