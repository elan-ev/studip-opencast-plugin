import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

export default new Router({
    routes: [
        {
            path: "/",
            name: "video",
            component: () => import("@/views/Video"),
        },
        {
            path: "/playlist",
            name: "playlist",
            component: () => import("@/views/Playlist"),
        },
        {
            path: "/admin",
            name: "admin",
            component: () => import("@/views/Admin"),
        },
    ]
});