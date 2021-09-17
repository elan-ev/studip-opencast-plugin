<template>
      <div class="cw-block cw-block-test">
        <component
            :is="containerComponents.CoursewareDefaultBlock"
            :block="block"
            :canEdit="canEdit"
            :isTeacher="isTeacher"
            :preview="true"
            :defaultGrade="false"
            @storeEdit="storeBlock"
            @closeEdit="initCurrentData"
        >
            <template #content>
                <div>
                  <div v-if="currentUrl === null">
                      <translate>Es wurde bisher keine Video ausgewählt</translate>
                  </div>
                  <div v-else>
                      <iframe :src="currentUrl"
                        class="oc_courseware"
                        allowfullscreen
                    ></iframe>
                  </div>
                </div>
            </template>
            <template v-if="canEdit" #edit>
                <form class="default" @submit.prevent="">
                    <label>
                        <translate>Serie auswählen</translate>
                        <v-select
                            :options="series"
                            :reduce="series => series.series_id"
                            :clearable="false"
                            v-model="currentSeries"
                            class="cw-vs-select"
                        >
                            <template #open-indicator="selectAttributes">
                                <span v-bind="selectAttributes"><studip-icon shape="arr_1down" size="10"/></span>
                            </template>
                            <template #no-options="{ search, searching, loading }">
                                <translate>Es steht keine Auswahl zur Verfügung</translate>.
                            </template>
                            <template #selected-option="{name}">
                                <span>{{name}}</span>
                            </template>
                            <template #option="{name}">
                                <span>{{name}}</span>
                            </template>
                        </v-select>
                    </label>

                    <label v-if="currentSeries">
                        <translate>Video auswählen</translate>
                        <v-select
                            :options="episodes"
                            label="episode"
                            :reduce="episodes => episodes.id"
                            :clearable="false"
                            v-model="currentEpisode"
                            class="cw-vs-select"
                        >
                            <template #open-indicator="selectAttributes">
                                <span v-bind="selectAttributes"><studip-icon shape="arr_1down" size="10"/></span>
                            </template>
                            <template #no-options="{ search, searching, loading }">
                                <translate>Es steht keine Auswahl zur Verfügung</translate>.
                            </template>
                            <template #selected-option="{name}">
                                <span>{{ name }}</span>
                            </template>
                            <template #option="{name}">
                                <span>{{ name }}</span>
                            </template>
                        </v-select>
                    </label>
                </form>
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
            currentSeries  : null,
            currentEpisode : null,
            currentUrl     : null,
            series: [],
            episodes: []
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

        async loadSeries() {
          await axios
            .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/ajax/getseries?cid=' + this.context.id)
            .then(response => {
              this.series = response.data;
            })
        },

        async loadEpisodes() {
            if (!this.currentSeries) {
                return;
            }

            await axios
                .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/ajax/getepisodes/'
                    + this.currentSeries + '/simple'
                    + '?cid=' + this.context.id)
                .then(response => {
                    this.episodes = response.data;
                })
        }
    },

    watch: {
        currentSeries(old_id, new_id) {
            this.loadEpisodes();
        },

        currentEpisode(old_id, new_id) {
            for (let id in this.episodes) {
                if (this.episodes[id].id == this.currentEpisode) {
                    this.currentUrl = this.episodes[id].url
                }
            }
        }
    },

    async mounted() {
        this.initCurrentData();
        await this.loadSeries();
    },

    inject: ["containerComponents"],
}
</script>
