<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable">
            <legend v-translate>
                Ressourcen
            </legend>
            <MessageBox type="info">
                <translate>
                    Jeder Capture-Agent kann nur maximal einem Raum zugewiesen werden!
                </translate>
            </MessageBox>
            <table class="default">
                <caption v-translate>
                    Zuweisung der Capture Agents
                </caption>
                <thead>
                    <tr>
                        <th v-translate>Raum</th>
                        <th v-translate>Capture Agent</th>
                        <th v-translate>Workflow</th>
                        <th v-translate>Status</th>
                        <th v-translate>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(resource, index) in resources" :key="index">
                        <td>{{ resource.name }}</td>
                        <td>
                            <template v-if="resource.capture_agent">
                                {{ resource.capture_agent + ` #${resource.config_id}` }}
                            </template>
                            <template v-else-if="free_capture_agents.length">
                                <select @change="assignCA($event, index)">
                                    <option value="" disabled selected>
                                        <span v-translate>Bitte wählen Sie einen CA.</span>
                                    </option>
                                    <template v-for="(ca_obj, index) in free_capture_agents" :key="index">
                                        <optgroup style="font-weight:bold;" :label="`Server #${ca_obj.id}`">
                                            <option v-for="(capture_agent, calindex) in ca_obj.list" :key="calindex" :value="`${capture_agent.config_id}_` + capture_agent.name">
                                                {{ capture_agent.name }}
                                            </option>
                                        </optgroup>
                                    </template>
                                </select>
                            </template>
                            <span v-else v-translate>
                                Kein (weiterer) CA verfügbar
                            </span>
                        </td>
                        <td>
                            <template v-if="resource.workflow_id">
                                {{ getWorkflowTitle(resource) }}
                            </template>
                            <template v-else-if="resource.capture_agent">
                                <select v-model="resource.workflow_id">
                                    <option value="" disabled selected>
                                        <span v-translate>Bitte wählen Sie einen Workflow aus.</span>
                                    </option>
                                    <option v-for="workflow in compiledWDList(resource)"
                                        :key="workflow.id"
                                        :value="workflow.id"
                                    >
                                       {{ workflow.title }}
                                    </option>
                                </select>
                            </template>
                            <span v-else v-translate>
                                Kein Workflow verfügbar
                            </span>
                        </td>
                        <td>
                            <template v-if="resource.capture_agent && capture_agents.length">
                                <StudipIcon :shape="getCAIconData(resource.capture_agent, 'shape')" role="clickable" :title="getCAIconData(resource.capture_agent, 'title')"/>
                            </template>
                        </td>
                        <td>
                            <a href="#" @click.stop="confirmClearCaputeAgent($event, index)">
                                <StudipIcon shape="trash" :role="resource.capture_agent == '' ? 'inactive' : 'clickable'" :title="$gettext('Verknüpfung entfernen')"/>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipIcon from "@studip/StudipIcon";
import MessageBox from "@/components/MessageBox";

export default {
    name: "SchedulingOptions",

    components: {
        StudipIcon,
        MessageBox
    },

    props: ['config_list'],

    computed: {
        ...mapGetters(['config']),

        resources() {
            let resources = [];
            if (this.config_list?.scheduling?.resources) {
                resources = this.config_list.scheduling.resources;
            }
            return resources;
        },

        capture_agents() {
            let capture_agents = [];
            if (this.config_list?.scheduling?.capture_agents) {
                capture_agents = this.config_list.scheduling.capture_agents;
            }
            return capture_agents;
        },

        free_capture_agents() {
            let free_capture_agents = [];
            if (this.capture_agents) {
                let configs = this.capture_agents.map(ca => {return ca.config_id;}).filter((item, i, ar) => ar.indexOf(item) === i);
                for (let ci = 0; ci < configs.length; ci++) {
                    let ca_cat = this.capture_agents.filter(ca => ca.config_id == configs[ci] && ca.in_use == false);
                    if (ca_cat.length) {
                        free_capture_agents.push({
                            id: configs[ci],
                            list: ca_cat
                        });
                    }
                }
            }
            return free_capture_agents;
        },

        workflow_definitions() {
            let workflow_definitions = [];
            if (this.config_list?.scheduling?.workflow_definitions) {
                workflow_definitions = this.config_list?.scheduling?.workflow_definitions;
            }
            return workflow_definitions;
        }
    },

    methods: {
        assignCA(event, resource_index) {
            event.preventDefault();
            let value = event.target.value;
            let selected_ca_arr = value.split('_');
            if (selected_ca_arr.length == 2) {
                let selected_config_id = selected_ca_arr[0];
                let selected_ca = selected_ca_arr[1];
                let capture_agent_obj = this.capture_agents.filter(ca => ca.name == selected_ca && ca.config_id == selected_config_id);
                if (capture_agent_obj.length) {
                    this.resources[resource_index]['capture_agent'] = selected_ca;
                    this.resources[resource_index]['config_id'] = selected_config_id;
                    this.toggleCAOccupation(selected_ca, selected_config_id, true);
                }
            }
        },

        confirmClearCaputeAgent(event, resource_index) {
            if (event) {
                event.preventDefault();
            }

            // TODO: use ConfirmDialog component when possible!
            if (confirm('Sind Sie sicher, dass Sie diese Verknüpfung entfernen möchten?')) {
                this.clearCaptureAgent(resource_index);
            }
        },

        clearCaptureAgent(resource_index) {
            if (this.resources[resource_index] && this.resources[resource_index]['capture_agent']) {
                this.toggleCAOccupation(this.resources[resource_index]['capture_agent'], this.resources[resource_index]['config_id'], false);
                this.resources[resource_index]['capture_agent'] = '';
                this.resources[resource_index]['config_id'] = '';
                this.resources[resource_index]['workflow_id'] = '';
            }
            return;
        },

        toggleCAOccupation(capture_agent, config_id, status = true) {
            let ca_index = this.capture_agents.findIndex(ca => ca.name == capture_agent && ca.config_id == config_id);
            if (ca_index != -1) {
                this.capture_agents[ca_index]['in_use'] = status;
            }
        },

        compiledWDList(resource_obj) {
            let workflows = [];
            let capture_agent_obj = this.capture_agents.filter(ca => ca.name == resource_obj.capture_agent && ca.config_id == resource_obj.config_id);
            if (capture_agent_obj.length) {
                let config_id = capture_agent_obj[0].config_id;
                workflows = this.workflow_definitions.filter(wd => wd.config_id == config_id);
            }
            return workflows;
        },

        getWorkflowTitle(resource_obj) {
            let wd_title = '';
            let wd_obj = this.workflow_definitions.filter(wd => wd.id == resource_obj.workflow_id && wd.config_id == resource_obj.config_id);
            if (wd_obj.length) {
                wd_title = wd_obj[0].title;
            }
            return wd_title;
        },

        getCAIconData(capture_agent, what) {
            let data = '';
            let capture_agent_obj = this.capture_agents.filter(ca => ca.name == capture_agent);
            if (capture_agent_obj.length) {
                let state = capture_agent_obj[0].state;
                if (what == 'shape') {
                    data = this.getCAShape(state);
                }
                if (what == 'title') {
                    data = this.getCATitle(state);
                }
            }
            return data;
        },

        getCAShape(state) {
            let shape = '';
            switch (state) {
                case 'idle':
                    shape = 'pause';
                    break;
                case 'unknown':
                    shape = 'question';
                    break;
                default:
                    shape = 'video';
                    break;
            }
            return shape;
        },

        getCATitle(state) {
            let title = '';
            switch (state) {
                case 'idle':
                    title = this.$gettext('Idle');
                    break;
                case 'unknown':
                    title = this.$gettext('Status unbekannt');
                    break;
                default:
                    title = this.$gettext('Beschäftigt');
                    break;
            }
            return title;
        },
    }
}
</script>
