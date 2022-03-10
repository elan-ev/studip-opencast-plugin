<template>
    <div class="container" id="app-episodes">
        <Navbar></Navbar>
        <EpisodeSearch></EpisodeSearch>
        <EpisodeList></EpisodeList>

        <MountingPortal mountTo="#action-widget" name="sidebar-actions" append>
            <action-widget
                @uploadVideo="uploadDialog = true"
                @seriesManager="seriesDialog = true"
            ></action-widget>
        </MountingPortal>

        <EpisodeAdd v-if="uploadDialog"
            @done="uploadDialog = false"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"/>

        <keep-alive>
            <SeriesManager v-if="seriesDialog"
                @done="seriesDialog = false"
                @cancel="seriesDialog = false"
            />
        </keep-alive>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import EpisodeList from "@/components/Episodes/EpisodeList"
import EpisodeSearch from "@/components/Episodes/EpisodeSearch"
import Navbar from "@/components/Episodes/Navbar"
import ActionWidget from '../components/Episodes/ActionWidget'
import EpisodeAdd from '@/components/Episodes/EpisodeAdd'
import SeriesManager from '@/components/Series/SeriesManager'

export default {
    name: "Episodes",

    data () {
        return {
            uploadDialog: false,
            seriesDialog: false,
            search: ''
        }
    },

    components: {
        EpisodeList,    Navbar,         ActionWidget,
        EpisodeAdd,     SeriesManager, EpisodeSearch
    },

    computed: {
        ...mapGetters(['currentUser'])
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        },

        seriesManager() {
            this.uploadDialog = true;
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
    }
};
</script>
