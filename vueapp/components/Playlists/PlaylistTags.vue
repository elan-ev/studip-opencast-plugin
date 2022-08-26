<template>
    <div>
        <studip-select :options="availableTags" v-model="playlist.tags" multiple taggable push-tags @option:selected="$emit('update');">
            <template #no-options="{ search, searching, loading }">
                <translate>Es steht keine Auswahl zur Verfügung.</translate>
            </template>
            <template #selected-option="option">
                <studip-icon shape="tag" role="info"/> <span class="vs__option-with-icon">{{option.tag}}</span>
            </template>
            <template #option="option">
                <studip-icon shape="tag" role="info"/> <span class="vs__option-with-icon">{{option.tag}}</span>
            </template>
        </studip-select>
    </div>
</template>

<script>

import StudipSelect from '@studip/StudipSelect';
import StudipIcon from '@studip/StudipIcon';

import { mapGetters } from "vuex";

export default {
    name: "PlaylistTags",

    components: {
        StudipSelect,   StudipIcon
    },

    props: ['playlist'],

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
