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
                <form class="default" ref="playlistAddCard-form">
                    <label>
                        <span class="required">Titel</span>
                        <input type="text"
                            maxlength="255"
                            :placeholder="$gettext('Titel der Wiedergabeliste')"
                            v-model="playlist.title"
                            required
                        >
                    </label>

                    <!--
                    <label>
                        Sichtbarkeit
                        <select class="size-s" v-model="playlist.visibility">
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
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipButton from "@studip/StudipButton";
import StudipDialog from '@studip/StudipDialog'

export default {
    name: "PlaylistAddCard",

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
            if (!this.$refs['playlistAddCard-form'].reportValidity()) {
                return false;
            }
            this.$emit('done', this.playlist);
        }
    }
}
</script>