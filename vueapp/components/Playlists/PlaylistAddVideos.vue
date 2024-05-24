<template>
    <div>
        <StudipDialog v-if="activeDialog === null"
            :title="$gettext('Videos hinzufügen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="350"
            width="500"
            @close="cancel"
        >
            <template v-slot:dialogContent ref="add-dialog">
                <h2>{{ $gettext('Quelle auswählen') }}</h2>
                <!-- if upload is allowed, adding videos to existing playlists is allowed from all contexts -->
                <div class="oc--dialog-possibilities">
                    <a v-if="canUpload" href="#" @click.prevent="activeDialog = 'upload'">
                        <studip-icon shape="computer" role="clickable" size="50"/>
                        {{ $gettext('Mein Computer') }}
                    </a>
                    <a v-if="canUpload" href="#" @click.prevent="activeDialog = 'contents'">
                        <studip-icon :shape="opencastImage" role="clickable" size="50"/>
                        {{ $gettext('Arbeitsplatz') }}
                    </a>
                    <a v-if="canUpload" href="#" @click.prevent="activeDialog = 'courses'">
                        <studip-icon shape="seminar" role="clickable" size="50"/>
                        {{ $gettext('Meine Veranstaltungen') }}
                    </a>
                </div>
            </template>
        </StudipDialog>


        <VideoUpload v-if="activeDialog === 'upload'"
             @done="uploadDone"
             @cancel="cancel"
             :currentUser="currentUser"
        />

        <VideosAddFromContents v-if="activeDialog === 'contents'"
             @done="done"
             @cancel="cancel"
        />

        <VideosAddFromCourses v-if="activeDialog === 'courses'"
            @done="done"
            @cancel="cancel"
        />
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog'
import VideoUpload from "@/components/Videos/VideoUpload";
import VideosAddFromContents from "@/components/Videos/VideosAddFromContents";
import VideosAddFromCourses from "@/components/Videos/VideosAddFromCourses";
import StudipIcon from "@studip/StudipIcon";

export default {
    name: 'PlaylistAddVideos',

    components: {
        StudipIcon,
        VideoUpload,
        VideosAddFromContents,
        VideosAddFromCourses,
        StudipDialog,
    },

    props: {
        canEdit: {
            type: Boolean,
            default: true,
        },
        canUpload: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['done', 'cancel'],

    data () {
        return {
            activeDialog: null,
            opencastImage: window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/opencast-courseware.svg',
        }
    },

    computed: {
        ...mapGetters([
            'currentUser',
        ]),
    },

    methods: {
        uploadDone() {
            this.$store.dispatch('addMessage', {
                type: 'info',
                text: this.$gettext('Ihr Video wird nun verarbeitet. Sie erhalten eine Benachrichtigung, sobald die Verarbeitung abgeschlossen ist.')
            });
            this.activeDialog = null;
            this.$emit('done');
        },

        done() {
            this.activeDialog = null;
            this.$emit('done');
        },

        cancel() {
            this.activeDialog = null;
            this.$emit('cancel');
        },
    },

    mounted() {

    }
}
</script>
