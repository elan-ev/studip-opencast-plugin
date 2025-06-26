import { ref, watch, computed, onBeforeMount, onBeforeUnmount } from 'vue';
import { useStore } from 'vuex';

export function useSimpleConfigChecker() {
    const store = useStore();
    const simple_config_interval = ref(null);

    const simple_config_list = computed(() => store.getters.simple_config_list);

    async function readSimpleConfigPeriodically() {
        simple_config_interval.value = setInterval(async () => {
            await store.dispatch('simpleConfigListRead');
        }, 7000);
    }

    onBeforeMount(() => {
        readSimpleConfigPeriodically();
    });

    onBeforeUnmount(() => {
        if (simple_config_interval.value !== null) {
            clearInterval(simple_config_interval.value);
        }
    });

    watch(simple_config_list, (newValue, oldValue) => {
        // This is the first run!
        if (JSON.stringify(oldValue) === '{}') {
            return;
        }

        let current_default_config_id, current_main_server,
            new_default_config_id, new_main_server = null;
        let current_maintenance, current_maintenance_readonly,
            new_maintenance, new_maintenance_readonly = false;

        if (oldValue?.settings && oldValue?.server) {
            current_default_config_id = oldValue.settings['OPENCAST_DEFAULT_SERVER'];
            current_main_server = oldValue.server[current_default_config_id];
            current_maintenance = current_main_server?.maintenance_mode?.active;
            current_maintenance_readonly = current_main_server?.maintenance_mode?.read_only;
        }

        if (newValue?.settings && newValue?.server) {
            new_default_config_id = newValue.settings['OPENCAST_DEFAULT_SERVER'];
            new_main_server = newValue.server[new_default_config_id];
            new_maintenance = new_main_server?.maintenance_mode?.active;
            new_maintenance_readonly = new_main_server?.maintenance_mode?.read_only;
        }

        let needs_reload = false;
        // if the default server has been changed, mostly used for upload and studio etc.
        if (JSON.stringify(new_main_server) !==  JSON.stringify(current_main_server)) {
            needs_reload = true;
        }

        // if maintenance has been changed!
        if (current_maintenance !== new_maintenance || current_maintenance_readonly !== new_maintenance_readonly) {
            needs_reload = true;
        }

        if (needs_reload) {
            window.location.reload();
        }
    });

    return {
        simple_config_interval
    };
}
