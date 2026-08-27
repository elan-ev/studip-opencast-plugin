<template>
    <div>
        <label>
            <div class="i18n_group">
                <div v-for="lang in languages"
                    class="i18n"
                    :style="{
                        display: lang.id != selectedLang.id ? 'none' : 'block'
                    }"
                    :data-lang="lang.id"
                    :data-icon="`url(` + getLangImage(lang) + `)`">
                    <input
                        v-if="type === 'text'"
                        type="text"
                        :ref="`studip_i18n_text_${lang.id}`"
                        :name="`studip_i18n_text_` + uuid + '_' + lang.id"
                        v-model="currentInputValue"
                        @keyup="updateInputValue"
                    >
                    <textarea
                        v-else-if="type === 'textarea'"
                        :value="currentText[lang.id]"
                        :id="`studip_wysiwyg_` + uuid + '_' + lang.id"
                        :ref="`studip_wysiwyg_${lang.id}`"
                        class="studip-wysiwyg"
                    >
                    </textarea>
                </div>

                <select tabindex="-1" class="i18n"
                    :style="`background-image: url(` + getLangImage(selectedLang) + `)`"
                    v-model="selectedLang"
                    @change="LanguageChange"
                >
                    <option
                        v-for="lang in languages"
                        :value="lang"
                        :style="`background-image: url(` + getLangImage(lang) + `)`"
                    >
                        {{ lang.name }}
                    </option>
                </select>
            </div>
        </label>
    </div>
</template>

<script>
import { toRaw } from "vue";

export default {
    name: 'I18NText',

    props: {
        text: {
            type: [String, Object]
        },
        languages: {
            type: Object
        },
        type: {
            type: String,
            required: false,
            default: "textarea"
        },
        callbackKey: {
            type: String,
            default: null
        }
    },

    emits: ['updateValue'],

    data() {
        return {
            currentInputValue: null,
            currentText: null,
            selectedLang: null,
            fallbackActive: false,
            wysiwyg_editor: {},
            uuid: Math.random().toString(16).slice(2),
            debounceTimeout: null,
        }
    },

    mounted() {
        this.initCKE();
    },

    beforeMount() {
        this.selectedLang = this.languages[Object.keys(this.languages)[0]];
        if (Object.keys(this.languages).includes('default')) {
            this.selectedLang = this.languages.default;
        }
        let json = {};
        if (typeof this.text === 'string') {
            try {
                json = JSON.parse(this.text);
            } catch (e) {
                json = {};
            }
        } else if (this.text) {
            json = { ...this.text };
        }

        this.currentText = json;
        this.currentInputValue = this.currentText[this.selectedLang.id] ?? null;
    },

    beforeUnmount() {
        if (this.debounceTimeout !== null) {
            clearTimeout(this.debounceTimeout);
        }
    },

    methods: {
        getLangImage(lang) {
            if (lang.id == 'default') {
                return `${STUDIP.ASSETS_URL}images/${lang.picture}`;
            }
            return OpencastPlugin.ASSETS_URL + 'images/languages/' + lang.picture;
        },

        async initCKE() {
            if (typeof STUDIP?.wysiwyg?.replace !== 'function') {
                return false;
            }

            if (this.type !== 'textarea') {
                return false;
            }

            const langId = this.selectedLang.id;
            const textarea = this.$refs['studip_wysiwyg_' + langId]?.[0];

            if (!textarea || this.wysiwyg_editor[langId]) {
                return Boolean(textarea);
            }

            const editor = await STUDIP.wysiwyg.replace(textarea);
            this.wysiwyg_editor[langId] = editor;

            // using toRaw to remove Vue proxys. They do not work well with CKEditor
            toRaw(editor).ui.focusTracker.on('change:isFocused', () => {
                this.updateValue(toRaw(editor).getData(), langId);
            });

            return true;
        },

        updateValue(value, langId = this.selectedLang.id)
        {
            this.currentText[langId] = value;

            // clean anything else besides languages
            for (let id in this.currentText) {
                if (!this.languages[id]) {
                    delete this.currentText[id];
                }
            }

            this.$emit('updateValue', JSON.stringify(this.currentText));
        },

        LanguageChange() {
            if (this.type == 'textarea') {
                this.initCKE();
                return;
            }
            this.currentInputValue = this.currentText[this.selectedLang.id] ?? null;
        },

        updateInputValue() {
            if (this.debounceTimeout !== null) {
                clearTimeout(this.debounceTimeout);
            }
            this.debounceTimeout = setTimeout(() => {
                this.$emit('updateValue', this.currentInputValue, this.selectedLang.id, this.callbackKey);
            }, 500);
        }
    }
}
</script>
