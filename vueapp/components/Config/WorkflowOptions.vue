<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable">
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

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageBox from "@/components/MessageBox";
import OpencastIcon from "@/components/OpencastIcon";
import ConfigOption from "@/components/Config/ConfigOption";
import I18NText from "@/components/Config/I18NText";

export default {
    name: "WorkflowOptions",

    components: {
        StudipButton,
        StudipIcon,
        MessageBox,
        OpencastIcon,
        ConfigOption,
        I18NText
    },

    props: ['config_list'],

    computed: {
        ...mapGetters(['config', 'simple_config_list']),

        workflow_definitions() {
            let wf_defs = [];

            if (this.simple_config_list?.settings?.OPENCAST_DEFAULT_SERVER) {
                let config_id = this.simple_config_list?.settings?.OPENCAST_DEFAULT_SERVER;
                for (let wf_conf of this.simple_config_list.workflow_configs) {
                    if (wf_conf['config_id'] == config_id) {
                        let options = [];
                        for (let wf of this.simple_config_list.workflows) {
                            if (wf['config_id'] == config_id && wf['tag'] === wf_conf['used_for']) {
                                options.push({
                                    'value': wf['id'],
                                    'description': wf['displayname']
                                })
                            }
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
