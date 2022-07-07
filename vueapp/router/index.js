import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

export default new Router({
    routes: [
        {
            path: "/",
            name: "videos",
            component: () => import("@/views/Videos"),
        },
        {
            path: "/playlists",
            name: "playlists",
            component: () => import("@/views/Playlists"),
        },
        {
            path: "/admin",
            name: "admin",
            component: () => import("@/views/Admin"),
        },
    ]
});