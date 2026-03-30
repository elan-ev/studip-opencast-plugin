<template>
    <tr class="oc--episode">
        <td class="oc--playercontainer">
            <a>
                <span class="oc--previewimage">
                    <img class="oc--previewimage" :src="preview" height="200"/>
                    <img class="oc--image-button oc--play-button" :src="play">
                </span>
            </a>
        </td>
        <td :colspan="numberOfColumns - 1" class="oc--metadata--empty">
            <div>
                <h2 class="oc--loadingbar">

                </h2>
                <div class="oc--loadingbar" style="width: 350px;"></div>
                <div class="oc--loadingbar" style="width: 240px;"></div>
            </div>
        </td>
    </tr>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    simple_config_list: Object,
    numberOfColumns: {
        type: Number,
        required: true
    },
});

// Computed.
const preview = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/default-preview.png';
});

const play = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/play.svg';
});
</script>
