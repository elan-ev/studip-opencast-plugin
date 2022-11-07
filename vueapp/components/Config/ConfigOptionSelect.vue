<template>
    <div>
        <span :class="{
            required: options.required
        }">
            {{ description }}
        </span>
    </div>
    <div>
        <studip-select
            v-model="selected"
            :options="options" 
            label="description"/>
    </div>
</template>

<script>
import StudipSelect from '@studip/StudipSelect';

export default {
    name: "ConfigOptionSelect",
    
    components: {
        StudipSelect
    },
    
    props: ['options', 'description'],
    
    computed: {
        selected: {
            get() {
                for (let id in this.options) {
                    if (this.options[id].value) {
                        return this.options[id]
                    }
                }
            },
            set(option) {
                for (let id in this.options) {
                    if (option.name === this.options[id].name) {
                        this.$emit('updateValue', this.options[id], true);
                    }
                    else {
                        this.$emit('updateValue', this.options[id], false);
                    }
                }
            }
        }
    }
}
</script>