<template>
    <div name="oc--episode">
        <li v-if="event.refresh === undefined" :key="event.id">
            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="event.publication && event.preview" :href="event.paella" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage" :src="event.preview.player ? event.preview.player : event.preview.search" height="200"/>
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
                        <li>
                            {{ $gettext('Hochgeladen am:') }}
                            <span v-if="event.created && $filters.datetime(event.created)">
                                {{ $filters.datetime(event.created) }} Uhr
                            </span>
                            <span v-else>
                                {{ $gettext('unbekannt') }}
                            </span>
                        </li>

                        <li>
                            {{ $gettext('Autor:') }}
                            {{ event.author }}
                        </li>
                        <li>
                            {{ $gettext('Mitwirkende:') }}
                            {{ event.contributors }}
                        </li>

                        <li>
                            {{ event.description }}
                        </li>
                    </ul>
                </div>
                <div class="oc--episode-buttons">
                    <ConfirmDialog v-if="DeleteConfirmDialog"
                        :title="$gettext('Aufzeichnung entfernen')"
                        :message="$gettext('Möchten Sie die Aufzeichnung wirklich entfernen?')"
                        @done="removeVideo"
                        @cancel="DeleteConfirmDialog = false"
                    />
                </div>
            </div>
        </li>
        <EmptyVideoCard v-else/>
    </div>
</template>

<script>
import EmptyVideoCard from "@/components/Videos/EmptyVideoCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'


export default {
    name: "VideoCard",

    components: {
        StudipButton, ConfirmDialog,
        EmptyVideoCard,
    },

    props: {
        event: Object
    },

    data() {
        return {
            DeleteConfirmDialog: false,
            DownloadDialog: false,
            editDialog: false,
            preview:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },

    methods: {
        removeVideo() {
            let view = this;
            this.$store.dispatch('deleteVideo', this.event.id)
            .then(() => {
                view.DeleteConfirmDialog = false;
            });
        },
    },

    computed: {
        getDuration() {
            var sec = parseInt(this.event.duration / 1000)
            var min = parseInt(sec / 60)
            var h = parseInt(min / 60)
            return ("0" + h).substr(-2) + ":" + ("0" + min%60).substr(-2) + ":" + ("0" + sec%60).substr(-2)
        }
    }
}
</script>
