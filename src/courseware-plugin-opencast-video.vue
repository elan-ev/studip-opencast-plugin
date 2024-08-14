<template>
      <div class="cw-block">
        <component
            :is="containerComponents.CoursewareDefaultBlock"
            :block="block"
            :canEdit="canEdit"
            :isTeacher="isTeacher"
            :preview="true"
            @storeEdit="storeBlock"
            @closeEdit="initCurrentData"
        >
            <template #content>
                <h2 v-if="currentTitle">{{ currentTitle }}</h2>
                <div v-if="context.type == 'courses'">
                  <div v-if="currentUrl === null">
                      <span  v-translate v-text="'Es wurde bisher keine Video ausgewählt'"></span>
                  </div>
                  <div v-else-if="currentUrl && (firstLTI || ltiConnected)">
                    <iframe :src="currentUrl + ((firstLTI) ? '': '?embed=1')"
                        class="oc_courseware"
                        @load="iframeIsLoaded"
                        ref="ociframe"
                        allowfullscreen
                    ></iframe>
                  </div>
                  <div class="oc-video-loading" v-else>
                    <span v-translate v-text="'Video wird geladen'"></span>
                  </div>
                </div>

                <div v-else>
                    <div class="messagebox messagebox_info cw-canvasblock-text-info">
                        <translate>
                            Dies ist ein Opencast Video-Block. Um ein Video zuzuordnen, muss dieser
                            Block in einem Veranstaltungskontext sein. Sobald dieser Block z.B. in eine
                            Veranstaltung kopiert wurde, können sie ein Video zuordnen!
                        </translate>
                    </div>
                </div>
            </template>
            <template v-if="canEdit" #edit>
                <div v-if="currentVisible == 'invisible' && canEdit" class="messagebox messagebox_warning cw-canvasblock-text-info">
                    <translate>
                        Dieses Video ist für die Teilnehmenden dieser Veranstaltung nicht sichtbar!
                        Korrigieren sie die Sichtbarkeitseinstellungen im Opencast-Reiter.
                    </translate>
                </div>

                <form v-if="context.type == 'courses'" class="default" @submit.prevent="">
                    <label>
                        <translate>
                            Titel:
                        </translate>
                        <input type="text" v-model="currentTitle">
                    </label>
                    <label>
                        <translate>
                            Video auswählen
                        </translate>
                        <studip-select
                            :options="episodes"
                            label="name"
                            :reduce="episodes => episodes.id"
                            :clearable="false"
                            v-model="currentEpisode"
                        >
                            <template #open-indicator="selectAttributes">
                                <span v-bind="selectAttributes"><studip-icon shape="arr_1down" size="10"/></span>
                            </template>
                            <template #no-options="{ search, searching, loading }">
                                <translate v-if="loadingEpisodes">Bitte warten, verfügbare Episoden werden geladen...</translate>
                                <translate v-else>Es wurden keine zugreifbaren Episoden gefunden!</translate>
                            </template>
                            <template #selected-option="{name, visible}">
                                <span>{{ name }}
                                    <translate v-if="visible =='invisible'" class="oc_italic">
                                        (unsichtbar für Teilnehmende!)
                                    </translate>
                                </span>
                            </template>
                            <template #option="{name, visible}">
                                <span>{{ name }}
                                    <translate v-if="visible =='invisible'" class="oc_italic">
                                        (unsichtbar für Teilnehmende!)
                                    </translate>
                                </span>
                            </template>
                        </studip-select>
                    </label>
                </form>

                <div v-else>
                    <div class="messagebox messagebox_info cw-canvasblock-text-info">
                        <translate>
                            Sie können diesem Block momentan kein Video zuordnen, da dieser sich nicht
                            in einer Veranstaltung befindet.
                        </translate>
                    </div>
                </div>
            </template>

            <template #info><translate>Informationen zum Opencast-Block</translate></template>

            <iframe :src="ltiURL" frameborder="0"></iframe>
        </component>
    </div>
</template>

<script>
const get = window._.get.bind(window._);
import axios from 'axios';
import { mapGetters } from 'vuex';

export default {
    name: "courseware-plugin-opencast-video",

    props: {
        block: Object,
        canEdit: Boolean,
        isTeacher: Boolean,
    },

    data() {
        return {
            currentSeries   : null,
            currentEpisode  : null,
            currentUrl      : null,
            series          : [],
            episodes        : [],
            loadingSeries   : false,
            loadingEpisodes : false,
            currentVisible  : true,
            currentTitle    : '',
            titleFromBackend: false,
            ltiConnected    : false,
            firstLTI        : false,
            timer           : null
        }
    },

    computed: {
        ...mapGetters({
            context: 'context',
        })
    },

    methods: {
        storeBlock() {
            const attributes = { payload: {
                series_id : this.currentSeries,
                episode_id: this.currentEpisode,
                title     : this.currentTitle
            } };
            const container = this.$store.getters["courseware-containers/related"]({
                parent: this.block,
                relationship: "container",
            });
            return this.$store.dispatch("updateBlockInContainer", {
                attributes,
                blockId: this.block.id,
                containerId: container.id,
            });
        },

        initCurrentData() {
            this.currentSeries  = get(this.block, "attributes.payload.series_id", "");
            this.currentEpisode = get(this.block, "attributes.payload.episode_id", "");
            this.currentTitle   = get(this.block, "attributes.payload.title", "");

            if (this.currentTitle) {
                this.titleFromBackend = true;
            }

            if (this.currentEpisode != '') {
                this.checkFirstLTI();
                this.currentUrl = STUDIP.ABSOLUTE_URI_STUDIP
                    + 'plugins.php/opencast/redirect/perform/video/' + this.currentEpisode
                    + '?cid=' + this.context.id;
            }
        },

        loadEpisodes() {
            let view = this;
            view.loadingEpisodes = true;

            axios
                .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/ajax/course_episodes/'
                    + this.context.id + '?cid=' + this.context.id)
                .then(response => {
                    view.episodes = response.data;

                    // check, if there is a matching episode id
                    if (view.currentEpisode) {
                        if (view.episodes.find((element) => element.id == view.currentEpisode) === undefined) {
                            let data;
                            view.episodes.push(data = {
                                series_id : view.currentSeries,
                                id        : view.currentEpisode,
                                name      : view.currentTitle + ' (Import)',
                                visible   : true
                            });
                        };
                    }
                    view.loadingEpisodes = false;
                })
        },

        checkFirstLTI()
        {
            if (!window.OPENCAST_SEMAPHORE) {
                window.OPENCAST_SEMAPHORE = 1;
                this.firstLTI = true;
            }
        },

        iframeIsLoaded()
        {
            if (this.firstLTI) {
                window.OPENCAST_LTI_CONNECTED = true;
            }
        },

        checkLTI()
        {
            if (window.OPENCAST_LTI_CONNECTED) {
                clearInterval(this.timer);

                // give another extra second time, just to be sure
                setTimeout(() => {
                    this.ltiConnected = true;
                }, 1000);
            }
        }
    },

    watch: {
        currentEpisode(old_id, new_id) {
            for (let id in this.episodes) {
                if (this.episodes[id].id == this.currentEpisode) {
                    this.currentSeries  = this.episodes[id].series_id;
                    this.checkFirstLTI();
                    this.currentUrl     = STUDIP.ABSOLUTE_URI_STUDIP
                        + 'plugins.php/opencast/redirect/perform/video/' + this.currentEpisode
                        + '?cid=' + this.context.id;
                    this.currentVisible = this.episodes[id].visible;
                    if (!this.titleFromBackend) {
                        this.currentTitle = this.episodes[id].name;
                    }
                }
            }
        }
    },

    mounted() {
        this.loadEpisodes();
        this.initCurrentData();
        this.timer = setInterval(() => {
            this.checkLTI();
        }, 300);
    },

    inject: ["containerComponents"],
}
</script>
