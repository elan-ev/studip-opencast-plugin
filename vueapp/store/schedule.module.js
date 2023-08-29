import ApiService from "@/common/api.service";

const state = {
    schedule_list: [],
    semester_list: [],
    semester_filter: 'all',
    allow_schedule_alternate: false,
    schedule_loading: false
}

const getters = {
    schedule_list(state) {
        return state.schedule_list;
    },

    semester_list(state) {
        return state.semester_list;
    },

    allow_schedule_alternate(state) {
        return state.allow_schedule_alternate;
    },

    semester_filter() {
        return state.semester_filter;
    },

    schedule_loading(state) {
        return state.schedule_loading;
    }
}


const actions = {
    async getScheduleList(context) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }

        let filter = state.semester_filter;
        context.commit('setScheduleLoading', true);
        return ApiService.get('courses/' + $cid + '/' + filter + '/schedule/')
            .then(({ data }) => {
                if (data?.semester_list) {
                    context.commit('setSemesterList', data.semester_list);
                }
                if (data?.schedule_list) {
                    context.commit('setScheduleList', data.schedule_list);
                }
                if (data?.allow_schedule_alternate) {
                    context.commit('setAllowAlternate', data.allow_schedule_alternate);
                }
            }).finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async schedule(context, termin_id) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        context.commit('setScheduleLoading', true);
        return ApiService.post('schedule/' + $cid + '/' + termin_id)
            .finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async scheduleLive(context, termin_id) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        context.commit('setScheduleLoading', true);
        return ApiService.post('schedule/' + $cid + '/' + termin_id).finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async unschedule(context, termin_id) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        context.commit('setScheduleLoading', true);
        return ApiService.delete('schedule/' + $cid + '/' + termin_id)
            .finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async updateSchedule(context, termin_id) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        context.commit('setScheduleLoading', true);
        return ApiService.put('schedule/' + $cid + '/' + termin_id)
            .finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async updateRecordingPeriod(context, params) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        let termin_id = params.termin_id;
        delete params.termin_id;

        context.commit('setScheduleLoading', true);
        return ApiService.put('schedule/' + $cid + '/' + termin_id, params)
            .finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    async bulkScheduling(context, params) {
        let $cid = context.rootState.opencast.cid;
        if ($cid == null) {
            return;
        }
        context.commit('setScheduleLoading', true);
        return ApiService.post('schedulebulk/' + $cid, params)
            .finally(() => {
                context.commit('setScheduleLoading', false);
            });
    },

    setSemesterFilter(context, semester_filter) {
        context.commit('setSemesterFilter', semester_filter);
    },

    clearSemesterFilter(context) {
        context.commit('setSemesterFilter', 'all');
    },

    changeScheduleLoadingState(context, schedule_loading) {
        context.commit('setScheduleLoading', schedule_loading);
    },
}

const mutations = {
    setScheduleList(state, list) {
        state.schedule_list = list;
    },

    setSemesterList(state, list) {
        state.semester_list = list;
    },

    setAllowAlternate(state, allow) {
        state.allow_schedule_alternate = allow;
    },

    setSemesterFilter(state, semester_filter) {
        state.semester_filter = semester_filter;
    },

    setScheduleLoading(state, schedule_loading) {
        state.schedule_loading = schedule_loading;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
