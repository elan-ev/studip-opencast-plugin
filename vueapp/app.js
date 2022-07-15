import { createApp } from 'vue';
import App from './App.vue';
import axios from "axios";
import VueAxios from "vue-axios";

import router from "./router";
import store from "./store";
import "./public-path";

import ApiService from "@/common/api.service";
import DateFilter from "@/common/date.filter";
import DateTimeFilter from "@/common/datetime.filter";
import ErrorFilter from "@/common/error.filter";
import FileSizeFilter from "@/common/filesize.filter";

import { createGettext } from "vue3-gettext";
import translations from './i18n/translations.json';

window.addEventListener("DOMContentLoaded", function() {
    window.Vue = createApp(App);

    let Vue = window.Vue;

    Vue.use(router);
    Vue.use(store);

    Vue.config.devtools = true;

    Vue.config.globalProperties.$filters = {
        date: DateFilter,
        datetime: DateTimeFilter,
        error: ErrorFilter,
        filesize: FileSizeFilter
    };

    let oc_axios = axios.create({
        baseURL: window.OpencastPlugin.API_URL
    });

    Vue.use(VueAxios, oc_axios);

    // Redirect to login page, if a 401 is catched
    Vue.axios.interceptors.response.use((response) => { // intercept the global error
            return response;
        }, function (error) {
            store.dispatch('errorCommit', error.response);

            // Do something with response error
            return Promise.reject(error)
        }
    );

    const gettext = createGettext({
        availableLanguages: {
            en_GB: 'British English',
        },
        defaultLanguage: String.locale.replace('-', '_'),
        translations: translations,
        silent: true,
    });

    Vue.use(gettext);

    if (window.OpencastPlugin.CID !== undefined) {
        store.dispatch('updateCid', window.OpencastPlugin.CID);
    }

    store.dispatch('updateSite', window.OpencastPlugin.ROUTE);

    Vue.mount('#opencast');
});
