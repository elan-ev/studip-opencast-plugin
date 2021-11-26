<template>
    <transition name="oc--episode">
        <li :key="event.id">
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

            <div class="oc--metadata" :key="event.id">
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
                    <opencast-button icon="download" v-translate>
                        Download
                    </opencast-button>

                    <opencast-button icon="edit" v-translate>
                        Bearbeiten
                    </opencast-button>

                    <opencast-button v-if="event.annotation_tool"
                        icon="edit" :href="event.annotation_tool"
                        v-translate
                    >
                        Annotationen
                    </opencast-button>

                    <opencast-button icon="trash" @click="removeEpisode" v-translate>
                        Entfernen
                    </opencast-button>
                </div>
            </div>
        </li>
    </transition>
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
