import { createStore } from 'vuex';

import config from "./config.module";
import messages from "./messages.module";
import opencast from "./opencast.module";
import videos from "./videos.module";
import playlists from "./playlists.module";
import log from "./log.module";
import schedule from "./schedule.module";


export default createStore({
  modules: {
    config,
    messages,
    opencast,
    videos,
    playlists,
    log,
    schedule
  }
});