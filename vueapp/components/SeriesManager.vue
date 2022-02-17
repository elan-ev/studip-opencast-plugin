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
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { LtiService } from '@/common/lti.service';
import StudipDialog from '@studip/components/StudipDialog';

export default {
    name: 'SeriesManager',

    components: {
        StudipDialog
    },

    data() {
        return {
            selectedServer: 0
        }
    },

    computed: {
        ...mapGetters(['servers'])
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
        this.$store.dispatch('loadServers').then(() => {
            for (let id in this.servers.servers) {
                let server = this.servers.servers[id];
                let lti = new LtiService(server.id);
                lti.setLaunchData(server.lti);
                lti.authenticate();
            }
        });
    }
}
</script>
