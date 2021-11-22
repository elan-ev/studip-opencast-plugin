<template>
    <li>
        <div class="oc--flexitem oc--flexplaycontainer">
            <div class="oc--playercontainer">
                <a v-if="event.track_link" :href="event.track_link" target="_blank">
                    <span class="oc--previewimage">
                        <img class="oc--previewimage" :src="preview" height="200"/>
                        <img class="oc--playbutton" :src="play">
                    </span>
                </a>
                <span v-else class="oc--previewimage">
                    <img class="oc--previewimage" :src="preview" height="200"/>
                    <p>No video uploaded</p>
                </span>
            </div>
        </div>
        <div class="oc--metadata">
            <div>
                <h2 class="oc--metadata-title">
                    {{event.title}}
                </h2>
                <ul class="oc--metadata-content">
                    <li>
                        Hochgeladen am: {{event.mk_date}}
                    </li>
                    <li>
                        Autor: {{event.author}}
                    </li>
                    <li>
                        Mitwirkende: Das sind Mitwirkende
                    </li>
                    <li>
                        Beschreibung: {{event.description}}
                    </li>
                    <li>
                        Länge: {{event.length}}
                    </li>
                </ul>
            </div>
            <div class="oc--episode-buttons">
                <opencast-button icon="trash" @click="removeEpisode">Entfernen</opencast-button>
                <opencast-button icon="download">Download</opencast-button>
                <opencast-button icon="edit">Edit</opencast-button>
                <opencast-button v-if="event.annotation_tool" icon="edit" :href="event.annotation_tool">Annotation Tool</opencast-button>
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
            preview: PLUGIN_ASSET_URL + '/images/default-preview.png',
            play: PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },

    methods: {
        removeEpisode() {
            this.$store.dispatch('removeEvent', this.event.id)
        },
    }
}
</script>
