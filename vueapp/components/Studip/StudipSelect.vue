<template>
    <v-select
        ref="select"
        @input="updateValue"
        v-bind="{ ...$props, ...$attrs }"
        :calculate-position="withPopper"
        class="studip-v-select"
        append-to-body
    >
        <template v-for="(index, name) in $slots" v-slot:[name]="data">
            <slot :name="name" v-bind="data"></slot>
        </template>
        <template #open-indicator="{ selectAttributes }">
            <span v-bind="selectAttributes"><studip-icon shape="arr_1down" :size="10" /></span>
        </template>
    </v-select>
</template>

<script>
import vSelect from 'vue3-select';
import { createPopper } from '@popperjs/core';
import 'vue3-select/dist/vue3-select.css';
import StudipIcon from './StudipIcon.vue';
export default {
    name: 'studip-select',
    inheritAttrs: false,
    components: {
        vSelect,
        StudipIcon
    },
    props: {
        maxHeight: {
            type: String,
            default: '12em',
        },
    },
    methods: {
        updateValue(val) {
            this.$emit('input', val);
        },
        withPopper(dropdownList, component, { width }) {
            if (component.$el?.offsetParent.classList.contains('studip-dialog-content')) {
                dropdownList.classList.add('studip-v-select-ul-dialog');
            }
            dropdownList.style.width = width;
            dropdownList.style.maxHeight = this.maxHeight;
            dropdownList.classList.add('studip-v-select-detachted-ul');
            let dropdownListHeight =
                parseFloat(this.getStyleValue(dropdownList, 'height')) +
                parseFloat(this.getStyleValue(dropdownList, 'paddingTop')) +
                parseFloat(this.getStyleValue(dropdownList, 'paddingBottom'));
            const popper = createPopper(component.$refs.toggle, dropdownList, {
                //placement: this.calculatePlacement(dropdownListHeight), TODO this is broken
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, -1],
                        },
                    },
                    {
                        name: 'toggleClass',
                        enabled: true,
                        phase: 'write',
                        fn({ state }) {
                            component.$refs.dropdownMenu.classList.toggle(
                                'studip-v-select-ul-drop-up',
                                state.placement === 'top'
                            );
                            component.$el.classList.toggle('studip-v-select-drop-up', state.placement === 'top');
                        },
                    },
                ],
            });
            return () => popper.destroy();
        },
        calculatePlacement(dropdownListHeight) {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            let selectBottom = Math.ceil(this.$refs.select.$el.getBoundingClientRect().bottom + scrollTop);
            let totalExpandedList = selectBottom + dropdownListHeight;
            let totalDocHeight = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );
            let footerHeight = 0;
            if (window.OpencastPlugin.STUDIP_VERSION >= 5.3) {
                footerHeight = document.getElementById('main-footer').offsetHeight;
            } else {
                footerHeight = document.getElementById('layout_footer').offsetHeight;
            }

            let functionalAreaHeight = totalDocHeight - footerHeight;
            return totalExpandedList >= functionalAreaHeight ? 'top' : 'bottom';
        },
        getStyleValue(element, styleProp) {
            let result = '';
            if (window.getComputedStyle) {
                result = getComputedStyle(element)[styleProp];
            } else if (element.currentStyle) {
                result = element.currentStyle[styleProp];
            }
            return result;
        },
    },
};
</script>
