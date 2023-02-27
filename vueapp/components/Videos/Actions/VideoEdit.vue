<template>
    <div>
        <StudipDialog
            :title="$gettext('Episode bearbeiten')"
            :closeText="$gettext('Abbrechen')"
            :confirmText="$gettext('Speichern')"
            confirmClass="accept"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default" style="max-width: 50em;" @submit="editVideo">
                    <fieldset>
                        <label>
                            <span class="required" v-translate>
                                Titel
                            </span>

                            <input type="text" maxlength="255"
                                name="title" v-model="event.title" required>
                        </label>

                        <label>
                            <span v-translate>
                                Mitwirkende
                            </span>
                            <input type="text" maxlength="255" name="contributor" v-model="event.contributors">
                        </label>

                        <label>
                            <span v-translate>
                                Thema
                            </span>
                            <input type="text" maxlength="255" name="subject" v-model="event.subject">
                        </label>

                        <label>
                            <span v-translate>
                                Beschreibung
                            </span>
                            <textarea cols="50" rows="5" name="description" v-model="event.description"></textarea>
                        </label>

                        <label>
                            Tags
                            <TagBar :taggable="event" @update="updatedTags"/>
                        </label>

                        <label v-if="isCourse">
                            <div>
                                <span v-translate>
                                    Sichtbarkeit des Videos
                                </span>
                            </div>

                            <section class="hgroup size-m">
                                <label>
                                    <input type="radio" value="2"
                                        :checked="visibility == 'default'"
                                        @change="visibility = 'default'"
                                    >
                                    <translate>
                                        Standard
                                    </translate>
                                </label>

                                <label>
                                    <input type="radio" value="1"
                                        :checked="visibility == 'visible'"
                                        @change="visibility = 'visible'"
                                    >
                                    <translate>
                                        Sichtbar
                                    </translate>
                                </label>

                                <label>
                                    <input type="radio" value="0"
                                        :checked="visibility == 'hidden'"
                                        @change="visibility = 'hidden'"
                                    >
                                    <translate>
                                        Unsichtbar
                                    </translate>
                                </label>
                            </section>
                        </label>

                        <label v-if="showUseTimestamp">
                            <div>
                                <span v-translate>
                                    Soll dieses Video über einen Zeitstempel Sichtbar geschaltet werden können?
                                </span>
                            </div>

                            <section class="hgroup size-s">
                                <label>
                                    <input type="radio" value="1"
                                        :checked="use_timestamp"
                                        @change="use_timestamp = true"
                                    >
                                    <translate>
                                        Ja
                                    </translate>
                                </label>

                                <label>
                                    <input type="radio" value="0"
                                        :checked="!use_timestamp"
                                        @change="use_timestamp = false"
                                    >
                                    <translate>
                                        Nein
                                    </translate>
                                </label>
                            </section>
                        </label>

                        <label v-if="showUseTimestamp && use_timestamp">
                            <span v-translate>
                                Zeitstempel für die Sichtbarkeit
                            </span>
                            <input class="oc--datetime-input" type="datetime-local" name="visibilityDate" id="visibilityDate" v-model="visible_timestamp">
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog';
import TagBar from '@/components/TagBar.vue';

export default {
    name: "VideoEdit",

    components: {
        StudipDialog, TagBar
    },

    data() {
        return {
            visibility: null,
            visible_timestamp: null,
            use_timestamp: false
        }
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters([
            "cid", 
            "playlists", "currentPlaylist"
        ]),

        isCourse() {
            return this?.cid ? true : false;
        },

        defaultVisibility() {
            return this.playlists.find(p => p['token'] === this.currentPlaylist)['visibility'];
        },

        showUseTimestamp() {
            return this.isCourse && (this.visibility == 'hidden' || this.visibility == 'default' && this.defaultVisibility == 'hidden');
        }
    },

    methods: {
        async accept() {
            // Handle visibility
            this.event.from_cid = this.cid;
            this.event.from_playlist = this.currentPlaylist;
            if (this.visibility === "default") {
                this.event.playlist_seminar = undefined;
            }
            else {
                if (this.event.playlist_seminar === undefined) {
                    this.event.playlist_seminar = {};
                }
                this.event.playlist_seminar.visibility = this.visibility;
                this.event.playlist_seminar.visible_timestamp = null;
                if (this.visibility !== 'visible') {
                    if (this.use_timestamp && this.visible_timestamp !== undefined) {
                        this.event.playlist_seminar.visible_timestamp = this.visible_timestamp;
                    }
                }
            }

            await this.$store.dispatch('updateVideo', this.event)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                let emit_action = data.message.type == 'success' ? 'refresh' : '';
                this.$emit('done', emit_action);
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        },

        updatedTags() {
            for (let i = 0; i < this.event.tags.length; i++) {
                if (typeof this.event.tags[i] !== 'object') {
                    // fix tag, because vue-select seems to have an incosistent behaviour
                    this.event.tags[i] = {
                        tag:  this.event.tags[i]
                    }
                }
            }
        }
    },

    mounted() {
        // Initialize visibility
        this.visibility = "default";
        if (this.event.playlist_seminar !== undefined) {
            if (this.event.playlist_seminar.visibility !== null) {
                this.visibility = this.event.playlist_seminar.visibility;
            }
            if (this.event.playlist_seminar.visibility === 'hidden' &&
                     this.event.playlist_seminar.visible_timestamp !== null) {
                this.visible_timestamp = this.event.playlist_seminar.visible_timestamp;
                this.use_timestamp = true;
            }
        }
    }
}
</script>