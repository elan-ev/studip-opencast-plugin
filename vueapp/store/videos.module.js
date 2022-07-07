import ApiService from "@/common/api.service";

const state = {
    videos: {},
    requestTime: null
}

const getters = {
    requestTime(state) {
        return state.requestTime
    },
    videos(state) {
        return state.videos
    },
}


const actions = {
    async loadVideos(context, offset = 0, limit = 20) {
        if (context.state.requestTime == null){
            context.commit('setRequestTime', Date.now());
        }
        const params = new URLSearchParams();
        params.append('requestTime', context.state.requestTime);
        params.append('offset', offset);
        params.append('limit', limit);
        return ApiService.get('videos', { params })
            .then(({ data }) => {
                context.commit('addVideos', data);
            });
    },

    async deleteVideo(context, id) {
        // TODO
    }
}

const mutations = {
    addVideos(state, videos){
        for (let id in videos) {
            state.videos[videos[id].token] = videos[id]
        }
    },

    setRequestTime(state, requestTime) {
        state.requestTime = requestTime;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
