<template>
    <span>
        <span v-if="setting.type == 'boolean'">
            <div>
                <span :class="{
                    required: setting.required
                }">
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
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>
            <input type="text"
                :name="setting.name" :value="setting.value"
                :placeholder="setting.placeholder"
                @change='setValue(this.value)'>
        </label>

        <label v-if="setting.type == 'password'">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>


            <div class="input-group files-search oc--admin-password">

                <input :type="passwordVisible ?'text' : 'password'"
                    @change="updateHiddenPassword"
                    v-model="password"
                    :placeholder="setting.placeholder"
                >

                <span class="input-group-append ">
                    <button class="button" @click.stop="togglePasswordVis($event)">
                        <StudipIcon icon="visibility-visible" role="clickable" v-if="passwordVisible"/>
                        <StudipIcon icon="visibility-invisible" role="clickable" v-if="!passwordVisible"/>
                    </button>
                </span>
            </div>
        </label>
    </span>
</template>

<script>
import StudipIcon from '../StudipIcon.vue';

export default {
    name: "ConfigOption",

    props: ['setting'],

    components: {
        StudipIcon
    },

    data() {
        return {
            password: '*****',
            passwordVisible: false
        }
    },

    methods: {
        setValue(newValue) {
            this.$emit('updateValue', this.setting, newValue);
        },

        togglePasswordVis($event) {
            $event.preventDefault();

            if (!this.passwordVisible){
                this.password = this.setting.value;
                this.passwordVisible = true;
            } else {
                this.password = '*****'
                this.passwordVisible = false;
            }
        },

        updateHiddenPassword() {
            console.log('updateHiddenPassowrd', this.password);
            this.setValue(this.password);
        }
    }
}
</script>
