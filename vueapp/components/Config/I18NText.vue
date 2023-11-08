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
                    <textarea
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
                    @change="initCKE()"
                >
                    <option
                        v-for="lang in languages"
                        :value="lang"
                        :style="`background-image: url(` + getLangImage(lang) + `)`"
                    >
                        {{ lang.name}}
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
        }
    },

    emits: ['updateValue'],

    data() {
        return {
            currentText: null,
            selectedLang: null,
            fallbackActive: false,
            wysiwyg_editor: {},
            uuid: Math.random().toString(16).slice(2)
        }
    },

    mounted() {
        this.initCKE();
    },

    beforeMount() {
        this.selectedLang = this.languages[Object.keys(this.languages)[0]];
        let json;
        try {
            json = JSON.parse(this.text);
        } catch (e) {
            json = {}
        }

        this.currentText = json;
    },

    methods: {
        getLangImage(lang) {
            return OpencastPlugin.ASSETS_URL + 'images/languages/' + lang.picture;
        },

        initCKE() {
            if (!STUDIP.wysiwyg_enabled) {
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
        }
    }
}
</script>
