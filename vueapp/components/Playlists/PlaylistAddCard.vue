<template>
    <div>
        <StudipDialog v-if="activeDialog === null"
            :title="$gettext('Wiedergabeliste hinzufügen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="350"
            @close="$emit('cancel')"
        >
            <template v-slot:dialogContent ref="add-dialog">
                <h2>{{ $gettext('Aktionen') }}</h2>
                <div class="oc--dialog-possibilities">
                    <a href="#" @click.prevent="activeDialog = 'new'">
                        <studip-icon shape="add" role="clickable" size="50"/>
                        {{ $gettext('Neu erstellen') }}
                    </a>

                    <a href="#" @click.prevent="activeDialog = 'copy'">
                        <studip-icon shape="copy" role="clickable" size="50"/>
                        {{ $gettext('Bestehende kopieren') }}
                    </a>

                    <!--
                    <a href="#" @click.prevent="activeDialog = 'link'">
                        <studip-icon shape="group" role="clickable" size="50"/>
                        {{ $gettext('Bestehende verknüpfen') }}
                    </a>
                    -->
                </div>
            </template>
        </StudipDialog>

        <PlaylistAddNewCard v-if="activeDialog === 'new'"
            :is-default="isDefault"
            @done="done"
            @cancel="cancel"
        />

        <PlaylistsCopyCard v-if="activeDialog === 'copy'"
            :is-default="isDefault"
            @done="done"
            @cancel="cancel"
        />

        <PlaylistsLinkCard v-if="activeDialog === 'link'"
            :is-default="isDefault"
            @done="done"
            @cancel="cancel"
        />

    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import StudipIcon from "@studip/StudipIcon";
import PlaylistAddNewCard from "@/components/Playlists/PlaylistAddNewCard";
import PlaylistsCopyCard from "@/components/Playlists/PlaylistsCopyCard";
import PlaylistsLinkCard from "@/components/Playlists/PlaylistsLinkCard";

export default {
    name: "PlaylistAddCard",

    components: {
        StudipDialog,
        StudipIcon,
        PlaylistAddNewCard,
        PlaylistsCopyCard,
        PlaylistsLinkCard,
    },

    props: {
        isDefault: {
            type: Boolean,
            default: false
        },
    },

    emits: ['done', 'cancel'],

    data() {
        return {
            activeDialog: null,
        }
    },

    methods: {
        done() {
            this.activeDialog = null;
            this.$emit('done');
        },

        cancel() {
            this.activeDialog = null;
            this.$emit('cancel');
        },
    }
}
</script>
