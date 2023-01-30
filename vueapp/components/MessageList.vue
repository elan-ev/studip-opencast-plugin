<template>
    <div :class="{
            'oc--messages-float': float
        }">
        <template v-if="messages.length">
            <MessageBox v-for="message in messages"
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

    props: ['float'],

    components: {
        MessageBox
    },

    computed: {
        ...mapGetters(['messages'])
    },

    methods: {
        removeMessage(id) {
            this.$store.commit('removeMessage', id);
        }
    }
}
</script>
