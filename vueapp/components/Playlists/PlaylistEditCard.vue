<template>
    <div>
        <StudipDialog
            :title="$gettext('Wiedergabeliste bearbeiten')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="$emit('cancel')"
            @confirm="updatePlaylist"
        >
            <template v-slot:dialogContent>
                <form class="default" ref="playlistEditCard-form">
                    <label v-if="!isDefaultCoursePlaylist">
                        <span class="required">{{ $gettext('Titel') }}</span>
                        <input type="text" v-model="eplaylist.title" required>
                    </label>

                    <!--
                    <label>
                        Sichtbarkeit
                        <select v-model="eplaylist.visibility">
                            <option value="internal">
                                {{ $gettext('Intern') }}
                            </option>
                            <option value="free">
                                {{ $gettext('Nicht gelistet') }}
                            </option>
                            <option value="public">
                                {{ $gettext('Öffentlich') }}
                            </option>
                        </select>
                    </label>
                    -->

                    <label>
                        {{ $gettext('Schlagworte') }}
                        <TagBar :taggable="eplaylist" @update="updateTags" />
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'

import TagBar from '@/components/TagBar.vue';

import { mapGetters } from "vuex";

export default {
    name: "PlaylistEditCard",

    components: {
        TagBar,
        StudipDialog
    },

    data() {
        return {
            eplaylist: {},
        }
    },

    computed: {
        ...mapGetters([
            'playlist', 'cid',
        ]),

        isDefaultCoursePlaylist() {
            return this.cid && this.playlist.is_default === '1';
        },
    },

    methods: {
        updatePlaylist() {
            if (!this.$refs['playlistEditCard-form'].reportValidity()) {
                return false;
            }
            this.playlist.title      = this.eplaylist.title;
            this.playlist.visibility = this.eplaylist.visibility;
            this.playlist.tags       = JSON.parse(JSON.stringify((this.eplaylist.tags)));

            this.$store.dispatch('updatePlaylist', this.playlist).then(() => {
                this.$store.dispatch('updateAvailableTags', this.playlist);
            });

            this.$emit('done', this.playlist);
        },

        updateTags() {
            for (let i = 0; i < this.eplaylist.tags.length; i++) {
                if (!this.eplaylist.tags[i].tag) {
                    this.eplaylist.tags[i] = {
                        tag: this.eplaylist.tags[i]
                    }
                }
            }
        },
    },

    mounted() {
        this.eplaylist.title      = this.playlist.title;
        this.eplaylist.visibility = this.playlist.visibility;
        this.eplaylist.tags       = JSON.parse(JSON.stringify(this.playlist.tags));
    }
}
</script>
