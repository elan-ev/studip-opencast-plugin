<template>
    <div>
        <StudipDialog
            :title="$gettext('Standardsichtbarkeit Videos in dieser Veranstaltung')"
            :confirmText="$gettext('Akzeptieren')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="260"
            width="530"
            @close="$emit('cancel')"
            @confirm="setCourseEpisodesVisibility"
        >
            <template v-slot:dialogContent>
                <form class="default" @submit="setCourseEpisodesVisibility">
                    <label>
                        <input type="radio" name="visibility" value="default"
                            :checked="currentVisibilityOption == 'default'"
                            v-model="visibilityOption"
                        >
                        {{ $gettext('Systemstandard') + ' (' + $gettext(standardVisibilityText) + ')' }}
                    </label>

                    <label>
                        <input type="radio" name="visibility" value="visible"
                            :checked="currentVisibilityOption == 'visible'"
                            v-model="visibilityOption"
                        >
                        {{ $gettext('Videos standardmäßig sichtbar für Studierende') }}
                    </label>

                    <label>
                        <input type="radio" name="visibility" value="hidden"
                            :checked="currentVisibilityOption == 'hidden'"
                            v-model="visibilityOption"
                        >
                        {{ $gettext('Videos standardmäßig unsichtbar für Studierende') }}
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { ref, computed, onBeforeMount } from 'vue'
import { useStore } from "vuex";
import { useGettext } from 'vue3-gettext';

import StudipDialog from "@/components/Studip/StudipDialog.vue";

export default {
    name: "EpisodesDefaultVisibilityDialog",
    components: {
        StudipDialog,
    },
    emits: ['done', 'cancel'],

    setup(props, { emit }) {
        const { $gettext } = useGettext();
        const store = useStore();

        const visibilityOption = ref();
        const cid = computed(() => store.getters.cid);
        const standardVisibilityText = computed(() => {
            return store.getters.simple_config_list?.settings?.OPENCAST_HIDE_EPISODES ?
            $gettext('Videos standardmäßig unsichtbar für Studierende') : $gettext('Videos standardmäßig sichtbar für Studierende');
        });
        const currentVisibilityOption = computed(() => {
            return store.getters.course_config?.course_default_episodes_visibility ?? 'default';
        });

        const setCourseEpisodesVisibility = () => {
            store.dispatch('setCourseEpisodesVisibility', {
                cid: store.getters.cid,
                visibility_option: visibilityOption.value
            }).then((data) => {
                store.dispatch('loadCourseConfig', store.getters.cid);
                store.dispatch('loadCourseConfig', store.getters.cid);
                emit('done');
            });
        }

        onBeforeMount(() => {
            // Make sure that the visibilityOption gets its inital value from computed variable!
            visibilityOption.value = currentVisibilityOption.value;
        })

        return {
            setCourseEpisodesVisibility,
            currentVisibilityOption,
            visibilityOption,
            cid,
            standardVisibilityText,
        }
    }
}
</script>
