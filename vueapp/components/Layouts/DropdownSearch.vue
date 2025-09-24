<template>
    <DropdownBase
        ref="dropdownBaseRef"
        :title="title"
        :button-class="{ 'oc--context-menu__button-active': !hasNoValues && !dropdownBaseRef.isOpen }"
        @select="$emit('select', $event)"
    >
        <template #button>
            <slot name="button">
                <studip-icon shape="search" size="20" />
            </slot>
        </template>

        <template #content>
            <div class="oc--context-menu__section-title" role="presentation">
                {{ $gettext('Suchbegriff') }}
            </div>
            <input
                type="text"
                class="oc--context-menu__search-input"
                v-model="searchTerm"
                @input="onSearchInput"
                aria-label="Suche"
            />
            <div class="oc--context-menu__section-title" role="presentation">
                {{ $gettext('Schlagworte') }}
            </div>
            <div class="oc--context-menu__section-group" role="group" aria-label="Tags" v-if="tags.length">
                <label v-for="tag in tags" :key="tag" class="oc--context-menu__entry oc--context-menu__entry--checkbox">
                    <input
                        type="checkbox"
                        :value="tag"
                        :checked="selectedTags.includes(tag)"
                        @change="onTagToggle(tag, $event.target.checked)"
                    />
                    {{ tag.tag }}
                </label>
            </div>

            <div v-else class="oc--context-menu__no-results">
                {{ $gettext('Keine Tags vorhanden') }}
            </div>
            <div class="oc--context-menu__search-footer">
                <button class="button" @click="reset" :disabled="hasNoValues">{{ $gettext('Zurücksetzen') }}</button>
            </div>
        </template>
    </DropdownBase>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import DropdownBase from './DropdownBase.vue';
import StudipIcon from '@studip/StudipIcon.vue';

const props = defineProps({
    title: { type: String, default: '' },
    tags: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Suchen...' },
    debounceMs: { type: Number, default: 800 },
});

const emit = defineEmits(['search', 'filter', 'select']);

const dropdownBaseRef = ref(null);
const isOpen = ref(false);
const searchTerm = ref('');
const selectedTags = ref([]);

const hasNoValues = computed(() => {
    return searchTerm.value.trim() === '' && selectedTags.value.length === 0;
});

let debounceTimeout = null;

function toggle() {
    isOpen.value = !isOpen.value;
}

watch(searchTerm, (newVal) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        emit('search', newVal);
    }, props.debounceMs);
});

function onTagToggle(tag, checked) {
    if (checked) {
        if (!selectedTags.value.includes(tag)) selectedTags.value.push(tag);
    } else {
        const idx = selectedTags.value.indexOf(tag);
        if (idx > -1) selectedTags.value.splice(idx, 1);
    }
    emit('filter', [...selectedTags.value]);
}

function reset() {
    searchTerm.value = '';
    selectedTags.value = [];
}
</script>