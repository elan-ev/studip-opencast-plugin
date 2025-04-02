<template>
    <div>
        <Slider
            v-bind="compiled_attrs"
            class="oc--slider"
            v-model="value"
            @change="updateValue"
        />
    </div>
</template>

<script>
import Slider from '@vueform/slider'

export default {
    name: 'studip-slider',
    components: {
        Slider,
    },

    data() {
        return {
            value: 0
        }
    },

    mounted () {
        this.getValue();
    },

    computed: {
        compiled_attrs() {
            let attrs = { ... this.$attrs };
            if (this.$attrs?.callbackParams) {
                delete attrs.callbackParams;
            }
            return attrs;
        }
    },

    methods: {
        updateValue(val) {
            let params = {};
            if (this.$attrs?.callbackParams) {
                params = this.$attrs.callbackParams;
            }
            params.value = val;
            this.$emit('sliderChanged', params);
        },

        getValue() {
            if (this.compiled_attrs?.value) {
                this.value = this.compiled_attrs.value;
            }
        }
    },
}
</script>
<style src="@vueform/slider/themes/default.css"></style>