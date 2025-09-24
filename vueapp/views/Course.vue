<template>
    <div class="container" id="app-episodes">
        <MessageList />
        <router-view></router-view>
    </div>
</template>

<script>
import MessageList from '@/components/MessageList';
import { mapGetters } from 'vuex';

export default {
    name: 'Course',

    components: {
        MessageList,
    },

    computed: {
        ...mapGetters('opencast', ['cid']),
        ...mapGetters('config', ['course_config']),
    },

    mounted() {
        this.$store.dispatch('opencast/loadCurrentUser');
        this.$store.dispatch('config/loadCourseConfig', this.cid).then((course_config) => {
            if (!course_config?.series?.series_id) {
                this.$store.dispatch('messages/addMessage', {
                    type: 'warning',
                    text: this.$gettext(
                        'Die Kurskonfiguration konnte nicht vollständig abgerufen werden, daher ist das Hochladen von Videos momentan nicht möglich.'
                    ),
                });
            }
        });
    },
};
</script>
