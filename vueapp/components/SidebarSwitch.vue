<template>
    <ul class="widget-list widget-links sidebar-views">
        <li :class="active">
            <a href="#">
                <translate>Tabellarische Ansicht</translate>
            </a>
        </li>
        <li>
            <a href="#">
                <translate>Kachelansicht</translate>
            </a>
        </li>
    </ul>
</template>

<script>
//import Sidebar from "../../assets/javascripts/lib/sidebar.js";

export default {
    name: 'my-courses-sidebar-switch',
    computed: {
        tableView () {
            return this.getConfig(this.viewConfig) === 'tables';
        },
        tilesView () {
            return this.getConfig(this.viewConfig) === 'tiles';
        },
    },
    methods: {
    },
};
</script>
