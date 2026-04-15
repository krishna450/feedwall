document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("feedwall-root");

    if (!root) return;

    root.innerHTML = `
        <div id="feedwall-app">
            <h2>Feedwall Loading...</h2>
        </div>
    `;

    console.log("Feedwall SPA Initialized");
});
