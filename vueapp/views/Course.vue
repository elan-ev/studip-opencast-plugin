<template>
    <div class="container" id="app-episodes">
        <router-view></router-view>

        <MountingPortal mountTo="#sidebar-navigation" name="sidebar-widget">
            <navigation-widget></navigation-widget>
        </MountingPortal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import NavigationWidget from '../components/NavigationWidget';

export default {
    name: "Episodes",

    components: {
        NavigationWidget
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        }
    }
};
</script>
