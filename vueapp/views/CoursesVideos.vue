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
                    <h2>{{ $gettext('Opencast Videos') }}</h2>
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
            <VideosTable v-if="playlist && tabSelection === 0 " :playlist="playlist" :cid="cid" :canEdit="canEdit" :canUpload="canUpload" />
        </template>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import VideosTable from '@/components/Videos/VideosTable';
import MessageBox from '@/components/MessageBox.vue';
import ContentBar from '@components/Layouts/ContentBar.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import ContentBarSearch from '@/components/ContentBarSearch.vue';

export default {
    name: 'CourseVideos',
    components: {
        VideosTable,
        MessageBox,
        ContentBar,
        Tab,
        Tabs,
        ContentBarSearch,
    },
    data() {
        return {
            tabSelection: 0,
        };
    },

    computed: {
        ...mapGetters(['playlist', 'cid', 'defaultPlaylist', 'course_config']),

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
        this.$store.dispatch('loadPlaylists').then(() => {
            this.$store.dispatch('setPlaylist', this.defaultPlaylist);
        });
    },
};
</script>
