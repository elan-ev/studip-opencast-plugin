<template>
    <div>
        <StudipDialog
            :title="$gettext('Wiedergabeliste anlegen')"
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

    data() {
        return {
            playlist: {
                title: '',
                visibility: 'internal'
            }
        }
    },

    methods: {
        createPlaylist() {
            if (!this.$refs['playlistAddNewCard-form'].reportValidity()) {
                return false;
            }
            this.$store.dispatch('addPlaylist', this.playlist);
            this.$emit('done');
        }
    }
}
</script>