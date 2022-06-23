import Vue from "vue";
import Vuex from "vuex";

import error from "./error.module";
import config from "./config.module";
import messages from "./messages.module";
import lti from "./lti.module";

Vue.use(Vuex);
Vue.config.devtools = true // Need this to use devtool browser extension

export default new Vuex.Store({
  modules: {
    error,
    config,
    messages,
    lti,
  }
});