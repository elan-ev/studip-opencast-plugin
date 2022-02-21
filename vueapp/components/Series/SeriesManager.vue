<template>
    <div>
        <StudipDialog
            :title="$gettext('Mit diesem Kurs verknüpfte Opencast Serien')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="course_series.length">
                    <caption v-translate>
                        Verknüpfte Serien
                    </caption>
                    <thead>
                        <th>Titel</th>
                        <th>Ersteller/in</th>
                        <th></th>
                    </thead>
                    <tbody>
                        <tr v-for="entry in course_series">
                            <td v-if="!entry.details" colspan="3">
                                Die Serie mit der ID {{ entry.series_id }}, Server #{{ entry.config_id }}
                                konnte nicht ihm angeschlossenen Opencast-System gefunden werden!
                            </td>

                            <td v-if="entry.details">
                                {{ entry.details.title }}
                            </td>
                            <td v-if="entry.details">
                                {{ entry.details.creator }}
                            </td>
                            <td v-if="entry.details">
                                <StudipIcon shape="trash" role="clickable"
                                    @click.prevent="deleteSeries(entry.series_id)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <form class="default">
                    <fieldset v-translate>
                        <legend v-translate>
                            Weitere Serie verknüpfen
                        </legend>

                        <h4 v-translate>
                            Server auswählen
                        </h4>

                        <label v-for="server in servers"
                            class="oc--server--mini-card "
                        >
                            <input class="studip_checkbox"
                                type="radio"
                                name="servers"
                                v-model="selectedServer"
                                :value="server.id">
                            <span>
                                #{{ server.id }} - {{ server.service_version }}
                                <p>
                                    {{ server.service_url }}
                                </p>
                            </span>
                        </label>
                    </fieldset>

                    <fieldset v-if="selectedServer">
                        <label>
                            <translate>Serie auswählen</translate>
                            <StudipSelect
                                :options="computedSeries"
                                :reduce="computedSeries => computedSeries.identifier"
                                label="title"
                                :clearable="false"
                                v-model="currentSeries"
                                class="cw-vs-select"
                            >
                                <template #open-indicator="selectAttributes">
                                    <span v-bind="selectAttributes"><studip-icon shape="arr_1down" size="10"/></span>
                                </template>
                                <template #no-options="{ search, searching, loading }">
                                    <translate v-if="loadingSeries">Bitte warten, verfügbare Serien werden geladen...</translate>
                                    <translate v-else>Es wurden keine zugreifbaren Serien gefunden!</translate>
                                </template>
                                <template #selected-option="{title}">
                                    <span>{{title}}</span>
                                </template>
                                <template #option="{title}">
                                    <span>{{title}}</span>
                                </template>
                            </StudipSelect>
                        </label>
                    </fieldset>

                    <footer>
                        <StudipButton icon="accept" v-translate @click.prevent="addSeries"
                            :class="{
                                disabled: currentSeries == null
                            }">
                            Serie hinzufügen
                        </StudipButton>
                    </footer>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { LtiService } from '@/common/lti.service';

import StudipDialog from '@studip/StudipDialog';
import StudipSelect from '@studip/StudipSelect';
import StudipIcon from '@studip/StudipIcon';
import StudipButton from "@studip/StudipButton";

export default {
    name: 'SeriesManager',

    components: {
        StudipDialog, StudipSelect, StudipIcon,
        StudipButton
    },

    data() {
        return {
            selectedServer: 0,
            currentSeries: null,
            loadingSeries: false
        }
    },

    computed: {
        ...mapGetters(['servers', 'series', 'course_series']),

        computedSeries() {
            if (this.loadingSeries) {
                return [];
            }

            return this.series;
        }
    },

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        },

        addSeries() {
            console.log(this.currentSeries);
            this.$store.dispatch('addCourseSeries', {
                series_id: this.currentSeries,
                config_id: this.selectedServer
            });
        },

        deleteSeries(series_id) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie diese Serie aus diesem Kurs entfernen möchten?'))) {
                this.$store.dispatch('removeCourseSeries', series_id);
            }
        }
    },

    watch: {
        selectedServer(new_id, old_id) {
            let view = this;

            this.loadingSeries = true;
            this.currentSeries = null;

            this.$store.dispatch('loadSeries', new_id).then(() => {
                view.loadingSeries = false;
            });
        }
    },


    /**
     * This hack is necessary to circumvent binding problems with this component
     */
    deactivated() {
        let elems = document.getElementsByClassName('studip-dialog');
        for (let i = 0; i < elems.length; i++) {
            console.log(elems[i].attributes);
             elems[i].style.display = 'none';
        }
    },

    /**
     * This hack is necessary to circumvent binding problems with this component
     */
    activated() {
        let elems = document.getElementsByClassName('studip-dialog');
        for (let i = 0; i < elems.length; i++) {
            console.log(elems[i].attributes);
             elems[i].style.display = 'block    ';
        }
    },


    mounted() {
        this.$store.dispatch('loadServers');
        this.$store.dispatch('loadCourseSeries')
        this.$store.dispatch('authenticateLti');
    }
}
</script>
