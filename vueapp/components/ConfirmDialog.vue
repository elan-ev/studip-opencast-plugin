<template>
    <div>
        <StudipDialog
            :title="title"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <p>{{ message }}</p>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'

export default {
    name: 'ConfirmDialog',

    components: {
        StudipDialog
    },

    props: ['title','message'],

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        }
    }
}
</script>
