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
                        ref="`studip_i18n_text_` + lang.id"
                        :name="`studip_i18n_text_` + uuid + '_' + lang.id"
                        v-model="currentInputValue"
                        @keyup="updateInputValue"
                    >
                    <textarea
                        v-else-if="type === 'textarea'"
                        :value="currentText[lang.id]"
                        :id="`studip_wysiwyg_` + uuid + '_' + lang.id"
                        :ref="`studip_wysiwyg_` + lang.id"
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
            type: String
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
        let json;
        try {
            json = JSON.parse(this.text);
        } catch (e) {
            json = {}
        }

        this.currentText = json;
        this.currentInputValue = this.text[this.selectedLang.id];
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

        initCKE() {
            if (!STUDIP.wysiwyg_enabled) {
                return false;
            }

            if (this.type !== 'textarea') {
                return false;
            }

            let textarea = this.$refs['studip_wysiwyg_' + this.selectedLang.id][0];

            if (!this.wysiwyg_editor[this.selectedLang.id]) {
                this.checkEditor();
            }

            return true;
        },

        checkEditor()
        {
            let view = this;
            let textarea = this.$refs['studip_wysiwyg_' + this.selectedLang.id][0];

            if (!STUDIP.wysiwyg.getEditor(textarea)) {
                STUDIP.wysiwyg.replace(textarea);
                setTimeout(() => {
                    view.checkEditor()
                }, 500);
                return;
            }

            this.wysiwyg_editor[this.selectedLang.id] = STUDIP.wysiwyg.getEditor(textarea);

            // using toRaw to remove Vue proxys. They do not work well with CKEditor
            toRaw(this.wysiwyg_editor[this.selectedLang.id]).ui.focusTracker.on( 'change:isFocused', () => {
                view.updateValue(toRaw(view.wysiwyg_editor[view.selectedLang.id]).getData());
            });
        },

        updateValue(value)
        {
            this.currentText[this.selectedLang.id] = value;

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
            this.currentInputValue = this.text?.[this.selectedLang.id] ?? null;
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
