import ApiService from "@/common/api.service";

const state = {

}

const getters = {

}


const actions = {
    async createLogEvent(context, data) {
        let post = data;
        post.cid = context.rootState.opencast.cid;

        return ApiService.post('log', post);
    }
}

const mutations = {

}


export default {
    state,
    getters,
    mutations,
    actions
}
