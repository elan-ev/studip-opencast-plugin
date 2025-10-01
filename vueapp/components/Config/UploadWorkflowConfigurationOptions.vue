<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable" v-if="!disabled">
            <legend>
                {{ $gettext('Upload-Workflow-Konfigurationsoptionen') }}
            </legend>
            <fieldset v-for="(item, key) in configurationPanelOptions" :key="item.name + uploadWorkflowIndex">
                <label>
                    <span>
                        {{ $gettext('Option ID') }}
                    </span>
                    <input type="text" :name="`${key}-id`" readonly disabled :value="key">
                </label>
                <label>
                    <span>
                        {{ $gettext('Label') }}
                    </span>
                    <I18NText
                        type="text"
                        :text="item.displayName"
                        :callback-key="key"
                        :languages="languagesList"
                        @updateValue="setLabelValue"
                    />
                </label>
                <ConfigOption
                    :setting="
                        {
                            description: this.$gettext('Diese Option beim Hochladen anbieten?'),
                            name: `${key}-show`,
                            value: item.show,
                            type: 'boolean',
                            required: false
                        }
                    "
                    :key="key"
                    @updateValue="toggleActivation(key)"
                />
            </fieldset>
        </fieldset>
    </div>
</template>

<script>
import I18NText from "@/components/Config/I18NText";
import ConfigOption from "@/components/Config/ConfigOption";
import { mapGetters } from "vuex";

export default {
    name: "UploadWorkflowConfigurationOptions",

    components: {
        I18NText,
        ConfigOption,
    },

    props: {
        disabled: true
    },

    computed: {
        ...mapGetters(['simple_config_list', 'config_list']),

        uploadWorkflowConfig() {
            let wf = this.simple_config_list?.workflow_configs.filter(wf => wf.used_for === 'upload')?.[0];
            return wf;
        },

        uploadWorkflowIndex() {
            if (this.uploadWorkflowConfig) {
                let index = this.simple_config_list?.workflows.findIndex(wf => {
                    return parseInt(wf.id, 10) === parseInt(this.uploadWorkflowConfig.workflow_id, 10) &&
                        parseInt(wf.config_id, 10) === parseInt(this.uploadWorkflowConfig.config_id, 10);
                });
                return index;
            }
            return null;
        },

        uploadWorkflow() {
            if (this.uploadWorkflowIndex !== null) {
                return this.simple_config_list?.workflows[this.uploadWorkflowIndex];
            }
            return null;
        },

        configurationPanelOptions() {
            let configurationPanelOptions = this.uploadWorkflow?.configuration_panel_options || {};
            return configurationPanelOptions;
        },

        languagesList() {
            let languages = this.config_list.languages;
            // We need to add the default as well.
            let defaultLang = {
                id: "default",
                picture: "icons/blue/globe.svg",
                name: this.$gettext('Standard')
            };
            languages.default = defaultLang;
            return languages;
        }
    },

    methods: {
        setLabelValue(newValue, lang, key) {
            this.simple_config_list.workflows[this.uploadWorkflowIndex].configuration_panel_options[key].displayName[lang] = newValue;
        },

        toggleActivation(key) {
            this.simple_config_list.workflows[this.uploadWorkflowIndex].configuration_panel_options[key].show = !this.simple_config_list.workflows[this.uploadWorkflowIndex].configuration_panel_options[key].show;
        }
    },
}
</script>
