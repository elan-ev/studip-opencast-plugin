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
                <form class="default" ref="playlistAddNewCard-form">
                    <label>
                        <span class="required">Titel</span>
                        <input type="text"
                                maxlength="255"
                                :placeholder="$gettext('Titel der Wiedergabeliste')"
                                v-model="playlist.title"
                                required
                        >
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipButton from "@studip/StudipButton";
import StudipDialog from '@studip/StudipDialog'

export default {
    name: "PlaylistAddNewCard",

    components: {
        StudipButton,
        StudipDialog
    },

    props: {
        isDefault: {
            type: Boolean,
            default: false
        },
    },

    computed: {
        title() {
            return this.isDefault ? this.$gettext('Kurswiedergabeliste anlegen') : this.$gettext('Wiedergabeliste anlegen');
        }
    },

    data() {
        return {
            playlist: {
                title: '',
                visibility: 'internal',
                is_default: false
            }
        }
    },

    methods: {
        createPlaylist() {
            if (!this.$refs['playlistAddNewCard-form'].reportValidity()) {
                return false;
            }

            this.playlist.is_default = this.isDefault;

            this.$store.dispatch('addPlaylist', this.playlist);
            this.$emit('done');
        }
    }
}
</script>
