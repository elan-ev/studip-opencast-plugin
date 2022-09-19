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
        >
            <template v-slot:dialogContent>
                <form class="default">
                    <label>
                        <span class="required">Titel</span>
                        <input type="text"
                            :placeholder="$gettext('Titel der Wiedergabeliste')"
                            v-model="playlist.title"
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
            this.$emit('done', this.playlist);
        }
    }
}
</script>