export const State = {
    posts: [],
    lastFetch: null,

    setPosts(data) {
        this.posts = data;
        this.lastFetch = new Date().toISOString();
    },

    addNewPosts(newPosts) {
        this.posts = [...newPosts, ...this.posts];
    }
};
