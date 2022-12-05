import { triggerRef } from 'vue';
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
                    path: 'playlists/:token/edit/',
                    name: 'playlist_edit',
                    props: true,
                    component: () => import("@/views/PlaylistEdit"),
                }
            ]
        },

        {
            path: "/admin",
            name: "admin",
            component: () => import("@/views/Admin"),
        },

        {
            path: "/course",
            component: () => import("@/views/Course.vue"),
            children: [
                {
                    path: "videos",
                    name: "course",
                    component: () => import("@/views/CoursesVideos"),
                },
                {
                    path: "schedule",
                    name: "schedule",
                    component: () => import("@/views/Schedule"),
                },
            ]
        },
    ]
});