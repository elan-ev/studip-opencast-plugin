<template>
    <div>
        <StudipDialog
            :title="$gettext('Ressource bearbeiten')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="415"
            width="500"
            @confirm="acceptChanges"
            @close="close"
        >
            <template v-slot:dialogContent>
                <form class="default" @submit="acceptChanges" ref="formRef">
                    <fieldset>
                        <label>
                            <span>{{ $gettext('Raum') }}</span>
                            <input type="text" disabled :value="editing_resource.name"/>
                        </label>

                        <label>
                            <span class="required">
                                {{ $gettext('Capture Agent') }}
                            </span>

                            <select @change="assignCA($event)" required>
                                <option value="" disabled :selected="!editing_resource?.capture_agent">
                                    {{ $gettext('Bitte wählen Sie einen CA.') }}
                                </option>
                                <template v-for="(ca_obj, index) in filtered_capture_agents" :key="index">
                                    <optgroup style="font-weight:bold;" :label="`Server #${ca_obj.id}`">
                                        <option v-for="(capture_agent, calindex) in ca_obj.list"
                                            :key="calindex"
                                            :value="capture_agent.list_id"
                                            :disabled="capture_agent?.in_use && `${ca_obj.id}_${editing_resource.capture_agent}` !== capture_agent.list_id"
                                            :selected="`${ca_obj.id}_${editing_resource.capture_agent}` === capture_agent.list_id && capture_agent?.in_use"
                                        >
                                            {{ capture_agent.name }}
                                        </option>
                                    </optgroup>
                                </template>
                            </select>
                        </label>

                        <label>
                            <span>
                                {{ $gettext('Workflow') }}
                            </span>

                            <select v-model="editing_resource.workflow_id">
                                <option value="" disabled :selected="!editing_resource?.workflow_id">
                                    {{ $gettext('Bitte wählen Sie einen Workflow aus.') }}
                                </option>
                                <option v-for="workflow in compiledWDList(editing_resource)"
                                    :key="workflow.id"
                                    :value="workflow.id"
                                >
                                    {{ workflow.title }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span>
                                {{ $gettext('Livestream Workflow') }}
                            </span>

                            <select v-model="editing_resource.livestream_workflow_id">
                                <option value="" disabled :selected="!editing_resource?.livestream_workflow_id">
                                    {{ $gettext('Bitte wählen Sie einen Workflow aus.') }}
                                </option>
                                <option v-for="workflow in compiledWDList(editing_resource)"
                                    :key="workflow.id"
                                    :value="workflow.id"
                                >
                                    {{ workflow.title }}
                                </option>
                            </select>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script setup>
import StudipDialog from '@studip/StudipDialog';
import { useStore } from "vuex";
import { useGettext } from 'vue3-gettext';

import { ref, defineComponent, defineEmits, defineProps, computed } from 'vue';

const { $gettext } = useGettext();
const store = useStore();

defineOptions({ name: 'SchedulingEditModal' });

// const props = defineProps({
//     editing_resource: Object,
//     config_list: Object,
// });

const emit = defineEmits(['done', 'cancel']);

defineComponent({
    StudipDialog
});

const formRef = ref();

const config_list = computed(() => {
    return store.getters.config_list;
});

const editing_resource = computed(() => {
    return store.getters.schedulingEditResourceObj;
});

const resources = computed(() => {
    let resources = [];
    if (config_list.value?.scheduling?.resources) {
        resources = config_list.value.scheduling.resources;
    }
    return resources;
});

const editing_resource_index = computed(() => {
    return resources.value.findIndex(rs => rs.id === editing_resource.value.id);
});

const current_resource = computed(() => {
    return resources.value?.[editing_resource_index.value];
});

const capture_agents = computed(() => {
    let capture_agents = [];
    if (config_list.value?.scheduling?.capture_agents) {
        capture_agents = config_list.value.scheduling.capture_agents;
    }
    return capture_agents;
});

const filtered_capture_agents = computed(() => {
    let filtered_capture_agents = [];
    if (capture_agents.value) {
        let configs = capture_agents.value.map(ca => {return ca.config_id;})
                        .filter((item, i, ar) => ar.indexOf(item) === i);
        for (let ci = 0; ci < configs.length; ci++) {
            let ca_cat = capture_agents.value.filter(ca => ca.config_id == configs[ci]);
            ca_cat.map(ca => ca.list_id = `${ca.config_id}_${ca.name}`);
            if (ca_cat.length) {
                filtered_capture_agents.push({
                    id: configs[ci],
                    list: ca_cat
                });
            }
        }
    }
    return filtered_capture_agents;
});

const workflow_definitions = computed(() => {
    let workflow_definitions = [];
    if (config_list.value?.scheduling?.workflow_definitions) {
        workflow_definitions = config_list.value?.scheduling?.workflow_definitions;
    }
    return workflow_definitions;
});

const compiledWDList = (resource_obj) => {
    let workflows = [];
    let capture_agent_obj = capture_agents.value.filter(
        ca => ca.name == resource_obj.capture_agent && ca.config_id == resource_obj.config_id
    );
    if (capture_agent_obj.length) {
        let config_id = capture_agent_obj[0].config_id;
        workflows = workflow_definitions.value.filter(wd => wd.config_id == config_id);
    }
    return workflows;
};

const acceptChanges = () => {
    if (!formRef.value.reportValidity()) {
        return false;
    }

    // If there is no change, we close the modal!
    if (JSON.stringify(editing_resource.value) === JSON.stringify(current_resource.value)) {
        store.dispatch('closeSchedulingEditModal');
        return;
    }

    // As long as the changes are within the same config (server), it is possible to change CA and WFs
    if (parseInt(editing_resource.value.config_id, 10) === parseInt(current_resource.value.config_id, 10)
    ) {
        applyEditChanges();
        return;
    }

    // In case we hit here, that means capture agent and config (server) has been changed, so we open the confirmation modal.
    let confirmData = {
        title: $gettext('Änderungen bestätigen'),
        text: $gettext('Der Aufnahmeagent wurde geändert. Dadurch werden alle zukünftig zugewiesenen geplanten Aufnahmen für diesen Raum und diesen Aufnahmeagenten entfernt! Möchten Sie fortfahren?'),
        confirm: () => {
            applyEditChanges();
        },
        width: 545,
    }

    store.dispatch('openConfirmationModal', confirmData);
    store.dispatch('closeSchedulingEditModal', false);
    return;
};

const applyEditChanges = () => {
    if (!current_resource.value || !editing_resource.value) {
        // TODO: print the error via addMessage modal version.
        return;
    }
    // Make CA not in use!
    toggleCAOccupation(current_resource.value.capture_agent, current_resource.value.config_id, false);
    // Replace current resource with editing resource.
    Object.assign(current_resource.value, editing_resource.value);
    // Make CA in use!
    toggleCAOccupation(current_resource.value.capture_agent, current_resource.value.config_id, true);

    store.dispatch('toggleSchedulingUnsavedChanges', true);
    store.dispatch('closeSchedulingEditModal');
    store.dispatch('closeConfirmationModal');
}

const toggleCAOccupation = (capture_agent, config_id, status = true) => {
    let ca_index = capture_agents.value.findIndex(ca => ca.name == capture_agent && ca.config_id == config_id);
    if (ca_index != -1) {
        capture_agents.value[ca_index]['in_use'] = status;
    }
};

const assignCA = (event) => {
    event.preventDefault();
    let value = event.target.value;
    let selected_ca_arr = value.split('_');
    if (selected_ca_arr.length == 2) {
        let selected_config_id = selected_ca_arr[0];
        let selected_ca = selected_ca_arr[1];
        let capture_agent_obj = capture_agents.value.filter(
            ca => ca.name == selected_ca && ca.config_id == selected_config_id
        );
        if (capture_agent_obj.length) {
            let current_ca = editing_resource.value.capture_agent;
            let current_config_id = editing_resource.value.config_id;
            toggleCAOccupation(current_ca, current_config_id, false);
            editing_resource.value.capture_agent = selected_ca;
            editing_resource.value.config_id = selected_config_id;
            toggleCAOccupation(selected_ca, selected_config_id, true);
        }
    }
};

const close = () => {
    store.dispatch('closeSchedulingEditModal');
};
</script>
