<template>
    <div>
        <VideoChangeWarning
            :event="event"
            :title="$gettext('Auswirkung des Schnitts im Editor')"
            @done="openEditor"
            @cancel="decline"
        />
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import VideoChangeWarning from '@/components/Videos/VideoChangeWarning';

export default {
    name: 'VideoCut',

    components: {
        StudipDialog,
        VideoChangeWarning
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    methods: {
        openEditor() {
            let redirectUrl = window.OpencastPlugin.REDIRECT_URL + '/perform/editor/' + this.event.token;
            window.open(redirectUrl, '_blank');
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        },
    },
}
</script>
