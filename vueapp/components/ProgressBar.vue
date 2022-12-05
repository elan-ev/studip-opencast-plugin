<template>
    <div class="oc--progress">
        <div class="oc--progress-bar" :style="`width: ` + progress + `%`">
            <span>
                {{ progress }}%
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProgressBar',

    props: ['progress']
}
</script>
