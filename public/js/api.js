const API_BASE = FEEDWALL_CONFIG.api_url;

const App = {
    token: localStorage.getItem("fw_token") || null,

    init() {
        this.root = document.getElementById("feedwall-root");

        if (!this.root) return;

        if (this.token) {
            this.loadApp();
        } else {
            this.showAuth();
        }
    },

    // ---------------- AUTH UI ----------------

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
        const username = document.getElementById("fw-username").value;
        const passcode = document.getElementById("fw-passcode").value;

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
            this.showMsg(data.error || "Login failed");
        }
    },

    async register() {
        const username = document.getElementById("fw-username").value;
        const passcode = document.getElementById("fw-passcode").value;

        const res = await fetch(API_BASE + "register", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, passcode })
        });

        const data = await res.json();

        if (data.success) {
            this.showMsg("Registered! Now login.");
        } else {
            this.showMsg(data.error || "Registration failed");
        }
    },

    showMsg(msg) {
        document.getElementById("fw-msg").innerText = msg;
    },

    logout() {
        localStorage.removeItem("fw_token");
        this.token = null;
        this.showAuth();
    },

    // ---------------- MAIN APP ----------------

    async loadApp() {
        this.root.innerHTML = `
            <div id="fw-app">
                <div id="fw-topbar">
                    <button id="fw-logout">Logout</button>
                    <button id="fw-new-post">+ Post</button>
                </div>

                <div id="fw-canvas"></div>

                <div id="fw-post-box" class="hidden">
                    <textarea id="fw-post-text" placeholder="What's happening?"></textarea>
                    <input type="file" id="fw-post-image" />
                    <button id="fw-submit-post">Submit</button>
                </div>
            </div>
        `;

        document.getElementById("fw-logout").onclick = () => this.logout();
        document.getElementById("fw-new-post").onclick = () => this.togglePostBox();
        document.getElementById("fw-submit-post").onclick = () => this.submitPost();

        this.loadWall();
    },

    togglePostBox() {
        const box = document.getElementById("fw-post-box");
        box.classList.toggle("hidden");
    },

    async submitPost() {
        const text = document.getElementById("fw-post-text").value;
        const file = document.getElementById("fw-post-image").files[0];

        const formData = new FormData();
        formData.append("content_text", text);
        if (file) formData.append("image", file);

        const res = await fetch(API_BASE + "submit-post", {
            method: "POST",
            headers: {
                "Authorization": "Bearer " + this.token
            },
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            this.togglePostBox();
            this.loadWall();
        } else {
            alert(data.error || "Failed");
        }
    },

    async loadWall() {
        const res = await fetch(API_BASE + "get-wall");
        const posts = await res.json();

        const canvas = document.getElementById("fw-canvas");

        canvas.innerHTML = posts.map(p => `
            <div class="fw-post">
                ${p.image_path ? `<img src="/wp-content/uploads/feedwall_media/${p.image_path}_thumb.jpg">` : ""}
                <p>${p.content_text}</p>
                <button onclick="App.openComments(${p.post_id})">💬</button>
            </div>
        `).join("");
    },

    async openComments(postId) {
        const res = await fetch(API_BASE + "get-comments?post_id=" + postId);
        const comments = await res.json();

        const modal = document.createElement("div");
        modal.className = "fw-modal";

        modal.innerHTML = `
            <div class="fw-modal-box">
                <h3>Comments</h3>

                <div class="fw-comments">
                    ${comments.map(c => `<p>${c.content_text}</p>`).join("")}
                </div>

                <input id="fw-comment-input" placeholder="Write..." />
                <button id="fw-send">Send</button>
                <button id="fw-close">Close</button>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById("fw-close").onclick = () => modal.remove();

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
