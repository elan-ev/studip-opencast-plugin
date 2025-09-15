<template>
    <div class="oc--tabs">
        <div class="oc--tabs-nav" :class="{ 'oc--tabs-nav--dropdown': useDropdown }">
            <template v-if="useDropdown">
                <select v-model.number="activeTab" @change="selectTab(activeTab)">
                    <option v-for="(tab, index) in tabs" :key="index" :value="index">
                        {{ tab.name }}
                    </option>
                </select>
            </template>
            <template v-else>
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
            </template>
        </div>
        <div class="oc--tabs-content" :style="{ 'min-height': minHeight + 'px' }">
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
        minHeight: { type: Number, default: 0 },
        responsive: { type: Boolean, default: false },
    },
    data() {
        return {
            activeTab: 0,
            tabs: [],
            useDropdown: false,
        };
    },
    methods: {
        selectTab(index) {
            if (index >= this.tabs.length || index < 0) {
                return;
            }
            this.activeTab = index;
            if (!this.useDropdown) {
                this.$refs['tabnav' + index][0].focus();
            }
        },
        addTab(tab) {
            if (!this.tabs.find((t) => t.name === tab.name && t.icon === tab.icon)) {
                this.tabs.push(tab);
            }
        },
        handleKeyEvent(e) {
            if (this.useDropdown) return;
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
        checkResponsiveClass() {
            this.useDropdown = document.documentElement.classList.contains('responsive-display');
        },
    },
    provide() {
        return {
            addTab: this.addTab,
            activeTab: () => this.activeTab,
        };
    },
    mounted() {
        if (this.responsive) {
            this.checkResponsiveClass();

            const observer = new MutationObserver(() => {
                this.checkResponsiveClass();
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });

            this._observer = observer;
        }
        this.$nextTick(() => {
            if (this.modelValue) {
                this.selectTab(this.modelValue);
            }
        });
    },
    beforeUnmount() {
        if (this._observer) {
            this._observer.disconnect();
        }
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
