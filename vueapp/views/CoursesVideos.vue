<template>
    <div>
        <MessageBox type="info" v-if="!hasDefaultPlaylist">
            {{
                $gettext(
                    'Für diesen Kurs existiert noch keine Standard-Kurswiedergabeliste. Bitte erstellen Sie diese über das Aktionsmenü.'
                )
            }}
        </MessageBox>
        <template v-else>
            <ContentBar>
                <template #title>
                    <StudipIcon shape="opencast" :size="24" role="info"/>
                    <span>{{ $gettext('Opencast Videos') }}</span>
                </template>
                <template #nav>
                    <Tabs v-model="tabSelection">
                        <Tab :name="$gettext('Übersicht')"></Tab>
                        <Tab :name="$gettext('Videos')"></Tab>
                        <Tab :name="$gettext('Wiedergabelisten')"></Tab>
                        <Tab :name="$gettext('Aufzeichnungen planen')"></Tab>
                        <Tab :name="$gettext('Informationen')"></Tab>
                    </Tabs>
                </template>
                <template #search>
                    <ContentBarSearch />
                </template>
            </ContentBar>
            <div class="oc--course-videos-content">
            <VideosOverview v-if="tabSelection === 0" />
            <VideosAllInCourse v-if="tabSelection === 1" />
            <VideosTable v-if="playlist && tabSelection === 4 " :playlist="playlist" :cid="cid" :canEdit="canEdit" :canUpload="canUpload" />
            </div>
        </template>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import VideosTable from '@/components/Videos/VideosTable';
import VideosOverview from '@/components/Videos/VideosOverview';
import VideosAllInCourse from '@/components/Videos/VideosAllInCourse.vue'
import MessageBox from '@/components/MessageBox.vue';
import ContentBar from '@components/Layouts/ContentBar.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import ContentBarSearch from '@/components/ContentBarSearch.vue';
import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'CourseVideos',
    components: {
        VideosTable,
        VideosOverview,
        VideosAllInCourse,
        MessageBox,
        ContentBar,
        Tab,
        Tabs,
        ContentBarSearch,
        StudipIcon
    },
    data() {
        return {
            tabSelection: 0,
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid',]),
        ...mapGetters('playlists', ['defaultPlaylist', 'playlist',]),
        ...mapGetters('config', ['course_config']),

        canEdit() {
            return this.course_config?.edit_allowed ?? false;
        },

        canUpload() {
            return this.course_config?.upload_allowed ?? false;
        },

        hasDefaultPlaylist() {
            // if the course config is not available, assume it has a default playlist
            if (!this.course_config) {
                return true;
            }

            return this.course_config?.has_default_playlist;
        },
    },

    mounted() {
        this.$store.dispatch('playlists/loadPlaylists').then(() => {
            this.$store.dispatch('playlists/setPlaylist', this.defaultPlaylist);
        });
    },
};
</script>
