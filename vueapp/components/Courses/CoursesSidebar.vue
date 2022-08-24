<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentPage == 'videos'
                    }"
                    v-on:click="setPage('videos')">
                    <router-link :to="{ name: 'course' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: currentPage == 'schedule'
                    }"
                    v-if="can_schedule"
                    v-on:click="getScheduleList">
                    <router-link :to="{ name: 'schedule' }">
                        Aufzeichnungen planen
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget" v-if="currentPage == 'videos'">
        <div class="sidebar-widget-header" v-translate>
            Wiedergabelisten
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentPlaylist == 'all'
                    }"
                    v-on:click="setPlaylist('all')">
                    <router-link :to="{ name: 'course' }">
                        Alle Videos
                    </router-link>
                </li>
                <li :class="{
                    active: currentPlaylist == playlist.token
                    }"
                    v-for="playlist in playlists"
                    v-bind:key="playlist.token"
                    v-on:click="setPlaylist(playlist.token)">
                    <router-link :to="{ name: 'course' }">
                        {{ playlist.title }}
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <template v-if="currentPage == 'schedule'">
        <div v-if="semester_list.length" class="sidebar-widget " id="sidebar-actions">
            <div class="sidebar-widget-header" v-translate>
                Semesterfilter
            </div>
            <div class="sidebar-widget-content">
                <select class="sidebar-selectlist submit-upon-select" v-model="semesterFilter">
                    <option v-for="semester in semester_list"
                        :key="semester.id"
                        :value="semester.id"
                        :selected="semester.id == semester_filter"
                        v-translate>
                        {{ semester.name }}
                    </option>
                </select>
            </div>
        </div>
    </template>
    <template v-else>
        <div class="sidebar-widget " id="sidebar-actions">
            <div class="sidebar-widget-header" v-translate>
                Aktionen
            </div>
            <div class="sidebar-widget-content">
                <ul class="widget-list oc--sidebar-links widget-links">
                    <li @click="$emit('uploadVideo')">
                        <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                        Medien Hochladen
                    </li>
                    <li @click="$emit('recordVideo')">
                        <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                        Video Aufnehmen
                    </li>
                </ul>
            </div>
        </div>
    </template>
</template>

<script>
import { mapGetters } from "vuex";

import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon
    },

    emits: ['uploadVideo', 'recordVideo'],

    watch: {
        $route(to) {
            //console.log('Route:', to);
        },
    },

    data() {
        return {
            showAddDialog: false,
            semesterFilter: null
        }
    },

    methods: {
        setPlaylist(token) {
            this.$store.dispatch('setCurrentPlaylist', token);
            this.$store.dispatch('loadVideos');
        },

        getScheduleList() {
            this.$store.dispatch('setPage', 'schedule');
            this.$store.dispatch('clearMessages');
            this.$store.dispatch('getScheduleList');
        },

        setPage(page) {
            this.$store.dispatch('setPage', page);
            this.$store.dispatch('loadVideos');
        },
    },

    computed: {
        ...mapGetters(["playlists", "currentPlaylist", "currentPage",
            "cid", "semester_list", "semester_filter", 'currentUser']),

        fragment() {
            return this.$route.name;
        },

        can_schedule() {
            return this.cid !== undefined && this.currentUser.can_edit;
        }
    },

    mounted() {
        this.$store.dispatch('loadPlaylists');
        this.$store.dispatch('loadVideos');
        this.semesterFilter = this.semester_filter;
    },

    watch: {
        semesterFilter(newValue, oldValue) {
            if (newValue && oldValue && newValue != oldValue) {
                this.$store.dispatch('setSemesterFilter', newValue);
                this.$store.dispatch('clearMessages');
                this.$store.dispatch('getScheduleList');
            }
        }
    },
}
</script>
