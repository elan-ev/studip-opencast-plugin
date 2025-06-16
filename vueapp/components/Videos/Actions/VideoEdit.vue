<template>
    <div>
        <VideoChangeWarning
            v-if="!showEditDialog"
            :event="event"
            :title="$gettext('Auswirkung der Bearbeitung')"
            @done="showEditDialog = true"
            @cancel="decline"
        />

        <StudipDialog
            v-if="showEditDialog"
            :title="$gettext('Video bearbeiten')"
            :closeText="$gettext('Abbrechen')"
            :confirmText="$gettext('Speichern')"
            confirmClass="accept"
            :closeClass="'cancel'"
            height="640"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form v-if="currentEvent" class="default" @submit="editVideo" ref="videoEdit-form">
                    <label>
                        <span class="required">
                            {{ $gettext('Titel') }}
                        </span>

                        <input type="text" maxlength="255" name="title" v-model="currentEvent.title" required />
                    </label>

                    <label>
                        <span>
                            {{ $gettext('Vortragende(r)') }}
                        </span>
                        <input type="text" maxlength="255" name="presenter" v-model="currentEvent.presenters" />
                    </label>

                    <label>
                        <span>
                            {{ $gettext('Mitwirkende') }}
                        </span>
                        <input type="text" maxlength="255" name="contributor" v-model="currentEvent.contributors" />
                    </label>

                    <label>
                        <span>
                            {{ $gettext('Betreff') }}
                        </span>
                        <input type="text" maxlength="255" name="subject" v-model="currentEvent.subject" />
                    </label>

                    <label>
                        <span>
                            {{ $gettext('Beschreibung') }}
                        </span>
                        <textarea cols="50" rows="5" name="description" v-model="currentEvent.description"></textarea>
                    </label>

                    <label>
                        {{ $gettext('Schlagworte') }}
                        <TagBar :taggable="currentEvent" @update="updatedTags" />
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import StudipDialog from '@studip/StudipDialog';
import TagBar from '@/components/TagBar.vue';
import VideoChangeWarning from '@/components/Videos/VideoChangeWarning';

export default {
    name: 'VideoEdit',

    components: {
        StudipDialog,
        TagBar,
        VideoChangeWarning,
    },

    data() {
        return {
            showEditDialog: false,
            currentEvent: null
        };
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters(['cid', 'playlists', 'playlist']),

        isCourse() {
            return this?.cid ? true : false;
        },

        defaultVisibility() {
            return this.playlist['visibility'];
        },
    },

    methods: {
        async accept() {
            if (!this.$refs['videoEdit-form'].reportValidity()) {
                return false;
            }
            // Handle visibility
            this.currentEvent.cid = this.cid;
            this.currentEvent.playlist_token = this.playlist?.token;


            await this.$store
                .dispatch('updateVideo', this.currentEvent)
                .then(({ data }) => {
                    this.$store.dispatch('addMessage', data.message);
                    let emit_action = data.message.type == 'success' ? 'refresh' : '';
                    this.$emit('done', emit_action);
                })
                .catch(() => {
                    this.$emit('cancel');
                });
        },

        decline() {
            this.$emit('cancel');
        },

        updatedTags() {
            for (let i = 0; i < this.currentEvent.tags.length; i++) {
                if (typeof this.currentEvent.tags[i] !== 'object') {
                    // fix tag, because vue3-select seems to have an incosistent behaviour
                    this.currentEvent.tags[i] = {
                        tag: this.currentEvent.tags[i],
                    };
                }
            }
        },
    },

    mounted() {
        this.currentEvent = JSON.parse(JSON.stringify(this.event));
    },
};
</script>
