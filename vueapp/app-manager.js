import Vue from 'vue';
import Manager from './views/Manager.vue';

import router from "./router";
import store from "./store";
import "./public-path";

import ApiService from "./common/api.service";
import DateFilter from "./common/date.filter";
import ErrorFilter from "./common/error.filter";
import I18N from "./common/i18n.filter";

import { createProvider } from "./vue-apollo";

Vue.filter("date", DateFilter);
Vue.filter("error", ErrorFilter);
Vue.filter("i18n", I18N);

ApiService.init();

// Redirect to login page, if a 401 is catched
Vue.axios.interceptors.response.use((response) => { // intercept the global error
        store.dispatch('errorClear');

        return response;
    }, function (error) {
        store.dispatch('errorCommit', error.response);

        // Do something with response error
        return Promise.reject(error)
    }
);

$(function() {
    window.Vue = new Vue({
        router,
        store,
        apolloProvider: createProvider(),
        render: h => h(Manager)
    }).$mount('#app-manager');

    window.Vue.axios.defaults.baseURL = API_URL;
});
