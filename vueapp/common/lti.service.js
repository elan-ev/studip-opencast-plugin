import Vue from "vue";
import axios from "axios";
import VueAxios from "vue-axios";

let lti;

const LTIService = {
    init(lti) {
        this.lti = lti;
    },

    check()
    {
        return Vue.axios({
            method: 'POST',
            url: this.lti.launch_url,
            data: new URLSearchParams(this.lti.launch_data),
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            }
        });
    },

    get(resource) {
        return Vue.axios({
            url: resource,
            baseURL: this.lti.launch_url
        });
    },

    post(resource, params) {
        return Vue.axios({
            method: 'POST',
            url: resource,
            baseURL: this.lti.launch_url,
            data: params
        });
    }
};

export default LTIService;
