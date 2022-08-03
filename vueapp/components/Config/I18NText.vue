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
                        v-model="text[lang.id]"
                        :id="`studip_wysiwyg_` + lang.id"
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
export default {
    name: 'I18NText',

    props: {
        text: {
            type: Object
        },
        languages: {
            type: Object
        }
    },

    data() {
        return {
            currentText: null,
            selectedLang: null,
            fallbackActive: false,
            wysiwyg_editor: null
        }
    },

    mounted() {
        let ckeInit = this.initCKE();
    },

    beforeMount() {
        this.selectedLang = this.languages[Object.keys(this.languages)[0]];
        this.currentText  = this.text;
    },

    methods: {
        getLangImage(lang) {
            return OpencastPlugin.ASSETS_URL + 'images/languages/' + lang.picture;
        },

        async initCKE() {
            if (!STUDIP.wysiwyg_enabled) {
                return false;
            }

            let view = this;

            if (view.wysiwyg_editor !== null) {
                await view.wysiwyg_editor.destroy();
            }

            let textarea = view.$refs['studip_wysiwyg_' + view.selectedLang.id][0];


            await STUDIP.wysiwyg.replace(textarea);
            view.wysiwyg_editor = CKEDITOR.instances[textarea.id];

            view.wysiwyg_editor.on('blur', function() {
                //console.log('cke blur');
            });

            view.wysiwyg_editor.on('change', function() {
                view.updateValue(view.wysiwyg_editor.getData());
            });

            return true;
        },

        updateValue(value) {
            this.currentText[this.selectedLang.id] = value;
            this.$emit('input', this.currentText);
        }
    }
}
</script>
