<template>
    <form class="default" @submit.prevent="report">
        <MessageBox v-if="message?.text" :type="message.type">
            {{ message.text }}
        </MessageBox>
        <p>
            {{ $gettext('Wenn bei der Nutzung technische Probleme auftreten, können Sie uns diese hier melden.')}} <br>
            {{ $gettext('Bitte beschreiben Sie möglichst genau, was passiert ist – z.B. bei Ton, Video oder der Bedienung.')}}
        </p>
        <label>
            {{ $gettext('Problembeschreibung') }}
            <textarea v-model="description" cols="20" rows="5"></textarea>
        </label>
        <small>
            {{
                $gettext(
                    'Je genauer Ihre Beschreibung ist, desto besser können wir das Problem nachvollziehen und beheben.'
                )
            }}
        </small>
        <div class="oc--tab-footer">
            <button class="button" type="submit">{{ $gettext('Problem melden') }}</button>
        </div>
    </form>
</template>

<script>
import MessageBox from '@/components/MessageBox';

export default {
    name: 'VideoReport',

    components: {
        MessageBox,
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            description: '',
            message: {},
        };
    },

    methods: {
        async report() {
            this.message = {};
            if (this.description != '') {
                let data = {
                    token: this.event.token,
                    description: this.description,
                };
                await this.$store
                    .dispatch('reportVideo', data)
                    .then(({ data }) => {
                        this.$store.dispatch('addMessage', data.message);
                        this.$emit('done');
                    })
                    .catch(() => {
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
};
</script>
