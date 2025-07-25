<template>
  <DropdownBase ref="dropdownBaseRef" :title="title" @select="onSelect" @toggle="onToggle">
    <template #button>
      <slot name="button">
        <studip-icon shape="actions" />
      </slot>
    </template>

    <template #content>
      <template v-for="(section, sIndex) in items" :key="sIndex">
        <div v-if="section.name" class="oc--context-menu__section-title" role="presentation">
          {{ section.name }}
        </div>
        <div class="oc--context-menu__section-group" role="group" :aria-label="section.name || undefined">
          <component
            v-for="item in section.items"
            :key="item.id"
            :is="item.type === 'link' ? 'a' : 'button'"
            :href="item.type === 'link' ? item.url : undefined"
            :class="['oc--context-menu__entry', { 'is-disabled': item.disabled }]"
            role="menuitem"
            :tabindex="item.disabled ? -1 : 0"
            :disabled="item.type !== 'link' && item.disabled"
            :target="item.newTab ? '_blank' : undefined"
            :rel="item.newTab ? 'noopener noreferrer' : undefined"
            @click="onClick(item, $event)"
            @keydown.enter.prevent="onEnter(item)"
          >
            <div class="oc--context-menu__entry-content">
              <studip-icon v-if="item.icon" :shape="item.icon" class="oc--context-menu__entry-icon" />
              <div class="oc--context-menu__entry-texts">
                <div class="oc--context-menu__entry-label">{{ item.label }}</div>
                <div v-if="item.description" class="oc--context-menu__entry-description">{{ item.description }}</div>
              </div>
              <LayoutSwitch
                v-if="item.type === 'toggle'"
                :tabable="false"
                :model-value="item.value"
                @update:modelValue="val => $emit(item.emit, val)"
                @click.stop
              />
            </div>
          </component>
        </div>
      </template>
    </template>
  </DropdownBase>
</template>

<script setup>
import { ref } from 'vue';
import DropdownBase from './DropdownBase.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import LayoutSwitch from './LayoutSwitch.vue';

const dropdownBaseRef = ref(null);

defineProps({
  title: {
    type: String,
    required: true,
  },
  items: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['select', 'toggle']);

function onClick(item, event) {
  if (item.disabled) {
    event.preventDefault();
    return;
  }
  if (item.type !== 'link' && item.type !== 'toggle') {
    emit('select', item);
    dropdownBaseRef.value?.close();
  }
  if (item.type === 'link') {
    dropdownBaseRef.value?.close();
  }
  if (item.type === 'toggle') {
    toggleItem(item);
  }
}

function onEnter(item) {
  if (item.type !== 'link' && item.type !== 'toggle') {
    emit('select', item);
  }
}

function toggleItem(item) {
    const newValue = !item.value;
    emit('toggle', { item: item, value: newValue });
}
</script>
