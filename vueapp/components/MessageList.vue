<template>
    <div :class="{
            'oc--messages-float': float
        }">
        <template v-if="currentMessages.length">
            <MessageBox v-for="message in currentMessages"
                :key="message.id"
                :type="message.type" @hide="removeMessage(message.id)">
                    {{ message.text }}
            </MessageBox>
        </template>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import MessageBox from '@/components/MessageBox';

export default {
    name: 'MessageList',

    props: {
        float: {
            type: Boolean,
            default: false
        },
        dialog: {
            type: Boolean,
            default: false
        }
    },

    components: {
        MessageBox
    },

    computed: {
        ...mapGetters('messages', ['messages']),

        currentMessages() {
            if (this.dialog) {
                return this.messages.filter(m => m.dialog === true || m.global === true);
            }
            else {
                return this.messages.filter(m => m.dialog === false || m.dialog === undefined || m.global === true);
            }
        }
    },

    methods: {
        removeMessage(id) {
            this.$store.commit('messages/removeMessage', id);
        }
    },

    unmounted() {
        if (this.dialog) {
            this.$store.dispatch('messages/clearMessages', this.dialog);
        }
    }
}
</script>
