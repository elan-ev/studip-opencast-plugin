<template>
    <div class="container" id="app-playlists">
        <h2>Wiedergabelisten</h2>
        <PlaylistList></PlaylistList>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import PlaylistList from '@/components/Playlists/PlaylistList'

export default {
    name: "Playlists",
    components: {
        PlaylistList
    }
};
</script>
