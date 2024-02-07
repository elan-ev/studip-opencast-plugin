<template>
    <ul class="widget-list widget-links sidebar-views">
        <li :class="active">
            <a href="#">
                {{ $gettext('Tabellarische Ansicht') }}
            </a>
        </li>
        <li>
            <a href="#">
                {{ $gettext('Kachelansicht') }}
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
