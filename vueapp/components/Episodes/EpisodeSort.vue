<template>
  <div>
    <select @change="sortHandler()" v-model="sel">
        <option v-for="(name, value) in options" 
          :key="name" :value="value"
          :selected="value==sel">
          {{name}}
        </option>
    </select>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: "EpisodeSort",

  data() {
    return {
      sel: '',

      options: {
        "titel_asc": "Titel aufsteigend",
        "titel_desc": "Titel absteigend",
        "created_asc": "Aufzeichnungsdatum aufsteigend",
        "created_desc": "Aufzeichnungsdatum absteigend"
      }
    }
  },

  computed: {
    ...mapGetters([
        'sort'
    ]),
  },

  methods: {
    sortHandler() {
      this.$store.dispatch('setSort', this.sel)
    }
  },

  mounted() {
    this.sel = this.sort
  }

}
</script>