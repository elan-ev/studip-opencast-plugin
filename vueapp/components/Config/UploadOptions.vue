<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable collapsed" v-if="!disabled">
            <legend>
                {{ $gettext('Erlaubte Dateiendungen beim Hochladen von Mediendateien') }}
            </legend>

            <ConfigOption v-for="setting in upload_settings"
                          :key="setting.name" :setting="setting" useDescriptionAsLabel="true"
                          @updateValue="updateValue"/>
        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import ConfigOption from "@/components/Config/ConfigOption";

export default {
    name: "UploadOptions",

    components: {
        ConfigOption
    },

    props: {
        configId: {
            type: String,
            required: true,
        },
        disabled: true
    },

    data() {
        return {
            selectedUploadWorkflow: null,
        }
    },

    computed: {
        ...mapGetters('config', ['simple_config_list']),

        upload_workflows() {
            let upload_workflows = [];

            for (let wf of this.simple_config_list.workflows) {
                if (wf['config_id'] === this.configId && wf['tag'] === 'upload') {
                    upload_workflows.push(wf);
                }
            }

            return upload_workflows;
        },

        upload_settings() {
            return this.upload_workflows.map(wf => {
                return {
                    description: wf.displayname,
                    name: wf.id,
                    value: wf.settings?.upload_file_types || '',
                    type: 'string',
                    placeholder: this.simple_config_list.default_upload_file_types
                };
            });
        }
    },

    methods: {
        updateValue(setting, newValue) {
            for (let index in this.simple_config_list.workflows) {
                if (this.simple_config_list.workflows[index]['id'] === setting.name) {
                    this.simple_config_list.workflows[index]['settings']['upload_file_types'] = newValue;
                    return;
                }
            }
        }
    }
}
</script>
