<template>
    <div>
        <form class="default">
            <fieldset>
                <legend v-translate>
                    Globale Einstellungen
                </legend>
                {{ config_list }}
                <ConfigOption v-for="settings in config.setinngs" :key="settings" />
            </fieldset>

            <footer>
                <StudipButton icon="accept" v-translate>
                    Einstellungen speichern
                </StudipButton>
            </footer>
        </form>
    </div>
</template>

<script>
export default {
    name: "GlobalOptions",
}
</script>
