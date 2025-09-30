import axios from "axios";

let oc_axios = axios.create();

oc_axios.CancelToken = axios.CancelToken;
oc_axios.isCancel    = axios.isCancel;

export default oc_axios;

/**
 * Configures Axios instance with request and connection timeouts.
 *
 * - Sets the total request timeout using the `timeout` option (in milliseconds).
 * - Adds a response interceptor to log and handle request timeouts.
 * - Adds a request interceptor to implement a custom connection timeout using CancelToken.
 *   (Skips connection timeout if `config.connect_timeout` is set to 0.)
 * - Adds a response interceptor to clear connection timeout timers after each request.
 *
 * @param {AxiosInstance} axios - The Axios instance to configure.
 * @param {Object} server_options - Server options containing timeout settings.
 * @returns {AxiosInstance} The configured Axios instance.
 */
export function applyAxiosTimeouts(axios, server_options) {

    const newAxios = _cloneAxiosInstance(axios);

    const timeout = server_options?.timeout ?? 0;
    const connect_timeout = server_options?.connect_timeout ?? 0;

    if (timeout > 0) {
        newAxios.defaults.timeout = timeout;

        newAxios.interceptors.response.use(
            response => response,
            error => {
                // Log the error in case of timeout!
                if (error.code === 'ECONNABORTED') {
                    error.message = `Request timed out after ${timeout}ms`;
                    console.error(error.message);
                }
                return Promise.reject(error);
            }
        );
    }

    if (connect_timeout > 0) {
        // Applying connection timeout.
        newAxios.interceptors.request.use(config => {

            // Prevent connect timeout!
            if (config.connect_timeout === 0) {
                return config;
            }

            const source = newAxios.CancelToken.source();
            config.cancelToken = source.token;

            // Record the timeout object in the metadata, in order to clear it later on!
            config.metadata = config.metadata || {};

            // Set a timer to cancel the connection after connect_timeout
            if (!config?.metadata?.connect_timeout_tm) {
                const connect_timeout_tm = setTimeout(() => {
                    let connect_timeout_msg = `Connection timeout of ${connect_timeout}ms exceeded`;
                    source.cancel(connect_timeout_msg);
                    console.error(connect_timeout_msg);
                }, connect_timeout);
                config.metadata.connect_timeout_tm = connect_timeout_tm;
            }
            return config;
        });

        newAxios.interceptors.response.use(
            response => {
                if (response?.config?.metadata?.connect_timeout_tm) {
                    clearTimeout(response.config.metadata.connect_timeout_tm);
                }
                return response;
            },
            error => {
                if (error?.config?.metadata?.connect_timeout_tm) {
                    clearTimeout(error.config.metadata.connect_timeout_tm);
                }
                return Promise.reject(error);
            }
        );
    }

    return newAxios;
}

/**
 * Clones an Axios instance, including its config and interceptors.
 *
 * @param {AxiosInstance} axios - The Axios instance to clone.
 * @returns {AxiosInstance} The cloned Axios instance.
 */
function _cloneAxiosInstance(axios) {
    const newAxios = axios.create({ ...axios.defaults });

    // Copy request interceptors
    axios.interceptors.request.forEach(interceptor => {
        newAxios.interceptors.request.use(interceptor.fulfilled, interceptor.rejected);
    });

    // Copy response interceptors
    axios.interceptors.response.forEach(interceptor => {
        newAxios.interceptors.response.use(interceptor.fulfilled, interceptor.rejected);
    });

    newAxios.CancelToken = axios.CancelToken;
    newAxios.isCancel    = axios.isCancel;

    return newAxios;
}
