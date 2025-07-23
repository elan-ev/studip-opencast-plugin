<template>
    <div class="oc--context-menu" ref="menuWrapper">
        <button
            type="button"
            class="button oc--context-menu__button"
            :aria-expanded="isOpen.toString()"
            aria-haspopup="true"
            @click="toggle"
            tabindex="-1"
        >
            <slot name="button">Menü öffnen</slot>
        </button>

        <div
            v-if="isOpen"
            class="oc--context-menu__panel"
            :class="{ 'align-right': isRightAligned }"
            role="menu"
            :aria-label="title || 'Kontextmenü'"
            @keydown="handleKeyDown"
        >
            <div class="oc--context-menu__menu-title" role="presentation">{{ title }}</div>

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
                        @click="
                            (e) => {
                                if (item.type !== 'link' && !item.disabled) onAction(item);
                                if (item.disabled) e.preventDefault();
                                if (item.type === 'link') close();
                            }
                        "
                        @keydown.enter.prevent="() => item.type !== 'link' && onAction(item)"
                    >
                        <div class="oc--context-menu__entry-content">
                            <studip-icon v-if="item.icon" :shape="item.icon" class="oc--context-menu__entry-icon" />
                            <div class="oc--context-menu__entry-texts">
                                <div class="oc--context-menu__entry-label">{{ item.label }}</div>
                                <div v-if="item.description" class="oc--context-menu__entry-description">
                                    {{ item.description }}
                                </div>
                            </div>
                        </div>
                    </component>
                </div>
            </template>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import StudipIcon from '@studip/StudipIcon.vue';

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

const emit = defineEmits(['select']);

const isOpen = ref(false);
const isRightAligned = ref(false);
const hasAdjusted = ref(false);
const menuWrapper = ref(null);

function toggle() {
    isOpen.value = !isOpen.value;
}

function close() {
    isOpen.value = false;
}

function onAction(item) {
    if (!item.disabled) {
        emit('select', item);
        close();
    }
}

function openAndFocusFirst() {
    isOpen.value = true;
    nextTick(() => {
        const first = menuWrapper.value.querySelector('oc--context-menu__menu-title');
        if (first) first.focus();
    });
}

function handleClickOutside(event) {
    if (menuWrapper.value && !menuWrapper.value.contains(event.target)) {
        close();
    }
}

function adjustPosition() {
    if (!menuWrapper.value) return;
    const menu = menuWrapper.value.querySelector('.oc--context-menu__panel');
    if (!menu) return;

    const rect = menu.getBoundingClientRect();
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth;

    isRightAligned.value = rect.right > viewportWidth;
}

function handleKeyDown(event) {
    if (!isOpen.value) return;

    const focusableSelectors = '.oc--context-menu__entry:not([disabled])';
    const focusableElements = menuWrapper.value.querySelectorAll(focusableSelectors);
    if (focusableElements.length === 0) return;

    const firstEl = focusableElements[0];
    const lastEl = focusableElements[focusableElements.length - 1];
    const activeEl = document.activeElement;

    if (event.key === 'Tab') {
        if (event.shiftKey) {
            if (activeEl === firstEl) {
                event.preventDefault();
                lastEl.focus();
            }
        } else {
            if (activeEl === lastEl) {
                event.preventDefault();
                firstEl.focus();
            }
        }
    } else if (event.key === 'Escape' || event.key === 'Esc') {
        event.preventDefault();
        close();
        // Fokus zurück auf Button:
        const button = menuWrapper.value.querySelector('.oc--context-menu__button');
        if (button) button.focus();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

watch(isOpen, (open) => {
    if (open && !hasAdjusted.value) {
        nextTick(() => {
            requestAnimationFrame(() => {
                adjustPosition();
                hasAdjusted.value = true;
                // Fokus auf das Panel setzen, nicht auf ein Item
                const panel = menuWrapper.value.querySelector('.oc--context-menu__panel');
                if (panel) panel.focus();
            });
        });
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
