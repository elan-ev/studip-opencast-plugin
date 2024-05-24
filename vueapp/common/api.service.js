import axios from "@/common/axios.service";

const ApiService = {

    query(resource, params) {
        return axios.get(resource, params);
    },

    get(resource, params = {}) {
        return axios.get(`${resource}`, params);
    },

    post(resource, params) {
        return axios.post(`${resource}`, params);
    },

    update(resource, slug, params) {
        return axios.put(`${resource}/${slug}`, params);
    },

    put(resource, params) {
        return axios.put(`${resource}`, params);
    },

    patch(resource, params) {
        return axios.patch(`${resource}`, params);
    },

    delete(resource, params) {
        return axios.delete(resource, params);
    }
};

export default ApiService;