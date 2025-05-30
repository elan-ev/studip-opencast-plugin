<template>
    <div class="oc--tabs">
        <div class="oc--tabs-nav">
            <button
                v-for="(tab, index) in tabs"
                :key="index"
                :data-index="index"
                :class="[
                    activeTab === index ? 'is-active' : '',
                    tab.icon !== '' && tab.name !== '' ? 'oc--tabs-nav-icon-text-' + tab.icon : '',
                    tab.icon !== '' && tab.name === '' ? 'oc--tabs-nav-icon-solo-' + tab.icon : '',
                ]"
                :tabindex="activeTab === index ? 0 : -1"
                :aria-selected="activeTab === index"
                @click="selectTab(index)"
                @keydown="handleKeyEvent($event)"
                :ref="'tabnav' + index"
            >
                {{ tab.name }}
            </button>
        </div>
        <div class="oc--tabs-content" :style="{'min-height': minHeight + 'px'}">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Tabs',
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: Number },
        minHeight: { type: Number, default: 0 }
    },
    data() {
        return {
            activeTab: 0,
            tabs: [],
        };
    },
    methods: {
        selectTab(index) {
            if (index >= this.tabs.length || index < 0) {
                return;
            }
            this.activeTab = index;
            this.$refs['tabnav' + index][0].focus();
        },
        addTab(tab) {
            this.tabs.push(tab);
        },
        handleKeyEvent(e) {
            const index = parseInt(e.target.dataset.index);
            switch (e.keyCode) {
                case 37: // left
                case 38: // up
                    if (index !== 0) {
                        this.selectTab(index - 1);
                    } else {
                        this.selectTab(this.tabs.length - 1);
                    }
                    break;
                case 39: // right
                case 40: // down
                    if (index !== this.tabs.length - 1) {
                        this.selectTab(index + 1);
                    } else {
                        this.selectTab(0);
                    }
                    break;
                case 36: //pos1
                    this.selectTab(0);
                    break;
                case 35: //end
                    this.selectTab(this.tabs.length - 1);
                    break;
            }
        },
        getActiveTabElement() {
            return this.$refs['tabnav' + this.activeTab][0];
        },
    },
    provide() {
        return {
            addTab: this.addTab,
            activeTab: () => this.activeTab,
        };
    },
    mounted() {
        this.$nextTick(() => {
            if (this.modelValue) {
                this.selectTab(this.modelValue);
            }
        });
    },
    watch: {
        modelValue(newValue) {
            this.selectTab(newValue);
        },
        activeTab(newValue) {
            if (this.modelValue !== newValue) {
                this.$emit('update:modelValue', newValue);
            }
        },
    },
};
</script>
