<template>
    <div class="container" id="app-episodes">
        <Navbar></Navbar>
        <EpisodeSearch></EpisodeSearch>
        <EpisodeSort></EpisodeSort>
        <EpisodeList></EpisodeList>

        <MountingPortal mountTo="#action-widget" name="sidebar-actions" append>
            <action-widget
                @uploadVideo="uploadDialog = true"
                @recordVideo="recordVideo"
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
import EpisodeSort from "@/components/Episodes/EpisodeSort"
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
        EpisodeAdd,     SeriesManager, EpisodeSearch,
        EpisodeSort
    },

    computed: {
        ...mapGetters(['currentUser', 'course_series', 'config'])
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        },

        recordVideo() {
            window.open(this.config['service_url'] + "/studio/index.html") // TODO add params
        },

        seriesManager() {
            this.uploadDialog = true;
        }
    },

    async mounted() {
        this.$store.dispatch('authenticateLti');
        await this.$store.dispatch('loadCourseSeries');
        this.$store.dispatch('configRead', this.course_series[0]['config_id']);
    }
};
</script>
