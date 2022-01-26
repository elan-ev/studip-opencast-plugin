<template>
    <div>
        <div>
            <span v-translate>
                Zu bearbeitende Sprache auswählen
            </span>
        </div>

        <section class="hgroup size-s">
            <label v-for="lang in languages">
                <input type="radio"
                    :name="content.name + '_lang'" :value="lang"
                    v-model="selectedLang"
                >
                <img
                    class="oc--lang"
                    :src="getLangImage(lang)"
                    width="24px"
                >
            </label>
        </section>

        <!--<studip-wysiwyg v-model="currentText"></studip-wysiwyg>-->
    </div>
</template>

<script>


export default {
    name: 'I18NText',

    props: ['content'],

    data() {
        return {
            languages: ['de', 'en'],
            selectedLang: 'de',
            currentText: 'test'
        }
    },

    methods: {
        getLangImage(lang) {
            return ASSETS_URL + 'images/languages/lang_' + lang + '.gif';
        }
    }
}
</script>
