const ApiService = {

    query(resource, params) {
        return window.Vue.axios.get(resource, params);
    },

    get(resource, params = {}) {
        return window.Vue.axios.get(`${resource}`, params);
    },

    post(resource, params) {
        return window.Vue.axios.post(`${resource}`, params);
    },

    update(resource, slug, params) {
        return window.Vue.axios.put(`${resource}/${slug}`, params);
    },

    put(resource, params) {
        return window.Vue.axios.put(`${resource}`, params);
    },

    delete(resource) {
        return window.Vue.axios.delete(resource);
    }
};

export default ApiService;