import Vue from 'vue';
import App from './App.vue';

import router from "./router";
import store from "./store";
import "./public-path";

import ApiService from "./common/api.service";
import DateFilter from "./common/date.filter";
import ErrorFilter from "./common/error.filter";
import GetTextPlugin from 'vue-gettext';
import translations from './i18n/translations.json';

import { createProvider } from "./vue-apollo";


import PortalVue from 'portal-vue'

Vue.use(PortalVue)

Vue.filter("date", DateFilter);
Vue.filter("error", ErrorFilter);

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

Vue.use(GetTextPlugin, {
    availableLanguages: {
        en_GB: 'British English',
    },
    defaultLanguage: String.locale.replace('-', '_'),
    translations: translations,
    silent: true,
});

$(function() {
    window.Vue = new Vue({
        router,
        store,
        apolloProvider: createProvider(),
        render: h => h(App)
    }).$mount('#app');

    store.dispatch('setCID', CID);
    window.apolloEndpoint = API_URL + '/graphql';
    window.Vue.axios.defaults.baseURL = API_URL;
});
