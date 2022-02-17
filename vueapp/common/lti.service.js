import Vue from "vue";
import axios from "axios";
import VueAxios from "vue-axios";
import ApiService from "@/common/api.service";

/*
let
    lti,
    lifetime,
    config_id,
    authenticated = false;
*/

function LtiException() {};

/**
 * This object manages the connection and calls to the lti-service
 *
 * LTI generation is done on server side to prevent leaking of credentials
 *
 * @type {Object}
 */
class LtiService {

    loadLaunchData() {
        return ApiService.get('/lti/launch_data/' + this.config_id)
        .then(({ data }) => {
            if (data.lti.length == 0) {
                throw new LtiException('could not retrieve launch data from server!');
            } else {
                this.lti = data.lti;
            }
        });
    }

    setLaunchData(lti) {
        this.lti = lti;
    }

    getLaunchUrl() {
        if (!this.isAuthenticated()) {
            throw new LtiException();
        }

        return this.lti.launch_url;
    }

    constructor(config_id) {
        this.config_id     = config_id;
        this.authenticated = false;
        this.lti           = null;
        this.lifetime      = 0;
    }

    isAuthenticated() {
        return (
            this.lifetime >= Math.round(Date.now() / 1000)
            && this.authenticated
        );
    }

    async authenticate()
    {
        try {
            if (this.lti === null) {
                await this.loadLaunchData();
            }
        } catch (e) {
            return e;
        }

        let obj = this;

        return await Vue.axios({
            method: 'POST',
            url: this.lti.launch_url,
            data: new URLSearchParams(this.lti.launch_data),
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        }).then(() => {
            obj.authenticated = true;
            obj.lifetime      = Math.round(Date.now() / 1000) + 1800;
            return true;
        }).catch(function (error) {
            return error;
        });
    }

    get(resource) {
        if (!this.isAuthenticated()) {
            throw new LtiException();
        }

        return Vue.axios({
            url: resource,
            baseURL: this.lti.launch_url
        });
    }

    post(resource, params) {
        if (!this.isAuthenticated()) {
            throw new LtiException();
        }

        return Vue.axios({
            method: 'POST',
            url: resource,
            baseURL: this.lti.launch_url,
            data: params
        });
    }

    getNewMediaPackage() {
        if (!this.isAuthenticated()) {
            throw new LtiException();
        }

        //return Vue.axios
    }
};

export { LtiService, LtiException };
