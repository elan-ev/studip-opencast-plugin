<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: fragment == 'videos'
                    }"
                    v-on:click="this.$store.dispatch('setCurrentPlaylist', 'all'); this.$store.dispatch('loadVideos')">
                    <router-link :to="{ name: 'videos' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'playlists' || fragment == 'playlistvideos'
                    }">
                    <router-link :to="{ name: 'playlists' }">
                        Wiedergabelisten
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

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

<script>
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
            showAddDialog: false
        }
    },

    computed: {
        fragment() {
            return this.$route.name;
        }
    },

    mounted() {
        this.$store.dispatch('loadVideos');
    }
}
</script>
