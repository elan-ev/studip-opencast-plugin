<template>
    <transition name="oc--episode">
        <li v-if="event.refresh === undefined" :key="event.id">
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
                        <!-- <p>No video uploaded</p> -->
                    </span>
                </div>
            </div>

            <div class="oc--metadata" :key="event.id">
                <div>
                    <h2 class="oc--metadata-title">
                        {{event.title}}
                    </h2>
                    <ul class="oc--metadata-content">
                        <li v-translate>
                            Hochgeladen am: {{event.mk_date * 1000 | datetime }} Uhr
                        </li>
                        <li v-translate>
                            Autor: {{event.author}}
                        </li>
                        <li v-translate>
                            Mitwirkende: Das sind Mitwirkende
                        </li>
                        <li v-translate>
                            Beschreibung: {{event.description}}
                        </li>
                        <li v-translate>
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

                    <opencast-button icon="trash" @click="showDeleteDialog = true" v-translate>
                        Entfernen
                    </opencast-button>

                    <EpisodeDeleteDialog v-if="showDeleteDialog"
                        @done="removeEpisode"
                        @cancel="showDeleteDialog = false"
                    />
                </div>
            </div>
        </li>
        <EmptyEpisodeCard v-else/>
    </transition>
</template>

<script>
import EmptyEpisodeCard from "@/components/Episodes/EmptyEpisodeCard"
import EpisodeDeleteDialog from '@/components/Episodes/EpisodeDeleteDialog'
import OpencastButton from '@/components/OpencastButton'


export default {
    name: "Episode",

    components: {
        OpencastButton,
        EpisodeDeleteDialog,
        EmptyEpisodeCard
    },

    props: {
        event: Object,
        index: Number
    },

    data() {
        return {
            showDeleteDialog: false,
            preview: PLUGIN_ASSET_URL + '/images/default-preview.png',
            play: PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },

    methods: {
        removeEpisode() {
            let view = this;
            this.$store.dispatch('removeEvent', this.event.id)
            .then(() => {
                view.showDeleteDialog = false;
            });
        },
    }
}
</script>
