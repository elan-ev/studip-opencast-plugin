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
                        :required="setting.required"
                        :disabled="disabled"
                    >
                    {{ $gettext('Ja') }}
                </label>

                <label>
                    <input type="radio" value="0"
                        :name="setting.name"
                        :checked="setting.value != true"
                        @change='setValue(false)'
                        :required="setting.required"
                        :disabled="disabled"
                    >
                    {{ $gettext('Nein') }}
                </label>
            </section>
        </span>

        <label v-if="setting.type == 'string' && !setting.options && !isI18N(setting)">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>
            <input type="text"
                :name="setting.name"
                :placeholder="setting.placeholder"
                v-model="setting.value"
                @change="setValue(setting.value)"
                :required="setting.required"
                :disabled="disabled"
            >
        </label>

        <label v-if="(setting.type == 'string' || setting.type == 'integer') && setting.options && !isI18N(setting)">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>
            <studip-select
                v-model="setting.value"
                :options="setting.options"
                :reduce="(option) => option.value"
                label="description"
                @option:selected="setValue(setting.value)"/>
        </label>

        <label v-if="setting.type == 'integer' && !setting.options">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>
            <input type="number"
                :name="setting.name"
                :placeholder="setting.placeholder"
                v-model="setting.value"
                @change="setValue(setting.value)"
                :required="setting.required"
                :disabled="disabled"
            >
        </label>

        <label v-if="setting.type == 'password'">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>


            <div class="input-group files-search oc--admin-password">

                <input :type="passwordVisible ? 'text' : 'password'"
                    @change="setValue(password)"
                    @focusin="passwordFocused=true"
                    @focusout="passwordFocused=false"
                    v-model="password"
                    :placeholder="setting.placeholder"
                    :required="setting.required"
                    :disabled="disabled"
                >

                <span class="input-group-append ">
                    <button class="button" @click.stop="togglePasswordVis($event)">
                        <StudipIcon shape="visibility-visible" role="clickable" v-if="passwordVisible"/>
                        <StudipIcon shape="visibility-invisible" role="clickable" v-if="!passwordVisible"/>
                    </button>
                </span>
            </div>
        </label>


        <label v-if="setting.type == 'string' && isI18N(setting) && !disabled">
            <span :class="{
                required: setting.required
            }">
                {{ setting.description }}
            </span>

            <I18NText :text="setting.value"
                :languages="languages"
                @updateValue="setValue"
            />
        </label>
    </span>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';
import StudipSelect from '@studip/StudipSelect';
import I18NText from "@/components/Config/I18NText";

export default {
    name: "ConfigOption",

    props: ['setting', 'languages', 'disabled'],

    components: {
        StudipIcon, StudipSelect, I18NText
    },

    data() {
        return {
            passwordInput: '', // Make password initially empty, so that an empty input can be detected
            passwordVisible: false,
            passwordFocused: false
        }
    },

    computed: {
        password: {
            get() {
                if (!this.passwordVisible) {
                    if (this.passwordFocused && this.passwordInput == '*****') {
                        this.passwordInput = '';
                    }
                    else if (!this.passwordFocused && this.setting.value) {
                        this.passwordInput = '*****';
                    }
                }

                return this.passwordInput;
            },
            set(newValue) {
                this.passwordInput = newValue;
            }
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
                this.passwordVisible = false;
            }
        },

        isI18N(setting)
        {
            if (
                setting.name == 'OPENCAST_UPLOAD_INFO_TEXT_BODY'
                || setting.name == 'OPENCAST_TOS'
            ) {
                return true;
            }

            return false;
        }
    }
}
</script>
