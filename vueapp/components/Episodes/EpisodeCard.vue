<template>
    <transition name="oc--episode">
        <li v-if="event.refresh === undefined" :key="event.id">
            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="event.track_link" :href="event.track_link" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage" :src="preview" height="200"/>
                            <img class="oc--playbutton" :src="play">
                            <span class="oc--duration">
                                {{ getDuration }}
                            </span>
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
                            Mitwirkende: {{event.contributor[0]}}
                        </li>
                        <li v-translate>
                            Beschreibung: {{event.description}}
                        </li>
                    </ul>
                </div>
                <div class="oc--episode-buttons">
                    <opencast-button icon="download" @click="showDownloadDialog=true" v-translate>
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

                    <opencast-button icon="trash" @click="showConfirmDialog = true" v-translate>
                        Entfernen
                    </opencast-button>

                    <ConfirmDialog v-if="showConfirmDialog"
                        :title="$gettext('Aufzeichnung entfernen')"
                        :message="$gettext('Möchten Sie die Aufzeichnung wirklich entfernen?')"
                        @done="removeEpisode"
                        @cancel="showConfirmDialog = false"
                    />
                    <DownloadDialog :downloads="event.downloads" v-if="showDownloadDialog"
                        @cancel="showDownloadDialog = false"/>
                </div>
            </div>
        </li>
        <EmptyEpisodeCard v-else/>
    </transition>
</template>

<script>
import EmptyEpisodeCard from "@/components/Episodes/EmptyEpisodeCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import OpencastButton from '@/components/OpencastButton'
import DownloadDialog from '@/components/Episodes/DownloadDialog'


export default {
    name: "Episode",

    components: {
        OpencastButton, ConfirmDialog,
        EmptyEpisodeCard, DownloadDialog
    },

    props: {
        event: Object,
        index: Number
    },

    data() {
        return {
            showConfirmDialog: false,
            showDownloadDialog: false,
            preview: PLUGIN_ASSET_URL + '/images/default-preview.png',
            play: PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },

    methods: {
        removeEpisode() {
            let view = this;
            this.$store.dispatch('removeEvent', this.event.id)
            .then(() => {
                view.showConfirmDialog = false;
            });
        },
    },

    computed: {
        getDuration() {
            var sec = parseInt(this.event.length / 1000)
            var min = parseInt(sec / 60)
            var h = parseInt(min / 60)
            return ("0" + h).substr(-2) + ":" + ("0" + min%60).substr(-2) + ":" + ("0" + sec%60).substr(-2)
        }
    }
}
</script>
