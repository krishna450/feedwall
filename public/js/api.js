export const API = {
    base: FEEDWALL_CONFIG.api_url,

    async getWall() {
        const res = await fetch(this.base + "get-wall");
        return res.json();
    },

    async checkNew(lastTimestamp) {
        const res = await fetch(this.base + "get-wall");
        const data = await res.json();

        return data.filter(p => new Date(p.created_at) > new Date(lastTimestamp));
    }
};
