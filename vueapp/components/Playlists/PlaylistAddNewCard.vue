<template>
    <div>
        <StudipDialog
            :title="title"
            :confirmText="$gettext('Erstellen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="$emit('cancel')"
            @confirm="createPlaylist"
            @keyup.enter="createPlaylist"
        >
            <template v-slot:dialogContent>
                <form class="default" ref="playlistAddNewCard-form" @submit.prevent="createPlaylist">
                    <label
                        v-if="
                            simple_config_list &&
                            simple_config_list['server'] &&
                            simple_config_list['server'].length > 1
                        "
                    >
                        <span class="required"> {{ $gettext('Server') }}' </span>

                        <select v-model="selectedServer" required>
                            <option v-for="server in simple_config_list['server']" :key="server.id" :value="server">
                                #{{ server.id }} - {{ server.name }} (Opencast V {{ server.version }}.X)
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="required">{{ $gettext('Titel') }}</span>
                        <input type="text" ref="autofocus" maxlength="255" v-model="playlist.title" required  name="playlist-title"/>
                    </label>
                    <label>
                        {{ $gettext('Beschreibung') }}
                        <textarea v-model="playlist.description" name="playlist-description"></textarea>
                    </label>
                    <label>
                        {{ $gettext('Schlagworte') }}
                        <TagBar :taggable="playlist.tags" @update="updateTags" />
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import TagBar from '@/components/TagBar.vue';
import { mapGetters } from 'vuex';

export default {
    name: 'PlaylistAddNewCard',

    components: {
        StudipDialog,
        TagBar,
    },

    props: {
        isDefault: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters('config', ['simple_config_list']),
        ...mapGetters('opencast', ['currentUser', 'currentLTIUser']),

        title() {
            return this.$gettext('Wiedergabeliste erstellen');
        },
    },

    data() {
        return {
            selectedServer: false,
            playlist: {
                title: '',
                description: '',
                creator: '',
                config_id: null,
                visibility: 'internal',
                is_default: false,
                tags: []
            },
        };
    },

    methods: {
        createPlaylist() {
            if (!this.$refs['playlistAddNewCard-form'].reportValidity()) {
                return false;
            }

            this.playlist.config_id = this.selectedServer.id;
            this.playlist.is_default = this.isDefault;
            this.playlist.creator = this.currentUser.fullname;

            this.$store
                .dispatch('playlists/addPlaylist', this.playlist)
                .then(() => {
                    this.$emit('done');
                })
                .catch(() => {
                    this.$store.dispatch('messages/addMessage', {
                        type: 'error',
                        text: this.$gettext('Die Wiedergabeliste konnte nicht erstellt werden.'),
                    });
                    this.$emit('cancel');
                });
        },
    },

    mounted() {
        this.$store.dispatch('config/simpleConfigListRead').then(() => {
            this.selectedServer =
                this.simple_config_list['server'][this.simple_config_list.settings['OPENCAST_DEFAULT_SERVER']];
        });

        this.$refs.autofocus.focus();
    },
};
</script>
