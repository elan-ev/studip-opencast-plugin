<template>
    <span>
        <studip-icon shape="tag" role="info"/>
        <span>{{ tag }}</span>
    </span>
</template>

<script>
import StudipIcon from '@studip/StudipIcon';

export default {
    name: 'Tag',

    components: {
        StudipIcon
    },

    props: ['tag']
}
</script>