<template>
    <div>
        <studip-select :options="availableTags" v-model="taggable.tags"
            multiple taggable
            label="tag"
            @option:selected="$emit('update');"
            @option:deselected="$emit('update');"
        >
            <template #list-header>
                <li style="text-align: center">
                    <b>{{ $gettext('Schlagworte') }}</b>
                </li>
            </template>
            <template #no-options="{ search, searching, loading }">
                {{ $gettext('Es existieren bisher keine Schlagworte. Wenn Sie Ihr erstes eigenes Schlagwort in das Suchfeld eingeben, wird dieses automatisch erstellt.')}}
            </template>
            <template #selected-option="option">
                <studip-icon shape="tag" role="info"/>
                <span class="vs__option-with-icon">
                    {{ option.tag }}
                </span>
            </template>
            <template #option="option">
                <studip-icon shape="tag" role="info"/>
                <span class="vs__option-with-icon">
                    {{ option.tag }}
                </span>
            </template>
        </studip-select>
    </div>
</template>

<script>

import StudipSelect from '@studip/StudipSelect';
import StudipIcon from '@studip/StudipIcon';

import { mapGetters } from "vuex";

export default {
    name: "TagBar",

    components: {
        StudipSelect,   StudipIcon
    },

    props: ['taggable'],

    data() {
        return {
            tags:[],
            selectAttributes: {'ref': 'openIndicator', 'role': 'presentation', 'class': 'vs__open-indicator'},
        }
    },

    computed: {
        ...mapGetters(['availableTags'])
    },

    mounted() {
        // update available tags
        this.$store.dispatch('updateAvailableTags');
    }
}
</script>
