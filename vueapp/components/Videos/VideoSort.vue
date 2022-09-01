<template>
    <div>
        <ConfirmDialog v-if="showConfirmDialog"
            :title="$gettext('Sortieren abbrechen')"
            :message="$gettext('Sind sie sicher, dass sie das Sortieren abbrechen möchten?')"
            @done="decline"
            @cancel="showConfirmDialog = false"
        />
        <StudipDialog v-else
            :title="$gettext('Episoden sortieren')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="900"
            width="1200"
            @close="showConfirmDialog=true"
            @confirm="accept"
        >
            <template v-slot:dialogContent ref="upload-dialog">
                <VideosList/>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageBox from '@/components/MessageBox'
import ConfirmDialog from '@/components/ConfirmDialog'
import VideosList from "@/components/Videos/VideosList"

export default {
    name: 'VideoSort',

    components: {
        StudipDialog,
        MessageBox,
        StudipButton,
        ConfirmDialog,
        VideosList
    },

    emits: ['done', 'cancel'],

    data () {
        return {
            showConfirmDialog: false
        }
    },

    computed: {
        ...mapGetters({
            'config' : 'simple_config_list',
        }),
    },

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        },
    },

    mounted() {
        this.$store.dispatch('setVideoSortMode', true)
        this.$store.dispatch('loadVideos')
    }
}
</script>
