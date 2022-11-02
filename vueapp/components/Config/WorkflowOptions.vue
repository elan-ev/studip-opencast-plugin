<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable">
            <legend>
                {{ $gettext('Standardworkflows') }}
            </legend>
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
        ...mapGetters(['config']),

        workflow_definitions() {
            let workflow_definitions = [];
            if (this.config_list?.scheduling?.workflow_definitions) {
                workflow_definitions = this.config_list?.scheduling?.workflow_definitions;
            }
            return workflow_definitions;
        }
    },

    methods: {

    }
}
</script>
