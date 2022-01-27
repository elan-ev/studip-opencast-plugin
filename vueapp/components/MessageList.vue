<template>
    <div>
        <MessageBox v-if="messages" v-for="message in messages"
            :key="message.id"
            :type="message.type" @hide="removeMessage(message.id)">
                {{ message.text }}
        </MessageBox>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import MessageBox from '@/components/MessageBox';

export default {
    name: 'MessageList',

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
