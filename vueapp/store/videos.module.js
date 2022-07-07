import ApiService from "@/common/api.service";

const state = {
    list: [],
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
    videosList(state) {
        return state.list
    }
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
}

const mutations = {
    addVideos(state, videos){
        for (let id in videos) {
            let video = videos[id]
            state.videos[video.token] = video
            if (! state.list.includes(video.token)) {
                state.list.push(video.token)
            }
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
