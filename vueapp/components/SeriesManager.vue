<template>
    <div>
        <StudipDialog
            :title="$gettext('Mit diesem Kurs verknüpfte Opencast Serien')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default">
                    <fieldset>
                        <legend v-translate>
                            Verknüpfte Serien
                        </legend>

                    </fieldset>
                    <fieldset v-translate>
                        <legend v-translate>
                            Weitere Serie verknüpfen
                        </legend>

                        <h4 v-translate>
                            Server auswählen
                        </h4>

                        <label v-for="server in servers.servers"
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
                                :options="searchedSeries"
                                :reduce="searchedSeries => searchedSeries.series_id"
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
                                <template #selected-option="{name}">
                                    <span>{{name}}</span>
                                </template>
                                <template #option="{name}">
                                    <span>{{name}}</span>
                                </template>
                            </StudipSelect>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { LtiService } from '@/common/lti.service';
import StudipDialog from '@studip/components/StudipDialog';
import StudipSelect from '@studip/components/StudipSelect';
import StudipIcon from '@studip/components/StudipIcon';

export default {
    name: 'SeriesManager',

    components: {
        StudipDialog, StudipSelect, StudipIcon
    },

    data() {
        return {
            selectedServer: 0,
            searchedSeries: [],
            currentSeries: null
        }
    },

    computed: {
        ...mapGetters(['servers', 'series'])
    },

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        }
    },

    mounted() {
        this.$store.dispatch('loadServers');
        this.$store.dispatch('authenticateLti');
    }
}
</script>
