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
import HelpUrlFilter from "@/common/helpurl.filter";

import vSelect from "vue-select";

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
        filesize: FileSizeFilter,
        helpurl: HelpUrlFilter
    };

    let oc_axios = axios.create({
        baseURL: window.OpencastPlugin.API_URL
    });

    oc_axios.CancelToken = axios.CancelToken;
    oc_axios.isCancel    = axios.isCancel;

    Vue.use(VueAxios, oc_axios);

    // Catch errors
    Vue.axios.interceptors.response.use((response) => { // intercept the global error
            store.dispatch('axiosStop');

            return response;
        }, function (error) {
            store.dispatch('axiosStop');

            store.dispatch('errorCommit', error.response);

            // Do something with response error
            return Promise.reject(error)
        }
    );

     // set loading animation
     Vue.axios.interceptors.request.use(
        request => {
            store.dispatch('axiosStart');

            return request;
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

    Vue.component("v-select", vSelect);

    if (window.OpencastPlugin.CID !== undefined) {
        store.dispatch('updateCid', window.OpencastPlugin.CID);
    }

    store.dispatch('updateSite', window.OpencastPlugin.ROUTE);

    Vue.mount('#opencast');
});
