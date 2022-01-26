<template>
    <span>
        <span v-if="setting.type == 'boolean'">
            <div>
                <span>
                    {{ setting.description }}
                </span>
            </div>

            <section class="hgroup size-s">
                <label>
                    <input type="radio" value="1"
                        :name="setting.name"
                        :checked="setting.value == true"
                        @change='setValue(true)'
                    >
                    <translate>
                        Ja
                    </translate>
                </label>

                <label>
                    <input type="radio" value="0"
                        :name="setting.name"
                        :checked="setting.value != true"
                        @change='setValue(false)'
                    >
                    <translate>
                        Nein
                    </translate>
                </label>
            </section>
        </span>

        <label v-if="setting.type == 'string'">
            {{ setting.description }}
            <input type="text"
                :name="setting.name" :value="setting.value"
                @change='setValue(this.value)'>
        </label>
    </span>
</template>

<script>

export default {
    name: "ConfigOption",

    props: ['setting'],

    methods: {
        setValue(newValue) {
            this.$emit('updateValue', this.setting, newValue);
        }
    }
}
</script>
