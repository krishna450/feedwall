import { API } from './api.js';
import { State } from './state.js';
import { Canvas } from './canvas.js';

document.addEventListener("DOMContentLoaded", async () => {

    const root = document.getElementById("feedwall-root");
    const canvas = new Canvas(root);

    // Initial load
    const posts = await API.getWall();
    State.setPosts(posts);
    canvas.render();

    // Silent polling
    setInterval(async () => {
        const newPosts = await API.checkNew(State.lastFetch);

        if (newPosts.length > 0) {
            showNewButton(newPosts, canvas);
        }
    }, 30000);

});

function showNewButton(newPosts, canvas) {
    let btn = document.getElementById('fw-new-btn');

    if (!btn) {
        btn = document.createElement('button');
        btn.id = 'fw-new-btn';
        btn.innerText = "↑ New Posts";

        document.body.appendChild(btn);
    }

    btn.onclick = () => {
        State.addNewPosts(newPosts);
        canvas.render();
        btn.remove();
    };
}
