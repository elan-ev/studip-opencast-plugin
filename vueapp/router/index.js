import Vue from "vue";
import Router from "vue-router";

Vue.use(Router);

export default new Router({
    routes: [
        {
            name: "course",
            path: "/",
            component: () => import("@/views/Course"),
        },
        {
            name: "episodes",
            path: "/",
            component: () => import("@/views/Episodes"),
        },
        {
            name: "scheduler",
            path: "/",
            component: () => import("@/views/Scheduler"),
        },
        {
            name: "manager",
            path: "/",
            component: () => import("@/views/Manager"),
        },
    ]

    /*
    routes: [
        {
            path: "/",
            component: () => import("@/views/AdminWizard"),

            children: [
                {
                    name: "admin",
                    path: "step1",
                    component: () => import("@/views/AdminBasic")
                },
                {
                    name: "admin_step2",
                    path: "step2",
                    component: () => import("@/views/AdminOptions"),
                    props: true
                }
            ]
        },
        {
            path: "/course",
            component: () => import("@/views/Course"),

            children: [
                {
                    name: "course",
                    path: "episodes",
                    component: () => import("@/views/Episodes")
                },
                {
                    name: "scheduler",
                    path: "scheduler",
                    component: () => import("@/views/Scheduler")
                },
                {
                    name: "management",
                    path: "management",
                    component: () => import("@/views/Manager")
                }
            ]
        }
    ]
    */
});
