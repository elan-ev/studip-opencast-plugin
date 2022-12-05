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
export default {
    name: 'ViewSwitch',
    computed: {
        tableView () {
            return this.getConfig(this.viewConfig) === 'table';
        },
        gridView () {
            return this.getConfig(this.viewConfig) === 'grid';
        },
    },
    methods: {
    },
};
</script>
