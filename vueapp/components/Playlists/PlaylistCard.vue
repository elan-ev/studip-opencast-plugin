<template>
    <div name="oc--episode">
        <li v-if="playlist.refresh === undefined" :key="playlist.id">
            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="playlist.publication" :href="playlist.publication" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage" :src="preview" height="200"/>
                            <img class="oc--playbutton" :src="play">
                            <span class="oc--duration">
                                {{ getDuration }}
                            </span>
                        </span>
                    </a>
                    
                    <span v-else class="oc--previewimage">
                        <img class="oc--previewimage" :src="preview" height="200" v-on:click="listVideos()"/>
                        <!-- <p>No video uploaded</p> -->
                    </span>

                </div>
            </div>

            <div class="oc--metadata" :key="playlist.id">
                <div>
                    <h2 class="oc--metadata-title">
                        {{playlist.title}}
                    </h2>
                    <ul class="oc--metadata-content">
                        <li>
                            {{ $gettext('Hochgeladen am:') }}
                            <span v-if="playlist.mkdate">
                            {{ $filters.datetime(playlist.mkdate * 1000) }} Uhr
                            </span>
                            <span v-else>
                                {{ $gettext('unbekannt') }}
                            </span>
                        </li>
                        <!-- <li v-translate>
                            {{ $gettext('Autor:') }}
                            {{ playlist.autor }}
                        </li>
                        <li v-translate>
                            {{ $gettext('Mitwirkende:') }}
                            {{ playlist.contributors }}
                        </li> -->
                        <li v-translate>
                            {{ $gettext('Beschreibung:') }}
                            {{ playlist.description }}
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
        <EmptyPlaylistCard v-else/>
    </div>
</template>

<script>
import EmptyPlaylistCard from "@/components/Playlists/EmptyPlaylistCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'


export default {
    name: "PlaylistCard",

    components: {
        StudipButton, ConfirmDialog,
        EmptyPlaylistCard,
    },

    props: {
        playlist: Object
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
            this.$store.dispatch('deleteVideo', this.playlist.id)
            .then(() => {
                view.DeleteConfirmDialog = false;
            });
        },

        listVideos() {
            this.$store.dispatch('setCurrentPlaylist', this.playlist.token);
            this.$store.dispatch('setPage', 0);
            window.scrollTo(0,0);
            this.$store.dispatch('loadVideos');
            this.$router.push('/contents/playlistvideos');
        }
    },

    computed: {
        // getDuration() {
        //     var sec = parseInt(this.playlist.duration / 1000)
        //     var min = parseInt(sec / 60)
        //     var h = parseInt(min / 60)
        //     return ("0" + h).substr(-2) + ":" + ("0" + min%60).substr(-2) + ":" + ("0" + sec%60).substr(-2)
        // }
    }
}
</script>