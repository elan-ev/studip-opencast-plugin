<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: fragment == 'episodes'
                }">
                    <router-link :to="{ name: 'episodes' }">
                        Aufzeichnungen
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'scheduler'
                }">
                    <router-link :to="{ name: 'scheduler' }">
                        Aufzeichnungen planen
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'manager'
                }">
                    <router-link :to="{ name: 'manager' }">
                        Verwaltung
                    </router-link>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';
import EpisodeAdd from '@/components/Episodes/EpisodeAdd'

export default {
    name: 'NavigationWidget',
    components: {
        StudipIcon, EpisodeAdd
    },
    data() {
        return {
            showAddDialog: false
        }
    },

    computed: {
        fragment() {
            return this.$route.name;
        }
    }
}
</script>
