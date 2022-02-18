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

    setLaunchData(lti) {
        this.lti = lti;
    }

    async getLaunchUrl() {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return this.lti.launch_url;
    }

    constructor(config_id, endpoints) {
        this.config_id     = config_id;
        this.endpoints     = endpoints;
        this.authenticated = false;
        this.lti           = null;
        this.lifetime      = 0;
    }

    belongsTo(config_id, endpoint) {
        return (
            this.config_id == config_id
            && this.endpoints.includes(endpoint)
        )
    }

    isAuthenticated() {
        return (
            this.lti !== null &&
            this.lifetime >= Math.round(Date.now() / 1000)
            && this.authenticated
        );
    }

    async authenticate()
    {
        try {
            if (this.lti === null) {
                throw new LtiException('no lti launch data set!');
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

    async get(resource) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return Vue.axios({
            url: resource,
            baseURL: this.lti.launch_url
        });
    }

    async post(resource, params) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return Vue.axios({
            method: 'POST',
            url: resource,
            baseURL: this.lti.launch_url,
            data: params
        });
    }

    async getNewMediaPackage() {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        //return Vue.axios
    }
};

export { LtiService, LtiException };
