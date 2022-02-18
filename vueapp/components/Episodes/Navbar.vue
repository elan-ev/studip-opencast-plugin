<template>
    <header class="cw-ribbon">
        <div class="cw-ribbon-wrapper-left">
            <nav class="cw-ribbon-nav">
                <button class="cw-ribbon-button">
                    <studip-icon icon="arr_1left" role="clickable" size="24"/>
                </button>
                <button class="cw-ribbon-button">
                    <studip-icon icon="arr_1right" role="clickable" size="24"/>
                </button>
            </nav>
            <div class="cw-ribbon-breadcrumb">Startseite</div>
        </div>
        <div class="cw-ribbon-wrapper-right">
            <button class="cw-ribbon-button">
                <studip-icon icon="table-of-contents" role="clickable" size="24"/>
            </button>
            <button class="cw-ribbon-button">
                <studip-icon icon="fullscreen-on" role="clickable" size="24"/>
            </button>
            <studip-action-menu :items="menuItems" class="cw-ribbon-action-menu"></studip-action-menu>
        </div>
    </header>
</template>

<script>
import StudipActionMenu from "@/components/StudipActionMenu"
import StudipIcon from '@/components/StudipIcon.vue';

export default {
    name: "Navbar",

    components: {
        StudipActionMenu,
        StudipIcon
    },

    computed: {
        menuItems() {
            let menu = [
                { id: 1, label: 'Informationen anzeigen', icon: 'info', emit: 'showInfo' },
                { id: 2, label: 'Lesezeichen setzen', icon: 'star', emit: 'setBookmark' },
            ];

            return menu;
        },
    }
}
</script>
