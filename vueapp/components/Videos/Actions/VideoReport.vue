<template>
    <div>
        <StudipDialog
            :title="$gettext('Technisches Feedback')"
            :confirmText="$gettext('Einsenden')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="400"
            width="600"
            @close="decline"
            @confirm="report"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="message?.text" :type="message.type">
                    {{ message.text }}
                </MessageBox>
                <form class="default" @submit.prevent="report">
                    <fieldset>
                        <label>
                            {{ $gettext('Bitte beschreiben Sie das aufgetretene Problem') }}
                            <br>
                                {{ $gettext('z.B. Ton- oder Abspielprobleme etc.') }}
                            <textarea v-model="description" cols="20" rows="5"></textarea>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import MessageBox from "@/components/MessageBox";

export default {
    name: 'VideoReport',

    components: {
        StudipDialog,
        MessageBox
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            description: '',
            message: {}
        }
    },

    methods: {
        async report() {
            this.message = {};
            if (this.description != '') {
                let data = {
                    token: this.event.token,
                    description: this.description
                };
                await this.$store.dispatch('reportVideo', data)
                .then(({ data }) => {
                    this.$store.dispatch('addMessage', data.message);
                    this.$emit('done');
                }).catch(() => {
                    this.$emit('cancel');
                });
            } else {
                this.message.type = 'error';
                this.message.text = this.$gettext('Beschreibung darf nicht leer sein');
            }
        },

        decline() {
            this.$emit('cancel');
        },
    },
}
</script>