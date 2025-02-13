<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable" v-if="!disabled">
            <legend>
                {{ $gettext('Standardworkflows') }}
            </legend>

            <ConfigOption v-for="setting in workflow_definitions"
                :key="setting.name" :setting="setting"
                @updateValue="updateValue"/>
        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import ConfigOption from "@/components/Config/ConfigOption";

export default {
    name: "WorkflowOptions",

    components: {
        ConfigOption
    },

    props: {
        disabled: true
    },

    computed: {
        ...mapGetters(['simple_config_list']),

        workflow_definitions() {
            let wf_defs = [];
            if (this.simple_config_list?.settings?.OPENCAST_DEFAULT_SERVER) {
                let config_id = this.simple_config_list.settings.OPENCAST_DEFAULT_SERVER;
                for (let wf_conf of this.simple_config_list.workflow_configs) {
                    if (wf_conf['config_id'] == config_id) {
                        let options = [];
                        for (let wf of this.simple_config_list.workflows) {
                            if (wf['config_id'] == config_id &&
                                (
                                    wf['tag'] === wf_conf['used_for']
                                    || wf['tag'] === 'upload' && wf_conf['used_for'] === 'studio'
                                )
                            ) {
                                options.push({
                                    'value': wf['id'],
                                    'description': wf['displayname']
                                })
                            }
                        }

                        if (options.length == 0) {
                            options.push({
                                'value': null,
                                'description': 'Kein Workflow verfügbar'
                            });
                        }

                        wf_defs.push({
                            'type': 'string',
                            'required': true,
                            'value': wf_conf['workflow_id'],
                            'name': wf_conf['id'],
                            'description': wf_conf['used_for'],
                            'options': options
                        });
                    }
                }
            }

            return wf_defs;
        }
    },

    methods: {
        updateValue(setting, newValue) {
            for (let id in this.simple_config_list.workflow_configs) {
                if (this.simple_config_list.workflow_configs[id]['id'] == setting.name) {

                    this.simple_config_list.workflow_configs[id]['workflow_id'] = newValue;
                    return;
                }
            }
        }
    }
}
</script>
