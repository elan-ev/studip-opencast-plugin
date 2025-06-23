<template>
    <div class="oc--content-bar">
        <header>
            <div class="oc--content-bar-header-buttons-left"><slot name="buttons-left"></slot></div>
            <div class="oc--content-bar-header-title"><slot name="title"></slot></div>
            <div class="oc--content-bar-header-buttons-right"><slot name="buttons-right"></slot></div>
        </header>
        <nav>
            <div class="oc--content-bar-nav"><slot name="nav"></slot></div>
            <div class="oc--content-bar-search"><slot name="search"></slot></div>
        </nav>
    </div>
</template>

<script setup>
</script>