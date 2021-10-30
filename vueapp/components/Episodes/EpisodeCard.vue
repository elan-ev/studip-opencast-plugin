<template>
    <li>
        <div class="oc_flexitem oc_flexplaycontainer">
            <div class="oce_playercontainer">
                <span class="previewimage">
                    <img class="previewimage" :src="preview" height="200"/>
                </span>
            </div>
        </div>
        <div class="oce_metadatacontainer">
            <div>
                <h2 class="oce_metadata oce_list_title">
                    {{event.title}}
                </h2>
                <ul class="oce_contentlist">
                    <li class="oce_list_date">
                        Hochgeladen am: Das ist ein Datum
                    </li>
                    <li>
                        Autor: {{event.lecturer}}
                    </li>
                    <li>
                        Mitwirkende: Das sind Mitwirkende
                    </li>
                    <li>
                        Beschreibung: Das ist eine Beschreibung
                    </li>
                </ul>
            </div>
            <div class="ocplayerlink">
                <opencast-button icon="trash">Entfernen</opencast-button>
                <opencast-button icon="download">Download</opencast-button>
                <opencast-button icon="edit">Edit</opencast-button>
            </div>
        </div>
    </li>
</template>

<script>
import gpl from "graphql-tag"
import OpencastButton from '../OpencastButton.vue'

export default {
    name: "Episode",

    components: {
        OpencastButton
    },

    props: {
        event: Object,
        index: Number
    },

    data() {
        return {
            preview: PLUGIN_ASSET_URL + '/images/default-preview.png'
        }
    },

    methods: {
        removeEpisode() {
            this.$apollo.mutate({
                mutation: gpl` mutation ($id: ID!) {
                    removeEvent(id: $id) {
                        id
                        title
                        lecturer
                    }
                }`,
                variables: {
                    id: this.event.id
                },
            })
        },
    }
}
</script>