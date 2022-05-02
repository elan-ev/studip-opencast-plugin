<template>
      <div class="cw-block cw-block-test">
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
                <div v-if="context.type == 'courses'">
                  <div v-if="currentUrl === null || ltiConnected == false">
                      <span v-if="currentUrl === null" v-translate v-text="'Es wurde bisher keine Video ausgewählt'"></span>
                      <span v-else v-translate v-text="'Das Video ist nicht verfügbar'"></span>
                  </div>
                  <div v-else>
                      <iframe :src="currentUrl"
                        class="oc_courseware"
                        allowfullscreen
                    ></iframe>
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
                        <translate>Video auswählen</translate>
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
            ltiConnected    : false,
            loadingSeries   : false,
            loadingEpisodes : false,
            currentVisible  : true
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
                url       : this.currentUrl
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
            this.currentUrl     = get(this.block, "attributes.payload.url", "");
        },

        loadEpisodes() {
            let view = this;
            view.loadingEpisodes = true;

            axios
                .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/ajax/course_episodes/'
                    + this.context.id + '?cid=' + this.context.id)
                .then(response => {
                    this.episodes = response.data;
                    view.loadingEpisodes = false;
                })
        },

        runLTI() {
            axios.get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/ajax/getltidata/'
                + this.currentSeries +  '?cid=' + this.context.id)
                .then(({data}) => {
                    if (data.lti_url && data.lti_data) {
                        OC.ltiCall(data.lti_url, JSON.parse(data.lti_data),
                        () => {
                            this.ltiConnected = true;
                        },
                        () => {
                            this.ltiConnected = false;
                        });
                    } else {
                        this.ltiConnected = false;
                    }
                })
        }
    },

    watch: {
        currentEpisode(old_id, new_id) {
            for (let id in this.episodes) {
                if (this.episodes[id].id == this.currentEpisode) {
                    this.currentSeries  = this.episodes[id].series_id;
                    this.currentUrl     = this.episodes[id].url;
                    this.currentVisible = this.episodes[id].visible;
                }
            }
        }
    },

    mounted() {
        this.loadEpisodes();
        this.initCurrentData();
        this.runLTI();
    },

    inject: ["containerComponents"],
}
</script>
