<template>
    <tr>
        <td></td>
        <td><span class="oc--loadingbar oc--loadingbar-title"></span></td>
        <td><span class="oc--loadingbar"></span></td>
        <td><span class="oc--loadingbar"></span></td>
        <td><span class="oc--loadingbar"></span></td>
        <td></td>
    </tr>
</template>

<script>

export default {
    name: "EmptyPlaylistCard",

    data() {
        return {
            preview: window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },
}
</script>