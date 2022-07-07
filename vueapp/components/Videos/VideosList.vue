<template>
  <div class="scrolling-component" ref="scrollComponent">
    {{ videos }}
    <div v-if="videos">
      <VideoCard v-for="(video, token) in videos" :video="video" :key="token"/>
    </div>
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
