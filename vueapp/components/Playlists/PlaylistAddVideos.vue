<template>
    <div>
        <StudipDialog v-if="activeDialog === null"
            :title="$gettext('Videos hinzufügen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="350"
            width="400"
            @close="cancel"
        >
            <template v-slot:dialogContent ref="add-dialog">
                <h2>{{ $gettext('Quelle auswählen') }}</h2>
                <div class="oc--videos-add-possibilities">
                    <a v-if="canUpload" href="#" @click.prevent="activeDialog = 'upload'">
                        <studip-icon shape="computer" role="clickable" size="50"/>
                        {{ $gettext('Mein Computer') }}
                    </a>
                    <a v-if="canEdit" href="#" @click.prevent="activeDialog = 'contents'">
                        <studip-icon :shape="opencastImage" role="clickable" size="50"/>
                        {{ $gettext('Arbeitsplatz') }}
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
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog'
import VideoUpload from "@/components/Videos/VideoUpload";
import VideosAddFromContents from "@/components/Videos/VideosAddFromContents";
import StudipIcon from "@studip/StudipIcon";

export default {
    name: 'PlaylistAddVideos',

    components: {
        StudipIcon,
        VideoUpload,
        VideosAddFromContents,
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
