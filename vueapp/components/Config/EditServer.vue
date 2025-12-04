<template>
    <div>
        <StudipDialog
            :title="$gettext('Opencast Server Einstellungen')"
            :confirmText="$gettext('Speichern')"
            :closeText="$gettext('Schließen')"
            :disabled="disabled"
            height="600"
            width="600"
            @confirm="storeConfig"
            @close="close"
        >
            <template v-slot:dialogContent ref="editServer-dialog">
                <form class="default" v-if="currentConfig" ref="editServer-form">
                    <fieldset>
                        <legend>
                            {{ $gettext('Grundeinstellungen') }}
                        </legend>
                        <label v-if="config?.service_version">
                            <b> {{ $gettext('Opencast Version') }} </b><br />
                            {{ isLikelyValidVersion(config.service_version) ? config.service_version : '-'}}
                        </label>

                        <ConfigOption v-for="setting in settings"
                            :setting="setting" :key="setting.name"
                            @updateValue="updateValue" />
                    </fieldset>

                    <WorkflowOptions :disabled="currentId === 'new'" ref="workflow-form"/>

                    <UploadOptions :configId="currentId" :disabled="currentId === 'new'"/>
                </form>

                <MessageList :float="true" :dialog="true"/>
            </template>

            <template v-slot:dialogButtons>
                <button v-if="currentId !== 'new'"
                    class="button trash"
                    type="button"
                    @click="deleteConfig"
                    :disabled="disabled"
                >
                    Löschen
                </button>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";
import ConfigOption from "@/components/Config/ConfigOption";
import WorkflowOptions from "@/components/Config/WorkflowOptions";
import UploadOptions from "@/components/Config/UploadOptions";

import axios from "@/common/axios.service";

export default {
    name: "EditServer",

    components: {
        StudipButton,
        StudipIcon,
        StudipDialog,
        ConfigOption,
        MessageList,
        WorkflowOptions,
        UploadOptions,
    },

    props: {
        id : {
            default: 'new'
        },
        config : {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            currentConfig: {},
            disabled: false
        }
    },

    computed: {
        ...mapGetters({
            configStore: 'config',
            simple_config_list: 'simple_config_list'
        }),

        currentId() {
            return this.currentConfig.id ? this.currentConfig.id : this.id;
        },

        settings() {
            return [
                {
                    description: this.$gettext('Basis URL zur Opencast-Installation'),
                    name: 'service_url',
                    value: this.currentConfig.service_url,
                    type: 'string',
                    placeholder: 'https://opencast.url',
                    required: true
                },
                {
                    description: this.$gettext('Nutzerkennung'),
                    name: 'service_user',
                    value: this.currentConfig.service_user,
                    type: 'string',
                    placeholder: 'ENDPOINT_USER',
                    required: true
                },
                {
                    description: this.$gettext('Passwort'),
                    name: 'service_password',
                    value: this.currentConfig.service_password,
                    type: 'password',
                    placeholder: 'ENDPOINT_USER_PASSWORD',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumerkey'),
                    name: 'lti_consumerkey',
                    value: this.currentConfig.lti_consumerkey,
                    type: 'string',
                    placeholder: 'CONSUMERKEY',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumersecret'),
                    name: 'lti_consumersecret',
                    value: this.currentConfig.lti_consumersecret,
                    type: 'password',
                    placeholder: 'CONSUMERSECRET',
                    required: true
                },
                {
                    description: this.$gettext('Zeitpuffer (in Sekunden) um Überlappungen zu verhindern'),
                    name: 'time_buffer_overlap',
                    value: this.currentConfig.time_buffer_overlap ? this.currentConfig.time_buffer_overlap : this.default_time_buffer_overlap,
                    type: 'number',
                    required: false
                },
                {
                    description: this.$gettext('Ist rollenbasierter Zugriff per Event-ID aktiviert?'),
                    name: 'episode_id_role_access',
                    value: this.currentConfig.episode_id_role_access ?? false,
                    type: 'boolean',
                    required: false
                },
                {
                    description: this.$gettext('Debugmodus einschalten?'),
                    name: 'debug',
                    value: this.currentConfig.debug,
                    type: 'boolean',
                    required: false
                },
                {
                    description: this.$gettext('SSL-Zertifkatsfehler ignorieren?'),
                    name: 'ssl_ignore_cert_errors',
                    value: this.currentConfig.ssl_ignore_cert_errors,
                    type: 'boolean',
                    required: false
                },
                {
                    description: this.$gettext('Timeout (in Millisekunden)'),
                    name: 'timeout_ms',
                    value: this.currentConfig.timeout_ms ?? '0',
                    type: 'integer',
                    placeholder: '0',
                    required: true
                },
                {
                    description: this.$gettext('Verbindungs-Timeout (in Millisekunden)'),
                    name: 'connect_timeout_ms',
                    value: this.currentConfig.connect_timeout_ms ?? '2000',
                    type: 'integer',
                    placeholder: '2000',
                    required: true
                }
            ];
        },

        default_time_buffer_overlap() {
            return this.configStore.settings.time_buffer_overlap;
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        storeConfig() {
            if (!this.$refs['editServer-form'].reportValidity()) {
                return false;
            }

            this.disabled = true;
            this.$store.dispatch('clearMessages', true);

            this.currentConfig.checked = false;

            if (this.currentId == 'new') {
                this.$store.dispatch('configCreate', this.currentConfig)
                .then(({ data }) => {
                    this.disabled = false;
                    this.$store.dispatch('configListRead', data.config);
                    this.checkConfigResponse(data);
                });
            } else {
                if (this.simple_config_list?.workflow_configs) {
                    this.currentConfig.workflow_configs = this.simple_config_list.workflow_configs;
                }

                // Add workflow settings
                if (this.simple_config_list?.workflows) {
                    let workflow_settings = [];
                    for (let workflow of this.simple_config_list?.workflows) {
                        // Check if upload file type is set
                        if (!Array.isArray(workflow.settings)) {
                            workflow.settings = [];
                        }

                        if (!workflow.settings.upload_file_types) {
                            workflow.settings.upload_file_types = this.simple_config_list.default_upload_file_types;
                        }

                        workflow_settings.push({
                            'id': workflow.id,
                            ...workflow.settings,
                        });
                    }
                    this.currentConfig.workflow_settings = workflow_settings;
                }

                this.$store.dispatch('configUpdate', this.currentConfig)
                .then(({ data }) => {
                    this.$store.dispatch('configListRead', data.config);
                    this.disabled = false;
                    this.checkConfigResponse(data);
                });
            }
        },

        deleteConfig() {
            if (confirm(this.$gettext('Sind Sie sicher, dass Sie die Serverkonfiguration löschen möchten? Die damit verbundenen Videos werden danach nicht mehr in Stud.IP zur Verfügung stehen!'))) {
                if (this.currentId == 'new') {
                    this.currentConfig = {}
                } else {
                    this.$store.dispatch('configDelete', this.currentId)
                        .then(() => {
                            this.$store.dispatch('configListRead');
                            this.$store.dispatch('addMessage', {
                                'type': 'success',
                                'text': this.$gettext('Serverkonfiguration wurde entfernt')
                            });
                            this.$forceUpdate;
                        });
                }

                this.close();
            }
        },

        checkConfigResponse(data) {
            if (data.message !== undefined) {
                if (data.message.type === 'error') {
                    this.$store.dispatch('addMessage', {
                        type: data.message.type,
                        text: data.message.text
                    });
                    return;
                }
                else if (data.message.type === 'success') {
                    if (this.currentId !== 'new') {
                        // Just show success message if server was edited
                        this.$store.dispatch('addMessage', {
                            type: data.message.type,
                            text: data.message.text
                        });
                    }
                    else {
                        // On create, scroll to the default workflow configuration
                        this.$store.dispatch('addMessage', {
                            type: data.message.type,
                            text: data.message.text + ' ' + this.$gettext('Sie können nun die Standardworkflows einstellen oder die Konfiguration abschließen.'),
                            dialog: true
                        });

                        this.currentConfig = data.config;
                        this.$store.dispatch('simpleConfigListRead');

                        let view = this;
                        // We need to wait for a short time so the component is actually visible
                        setTimeout(() => {
                            view.$refs['workflow-form'].$el.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }, 10);
                    }

                    if (this.currentId !== 'new') {
                        // Only close dialog, if no new server was created
                        this.$emit('close');
                    }

                    return;
                }
            }

            this.$store.dispatch('addMessage', {
                    type: 'error',
                    text: this.$gettext('Bei der Konfiguration ist ein Fehler aufgetreten. Versuchen Sie es bitte erneut.'),
                    dialog: true
            });
        },

        updateValue(setting, newValue) {
            this.currentConfig[setting.name] = newValue;
        },

        isLikelyValidVersion(version) {
            if (typeof version !== 'string') return false;

            const regex = /^\d+\.\d+(?:\.\d+)?(?:[-.][a-zA-Z0-9]+)*$/;
            return regex.test(version);
        }
    },

    mounted() {
        if (this.currentId !== 'new') {
            if (!this.config) {
                this.$store.dispatch('configRead', this.currentId)
                .then(() => {
                    this.currentConfig = this.configStore;
                });
            } else {
                this.currentConfig = this.config;
            }
        }

    }
};
</script>
