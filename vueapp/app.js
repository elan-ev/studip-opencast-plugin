import { createApp } from 'vue';
import App from './App.vue';
import axios from "@/common/axios.service";
import VueAxios from "vue-axios";

import router from "./router";
import store from "./store";
import "./public-path";

import DateFilter from "@/common/date.filter";
import DateTimeFilter from "@/common/datetime.filter";
import PermNameFilter from "@/common/permname.filter";
import ErrorFilter from "@/common/error.filter";
import FileSizeFilter from "@/common/filesize.filter";
import HelpUrlFilter from "@/common/helpurl.filter";

import vSelect from "vue3-select";

import { createGettext } from "vue3-gettext";
import translations from './i18n/translations.json';

window.addEventListener("DOMContentLoaded", function() {
    const Vue = createApp(App);

    Vue.use(router);
    Vue.use(store);

    Vue.config.devtools = true;

    Vue.config.globalProperties.$filters = {
        date: DateFilter,
        datetime: DateTimeFilter,
        permname: PermNameFilter,
        error: ErrorFilter,
        filesize: FileSizeFilter,
        helpurl: HelpUrlFilter
    };

    axios.defaults.baseURL = window.OpencastPlugin.API_URL;
    // Catch errors
    axios.interceptors.response.use((response) => { // intercept the global error
            store.dispatch('axiosStop');

            return response;
        }, function (error) {
            store.dispatch('axiosStop');

            if (error.data !== undefined) {
                store.dispatch('errorCommit', error.response);
            }

            // Do something with response error
            return Promise.reject(error)
        }
    );

     // set loading animation
     axios.interceptors.request.use(
        request => {
            store.dispatch('axiosStart');

            return request;
        }
    );

    Vue.use(VueAxios, axios);

    const gettext = createGettext({
        availableLanguages: {
            //de_DE: 'Deutsch',
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
