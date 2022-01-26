<template>
    <div>
        <label>
            <div class="i18n_group">
                <div v-for="lang in languages"
                    class="i18n"
                    :style="{
                        display: lang.name != selectedLang.name ? 'none' : 'block'
                    }"
                    :data-lang="lang.name"
                    :data-icon="`url(` + getLangImage(lang) + `)`">
                    <textarea
                        v-model="text[lang.name]"
                        :id="`studip_wysiwyg_` + lang.name"
                        :ref="`studip_wysiwyg_` + lang.name"
                        class="studip-wysiwyg"
                        @input="updateValue($event.target.value)"
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

    props: ['content'],

    data() {
        return {
            languages: [{
                'name': 'Deutsch',
                'picture': 'lang_de.gif'
            },{
                'name': 'English',
                'picture': 'lang_en.gif'
            }],

            selectedLang: {
                'name': 'Deutsch',
                'picture': 'lang_de.gif'
            },
            text: {
                'Deutsch': 'deutscher Text',
                'English': 'english text'
            },
            fallbackActive: false,
            wysiwyg_editor: null
        }
    },

    mounted() {
        let ckeInit = this.initCKE();
        if (!ckeInit) {
            this.fallbackActive = true;
        }
    },


    methods: {
        getLangImage(lang) {
            return ASSETS_URL + 'images/languages/' + lang.picture;
        },

        async initCKE() {
            if (!STUDIP.wysiwyg_enabled) {
                return false;
            }

            let view = this;

            if (view.wysiwyg_editor !== null) {
                await view.wysiwyg_editor.destroy();
            }

            let textarea = view.$refs['studip_wysiwyg_' + view.selectedLang.name][0];


            await STUDIP.wysiwyg.replace(textarea);
            view.wysiwyg_editor = CKEDITOR.instances[textarea.id];

            view.wysiwyg_editor.on('blur', function() {
                //console.log('cke blur');
            });

            view.wysiwyg_editor.on('change', function() {
                view.text[view.selectedLang.name] = view.wysiwyg_editor.getData();
            });

            return true;
        },

        updateValue(value) {
            if (this.fallbackActive) {
                this.$emit('input', value);
            }
        }
    }
}
</script>
