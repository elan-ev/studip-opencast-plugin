import { createStore } from 'vuex';

import avatar from './avatar.module';
import config from "./config.module";
import messages from "./messages.module";
import opencast from "./opencast.module";
import videos from "./videos.module";
import playlists from "./playlists.module";
import schedule from "./schedule.module";
import videodrawer from "./videodrawer.module";

export default createStore({
  modules: {
    avatar,
    config,
    messages,
    opencast,
    videos,
    playlists,
    schedule,
    videodrawer
  }
});