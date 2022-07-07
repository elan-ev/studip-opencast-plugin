
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
    }

    belongsTo(config_id, endpoint) {
        return (
            this.config_id == config_id
            && this.endpoints.includes(endpoint)
        )
    }

    async checkConnection() {
        let obj = this;

        return window.Vue.axios({
            method: 'GET',
            url: this.lti.launch_url,
            crossDomain: true,
            withCredentials: true
        }).then(({ data }) => {
            if (!data.roles) {
                obj.authenticated = false;
            }
        }).catch(function (error) {
            obj.authenticated = false;
        });
    }

    isAuthenticated() {
        return (
            this.lti !== null && this.authenticated
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

        return await window.Vue.axios({
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
            return true;
        }).catch(function (error) {
            return error;
        });
    }

    async get(resource) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return window.Vue.axios({
            url: resource,
            baseURL: this.lti.launch_url
        });
    }

    async post(resource, params) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return window.Vue.axios({
            method: 'POST',
            url: resource,
            baseURL: this.lti.launch_url,
            data: params
        });
    }
}

export { LtiService, LtiException };
