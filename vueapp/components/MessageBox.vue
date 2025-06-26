<template>
    <div class="messagebox"
        :class="`messagebox_` + type">
        <div class="messagebox_buttons">
            <a class="close" href="#" title="Nachrichtenbox schliessen" @click.prevent="hide">
                <span>Nachrichtenbox schliessen</span>
            </a>
        </div>

        <div>
            <template v-if="html">
                <span v-if="html" class="messagebox-html-container" v-html="html"></span>
            </template>
            <template v-else>
                <slot>Message</slot>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "MessageBox",
    props: ['type', 'html'],
    methods: {
        hide() {
            this.$emit('hide');
        }
    }
};
</script>
