import { LtiService } from "@/common/lti.service";
import ApiService from "@/common/api.service";

const state = {
    lti_connections: [],
    launch_data: null
}

const getters = {
    lti_connections(state) {
        return state.lti_connections
    },

    launch_data(state) {
        return state.launch_data
    }
}


const actions = {

    async loadLaunchData(context) {
        return ApiService.get('lti/launch_data/')
        .then(({ data }) => {
            if (data.lti.length == 0) {
                throw new LtiException('could not retrieve launch data from server!');
            } else {
                context.commit('setLaunchData', data.lti);
            }
        });
    },

    async authenticateLti(context) {
        // check, if all present lti-connections are authenticated
        let reauth_necessary = false;

        if (context.state.launch_data == null) {
            reauth_necessary = true;
        } else {
            for (let i = 0;  i < context.state.lti_connections.length; i++) {
                let lti = context.state.lti_connections[i];
                // check if we still are authenticated on the remote server
                await lti.checkConnection();
                if (!lti.isAuthenticated()) {
                    reauth_necessary = true;
                }
            }
        }

        console.log('LTI reauthentication necessary?', reauth_necessary);

        // if no launch_data has yet been retrieved or some connections are not authenticated,
        // load launch data from server and recreate all lti connections and authenticate them
        if (reauth_necessary) {
            let lti_connections = [];
            await context.dispatch('loadLaunchData');

            for (let id in context.state.launch_data) {
                let data = context.state.launch_data[id];

                context.commit('setLti', []);
                let lti = new LtiService(data.config_id, data.endpoints);
                lti.setLaunchData(data);
                lti.authenticate();

                lti_connections.push(lti);
            }

            context.commit('setLti', lti_connections);
        }
    }
}

const mutations = {
    setLti(state, data) {
        state.lti_connections = data;
    },

    setLaunchData(state, data) {
        state.launch_data = data;
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
