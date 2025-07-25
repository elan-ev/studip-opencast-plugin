<template>
  <DropdownBase ref="dropdownBaseRef" :title="title" @select="onSelect">
    <template #button>
      <slot name="button">
        <studip-icon shape="filter" :size="20" />
      </slot>
    </template>

    <template #content>
      <template v-for="(group, idx) in items" :key="idx">
        <div class="oc--context-menu__section-title" role="presentation">
          {{ group.label }}
        </div>
        <div class="oc--context-menu__section-group" role="group" :aria-label="group.label">
          <select
            class="oc--context-menu__select"
            @change="handleSelect(group.emit, $event)"
            :aria-label="group.label"
          >
            <option disabled selected value="">{{ $gettext('Bitte wählen') }}</option>
            <option
              v-for="option in group.options"
              :key="option.value"
              :value="option.value"
              :selected="group.selected === option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>
      </template>
    </template>
  </DropdownBase>
</template>

<script setup>
import { ref } from 'vue';
import DropdownBase from './DropdownBase.vue';
import StudipIcon from '@studip/StudipIcon.vue';

const props = defineProps({
  title: { type: String, required: true },
  items: {
    type: Array,
    required: true,
    // [{ label: 'Kategorie', options: [{ value: 'x', label: 'X' }, ...] }, ...]
  },
});

const emit = defineEmits(['select', 'toggle']);

const dropdownBaseRef = ref(null);

function handleSelect(groupEmit, event) {
  const val = event.target.value;
  if (!val) return;
  emit('select', { emit: groupEmit, value: val });
  dropdownBaseRef.value?.close();
}

function onSelect(item) {
  emit('select', item);
}
</script>
