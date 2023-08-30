<template>
    <span>
        <studip-icon shape="tag" role="info"/>
        <span>{{ tag }}</span>
    </span>
</template>

<script>

export default {
    name: 'Tag',

    components: {

    },

    props: ['tag']
}
</script>