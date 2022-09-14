<template>
    <div>
        <li class="oc--flex-episode">
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
                <div>
                    <h2 class="oc--metadata-title oc--loadingbar">

                    </h2>
                    <div class="oc--loadingbar" style="width: 350px;"></div>
                    <div class="oc--loadingbar" style="width: 240px;"></div>
                </div>
            </div>
        </li>
    </div>
</template>

<script>

export default {
    name: "EmptyEpisode",

    data() {
        return {
            preview: window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg'
        }
    },
}
</script>
