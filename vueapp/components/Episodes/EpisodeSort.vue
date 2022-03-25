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
        "TITLE": "Titel: Alphabetisch",
        "TITLE_DESC": "Titel: Umgekehrt Alphabetisch",
        "DATE_CREATED": "Datum hochgeladen: Älteste zuerst",
        "DATE_CREATED_DESC": "Datum hochgeladen: Neueste zuerst"
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