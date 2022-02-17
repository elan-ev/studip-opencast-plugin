<template>
    <div>
        <StudipDialog
            :title="$gettext('Mit diesem Kurs verknüpfte Opencast Serien')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                {{ servers }}
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { LtiService } from '@/common/lti.service';
import StudipDialog from '@studip/components/StudipDialog';

export default {
    name: 'SeriesManager',

    components: {
        StudipDialog
    },

    computed: {
        ...mapGetters(['servers'])
    },

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        }
    },

    mounted() {
        this.$store.dispatch('loadServers');
        //let lti =
    }
}
</script>
