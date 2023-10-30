<template>
    <tr class="oc--episode">
        <td v-if="showCheckbox">
            <input type="checkbox">
        </td>
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

<script>

export default {
    name: "EmptyEpisode",

    props: {
        'showCheckbox': {
            type: Boolean,
            default: false
        },
        'numberOfColumns': {
            type: Number,
            required: true
        },
    },

    data() {
        return {
            preview: window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },
}
</script>
