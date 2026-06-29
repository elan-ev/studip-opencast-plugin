<template>
    <Teleport :to="targetSelector">
        <div v-if="visible" class="drawer-overlay" @click="emit('close')"></div>
        <div class="drawer-wrapper">
            <Transition name="drawer-slide">
                <div v-if="visible" class="drawer" :class="[wrapperClass, `drawer--${side}`]" :style="drawerStyle">
                    <header class="drawer__header">
                        <button
                            class="drawer__close"
                            @click="emit('close')"
                            :title="$gettext('Schließen')"
                            :aria-label="$gettext('Schließen')"
                        >
                            <StudipIcon shape="decline" :size="24" />
                        </button>
                    </header>
                    <div class="drawer__content">
                        <slot />
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup>
import StudipIcon from '@studip/StudipIcon.vue';
import { computed } from 'vue';
const emit = defineEmits(['close']);
// Props mit Defaults
const props = defineProps({
    visible: Boolean,
    side: {
        type: String,
        default: 'left',
        validator: (val) => ['left', 'right'].includes(val),
    },
    width: {
        type: [Number, String],
        default: '270',
    },
    maxWidth: {
        type: Number,
        required: false,
    },
    attachTo: {
        type: [HTMLElement, null],
        default: null,
    },
    wrapperClass: {
        type: String,
        default: '',
    },
});

// Selector für Teleport
const targetSelector = computed(() => {
    if (props.attachTo instanceof HTMLElement) {
        return `#${props.attachTo.id}`;
    }
    return '.content'; // Fallback-Container-Klasse, z.B. Content-Div in Stud.IP
});

const drawerWidth = computed(() => {
    if (typeof props.width === 'number') {
        return props.width + 'px';
    }
    if (typeof props.width === 'string') {
        return props.width;
    }
    return '270px';
});

const drawerStyle = computed(() => {
    const style = {};
    if (typeof props.width === 'number') {
        style.width = props.width + 'px';
    } else {
        style.width = props.width;
    }

    if (props.maxWidth) {
        style.maxWidth = props.maxWidth + 'px';
    }

    style[props.side] = 0;

    return style;
});
</script>
