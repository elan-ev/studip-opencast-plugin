<template>
  <div class="scrolling-component" ref="scrollComponent">
    <VideoCard v-for="token in videosList" :video="videos[token]" :key="token"/>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideoCard from './VideoCard.vue';

export default {
  name: "VideosList",
  
  computed: {
    ...mapGetters(["videos", "videosList"]),
  },
  methods: {
    
  },
  components: {
    VideoCard
  },

  mounted() {
    this.$store.dispatch('loadVideos');
  }
};
</script>
