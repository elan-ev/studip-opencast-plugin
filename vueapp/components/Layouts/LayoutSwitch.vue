<template>
  <span class="oc--switch">
    <input
      type="checkbox"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      :disabled="disabled"
      :aria-checked="(modelValue ?? false).toString()"
      role="switch"
    />
    <span class="slider"></span>
  </span>
</template>

<script>
export default {
  name: "OcSwitch",
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["update:modelValue"],
};
</script>