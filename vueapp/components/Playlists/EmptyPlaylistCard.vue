<template>
    <div>
        <li>
            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a>
                        <span class="oc--previewimage">
                            <img class="oc--previewimage" :src="preview" height="200"/>
                            <img class="oc--playbutton" :src="play">
                        </span>
                    </a>
                </div>
            </div>
            <div class="oc--metadata oc--metadata--empty">
                <h2 class="oc--metadata-title oc--loadingbar">

                </h2>
                <ul class="oc--metadata-content">
                    <li class="oc--loadingbar" style="width: 320px;"></li>
                    <li class="oc--loadingbar" style="width: 270px;"></li>
                    <li class="oc--loadingbar" style="width: 400px;"></li>
                    <li class="oc--loadingbar" style="width: 150px;"></li>
                </ul>
            </div>
        </li>
    </div>
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