<template>
    <div class="oc--progress" :class="{'oc--minimal-progress': minimal === true}">
        <div class="oc--progress-bar" :style="`width: ` + progress + `%`">
            <span v-if="minimal !== true">
                {{ progress }}%
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProgressBar',

    props: ['progress', 'minimal']
}
</script>
