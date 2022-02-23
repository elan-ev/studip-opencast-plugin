import Vue from 'vue';
import App from './App.vue';

import router from "./router";
import store from "./store";
import "./public-path";

import ApiService from "@/common/api.service";
import DateFilter from "@/common/date.filter";
import DateTimeFilter from "@/common/datetime.filter";
import ErrorFilter from "@/common/error.filter";
import FileSizeFilter from "@/common/filesize.filter";
import GetTextPlugin from 'vue-gettext';


import translations from './i18n/translations.json';
import  { createPopper } from '@popperjs/core';

import { createProvider } from "./vue-apollo";


import PortalVue from 'portal-vue'

Vue.config.devtools = true // Need this to use devtool browser extension
Vue.use(PortalVue)

Vue.filter("date", DateFilter);
Vue.filter("datetime", DateTimeFilter);
Vue.filter("error", ErrorFilter);
Vue.filter("filesize", FileSizeFilter);

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
        name: 'Opencast Vue',
        router,
        store,
        apolloProvider: createProvider(),
        render: h => h(App)
    }).$mount('#opencast');

    if (CID !== null) {
        store.dispatch('setCID', CID);
    }
    window.apolloEndpoint = API_URL + '/graphql';
    window.Vue.axios.defaults.baseURL = API_URL;
});
