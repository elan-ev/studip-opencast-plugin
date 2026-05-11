<template>
    <span>
        <studip-icon shape="tag" role="info"/>
        <span>{{ tag }}</span>
    </span>
</template>

<script setup>
const props = defineProps(['tag']);
</script>
