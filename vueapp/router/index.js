import { createRouter, createWebHashHistory } from 'vue-router';

export default new createRouter({
    history: createWebHashHistory(),
    base: window.location.pathname,
    routes: [
        {
            path: "/contents",
            name: "contents",
            component: () => import("@/views/Contents"),
            children: [
                {
                    path: "videos",
                    name: "videos",
                    component: () => import("@/views/Videos"),
                },
                {
                    path: "playlists",
                    name: "playlists",
                    component: () => import("@/views/Playlists"),
                },
                {
                    path: "playlistvideos",
                    name: "playlistvideos",
                    component: () => import("@/views/PlaylistVideos"),
                },
            ]
        },

        {
            path: "/admin",
            name: "admin",
            component: () => import("@/views/Admin"),
        },

        {
            path: "/course",
            name: "course",
            component: () => import("@/views/Course.vue"),
            children: [
                {
                    path: "videos",
                    name: "coursevideos",
                    component: () => import("@/views/Videos"),
                },
            ]
        },
    ]
});