<template>
    <div class="oc--context-menu" ref="menuWrapper">
        <button
            type="button"
            class="button oc--context-menu__button"
            :class="buttonClass"
            :aria-expanded="isOpen.toString()"
            aria-haspopup="true"
            @click="toggle"
            tabindex="-1"
        >
            <slot name="button">Menü öffnen</slot>
        </button>

        <div
            v-if="isOpen && !useDrawer"
            class="oc--context-menu__panel"
            :class="{ 'align-right': isRightAligned }"
            role="menu"
            :aria-label="title || 'Dropdown-Menü'"
            @keydown="handleKeyDown"
            tabindex="0"
        >
            <div v-if="title" class="oc--context-menu__menu-title" role="presentation">{{ title }}</div>

            <slot name="content" />
        </div>

        <transition name="drawer">
            <div v-if="isOpen && useDrawer" class="oc--context-menu__drawer">
                <div class="oc--context-menu__overlay" @click="close"></div>
                <div class="oc--context-menu__drawer-panel" role="menu">
                    <div v-if="title" class="oc--context-menu__menu-title">
                        <span>{{ title }}</span>
                        <button @click="close">
                            <StudipIcon shape="decline" :size="20" />
                        </button>
                    </div>
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import StudipIcon from '@studip/StudipIcon';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    buttonClass: {
        type: [String, Object, Array],
        default: '',
    },
    responsive: { type: Boolean, default: true },
});

const emit = defineEmits(['select', 'toggle']);

const isOpen = ref(false);
const isRightAligned = ref(false);
const hasAdjusted = ref(false);
const menuWrapper = ref(null);
const useDrawer = ref(false);

function toggle() {
    isOpen.value = !isOpen.value;
}

function close() {
    isOpen.value = false;
}

function adjustPosition() {
    if (!menuWrapper.value) return;
    const menu = menuWrapper.value.querySelector('.oc--context-menu__panel');
    if (!menu) return;

    const rect = menu.getBoundingClientRect();
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth;

    isRightAligned.value = rect.right > viewportWidth;
}

function checkResponsiveClass() {
    if (props.responsive) {
        useDrawer.value = document.documentElement.classList.contains('responsive-display');
    }
}

watch(isOpen, (open) => {
    if (open && !hasAdjusted.value) {
        nextTick(() => {
            requestAnimationFrame(() => {
                adjustPosition();
                hasAdjusted.value = true;

                const panel = menuWrapper.value.querySelector('.oc--context-menu__panel');
                if (panel) panel.focus();
            });
        });
    }
});

function handleClickOutside(event) {
    if (menuWrapper.value && !menuWrapper.value.contains(event.target)) {
        close();
    }
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
    checkResponsiveClass();
    if (props.responsive) {
        const observer = new MutationObserver(() => checkResponsiveClass());
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

defineExpose({ close, isOpen });
</script>
