<template>
    <span>
        <label v-if="mappedSetting.type === 'boolean'">
            <span>
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                ></span>
            </span>
            <LayoutSwitch
                :model-value="setting.value"
                @update:model-value="setValue"
                :required="mappedSetting.required"
                :disabled="disabled"
            />
        </label>

        <label v-if="mappedSetting.type === 'string' && !mappedSetting.options && !mappedSetting.i18n">
            <span :class="{ required: mappedSetting.required }">
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                >
                </span>
            </span>
            <input
                type="text"
                :name="mappedSetting.name"
                :placeholder="mappedSetting.placeholder"
                v-model="localValue"
                @change="setValue(mappedSetting.value)"
                :required="mappedSetting.required"
                :disabled="disabled"
            />
        </label>

        <label
            v-if="
                (mappedSetting.type === 'string' || mappedSetting.type === 'integer') &&
                mappedSetting.options &&
                !mappedSetting.i18n
            "
        >
            <span :class="{ required: mappedSetting.required }">
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                >
                </span>
            </span>
            <studip-select
                v-model="localValue"
                :clearable="false"
                :options="mappedSetting.options"
                :reduce="(option) => option.value"
                label="description"
                @option:selected="setValue(mappedSetting.value)"
            />
        </label>

        <label v-if="mappedSetting.type === 'integer' && !mappedSetting.options">
            <span :class="{ required: mappedSetting.required }">
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                >
                </span>
            </span>
            <input
                type="number"
                :name="mappedSetting.name"
                :placeholder="mappedSetting.placeholder"
                v-model="localValue"
                @change="setValue(mappedSetting.value)"
                :required="mappedSetting.required"
                :disabled="disabled"
            />
        </label>

        <label v-if="mappedSetting.type === 'password'">
            <span :class="{ required: mappedSetting.required }">
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                >
                </span>
            </span>
            <div class="input-group files-search oc--admin-password">
                <input
                    :type="passwordVisible ? 'text' : 'password'"
                    v-model="password"
                    @change="setValue(password)"
                    @focusin="passwordFocused = true"
                    @focusout="passwordFocused = false"
                    :placeholder="mappedSetting.placeholder"
                    :required="mappedSetting.required"
                    :disabled="disabled"
                />
                <span class="input-group-append">
                    <button class="button" @click.stop="togglePasswordVis">
                        <StudipIcon shape="visibility-visible" role="clickable" v-if="passwordVisible" />
                        <StudipIcon shape="visibility-invisible" role="clickable" v-if="!passwordVisible" />
                    </button>
                </span>
            </div>
        </label>

        <label v-if="mappedSetting.type === 'string' && mappedSetting.i18n && !disabled">
            <span :class="{ required: mappedSetting.required }">
                {{ mappedSetting.label }}
                <span
                    v-if="mappedSetting.tooltip !== ''"
                    class="as-link tooltip tooltip-icon"
                    tabindex="0"
                    :data-tooltip="mappedSetting.tooltip"
                    :aria-label="mappedSetting.tooltip"
                >
                </span>
            </span>
            <I18NText :text="mappedSetting.value" :languages="languages" @updateValue="setValue" />
        </label>
    </span>
</template>

<script setup>
import { useSetting } from '@/composables/useSetting';
import { ref, computed, getCurrentInstance, watch } from 'vue';
import StudipIcon from '@studip/StudipIcon.vue';
import StudipSelect from '@studip/StudipSelect';
import LayoutSwitch from '@/components/Layouts/LayoutSwitch.vue';
import I18NText from '@/components/Config/I18NText';

const props = defineProps({
    setting: { type: Object, required: true },
    languages: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    useDescriptionAsLabel: { type: Boolean, default: false },
});
const emit = defineEmits(['updateValue']);

const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;

const passwordInput = ref('');
const passwordVisible = ref(false);
const passwordFocused = ref(false);

const { mapSetting } = useSetting();

const mappedSetting = mapSetting(props.setting, props.useDescriptionAsLabel);

const localValue = ref(props.setting.value);

const password = computed({
    get() {
        if (passwordVisible.value) {
            return props.setting.value || '';
        }
        if (passwordFocused.value) {
            return passwordInput.value;
        }
        return props.setting.value ? '*****' : '';
    },
    set(val) {
        passwordInput.value = val;
    },
});
const isI18N = computed(() => {
    return props.setting.name === 'OPENCAST_UPLOAD_INFO_TEXT_BODY' || props.setting.name === 'OPENCAST_TOS';
});

function setValue(val) {
    emit('updateValue', props.setting, val);
}

function togglePasswordVis() {
    if (!passwordVisible.value) {
        password.value = props.setting.value;
        passwordVisible.value = true;
    } else {
        passwordVisible.value = false;
    }
}
watch(
    () => props.setting.value,
    (val) => {
        localValue.value = val;
    }
);
</script>
