<template>
    <div class="oc--visibility-options">
        <VideoChangeWarning
            :event="event"
            :title="$gettext('Auswirkung der Bearbeitung')"
        />
        <form class="default" @submit="editVideo">
            <fieldset>
                <legend>
                    {{ $gettext('Sichtbarkeit des Videos in dieser Veranstaltung') }}
                </legend>
                <section class="hgroup size-m">
                    <label>
                        <input
                            type="radio"
                            value="2"
                            :checked="visibility == 'default'"
                            @change="changeVisibility('default')"
                        />
                        {{ $gettext('Standard') }}
                    </label>

                    <label>
                        <input
                            type="radio"
                            value="1"
                            :checked="visibility == 'visible'"
                            @change="changeVisibility('visible')"
                        />
                        {{ $gettext('Sichtbar') }}
                    </label>

                    <label>
                        <input
                            type="radio"
                            value="0"
                            :checked="visibility == 'hidden'"
                            @change="changeVisibility('hidden')"
                        />
                        {{ $gettext('Unsichtbar') }}
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <legend>
                    {{ $gettext('Sichtbarkeitsdatum') }}
                </legend>
                <label>
                    {{ $gettext('sichtbar ab') }}
                    <div class="oc--timestamp-input">
                        <input
                            class="oc--datetime-input"
                            type="datetime-local"
                            name="visibilityDate"
                            id="visibilityDate"
                            v-model="visible_timestamp"
                            @change="checkVisibility"
                        />
                        <button class="oc--trash-button" type="button" @click="visible_timestamp = ''">
                            <studip-icon shape="trash" role="clickable" />
                        </button>
                    </div>
                </label>
            </fieldset>
        </form>
        <div class="oc--tab-footer">
            <button class="button" @click="updateVisibility()">
                {{ $gettext('Übernehmen') }}
            </button>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import VideoChangeWarning from '@/components/Videos/VideoChangeWarning';
import StudipIcon from '@/components/Studip/StudipIcon'

export default {
    name: 'VideoVisibility',
    components: {
        VideoChangeWarning,
        StudipIcon
    },
    props: ['event'],
    data() {
        return {
            visibility: null,
            visible_timestamp: '',
        };
    },
    computed: {
        ...mapGetters(['cid', 'playlists', 'playlist']),

        defaultVisibility() {
            return this.playlist['visibility'];
        },
    },
    methods: {
        async updateVisibility() {
            const event = JSON.parse(JSON.stringify(this.event));
            // Handle visibility
            event.cid = this.cid;
            event.playlist_token = this.playlist?.token;
            this.checkVisibility();
            if (this.visibility === 'default') {
                event.seminar_visibility = null;
            } else {
                event.seminar_visibility = {
                    visibility: this.visibility,
                    visible_timestamp: this.visible_timestamp,
                };
            }

            await this.$store
                .dispatch('updateVideo', event)
                .then(({ data }) => {
                    this.$store.dispatch('addMessage', data.message);
                    console.log('visibility updated'); //TODO !!!
                })
                .catch(() => {
                    // this.$emit('cancel');
                });
        },
        checkVisibility() {
            if (this.visible_timestamp) {
                if (Date.parse(this.visible_timestamp) < Date.now()) {
                    this.visibility = 'visible';
                } else {
                    this.visibility = 'hidden';
                }
            } else if (!this.visibility) {
                this.visibility = 'default';
            }
        },
        changeVisibility(visibility) {
            this.visibility = visibility;
            if (this.visible_timestamp) {
                if (
                    visibility === 'default' ||
                    (visibility === 'hidden' && Date.parse(this.visible_timestamp) < Date.now()) ||
                    (visibility === 'visible' && Date.parse(this.visible_timestamp) >= Date.now())
                ) {
                    this.visible_timestamp = '';
                }
            }
        },
    },
    mounted() {
        this.visibility = this.event.seminar_visibility?.visibility;
        this.visible_timestamp = this.event.seminar_visibility?.visible_timestamp;
        this.checkVisibility();
    },
};
</script>
