<template>
        <focus-trap v-model="trap" :initial-focus="() => $refs.buttonB">
            <div class="studip-dialog" @keydown.esc="closeDialog">
                <transition name="dialog-fade">
                    <div class="studip-dialog-backdrop">
                        <vue-resizeable
                            class="resizable"
                            style="position: absolute"
                            ref="resizableComponent"
                            :dragSelector="dragSelector"
                            :active="handlers"
                            :fit-parent="fit"
                            :left="left"
                            :top="top"
                            :width="currentWidth"
                            :height="currentHeight"
                            :min-width="checkEmpty(minW)"
                            :min-height="checkEmpty(minH)"
                            @mount="initSize"
                            @resize:move="resizeHandler"
                            @resize:start="resizeHandler"
                            @resize:end="resizeHandler"
                            @drag:move="resizeHandler"
                            @drag:start="resizeHandler"
                            @drag:end="resizeHandler"
                        >
                            <div
                                :style="{ width: dialogWidth, height: dialogHeight, top: top, left: left }"
                                :class="{ 'studip-dialog-warning': question, 'studip-dialog-alert': alert }"
                                class="studip-dialog-body"
                                role="dialog"
                                :aria-modal="'true'"
                                :aria-labelledby="dialogTitleId"
                                :aria-describedby="dialogDescId"
                                ref="dialog"
                            >
                                <header
                                    class="studip-dialog-header"
                                >
                                    <span :id="dialogTitleId" class="studip-dialog-title" :title="dialogTitle">
                                        {{ dialogTitle }}
                                    </span>
                                    <slot name="dialogHeader"></slot>
                                    <span
                                        :aria-label="$gettext('Diesen Dialog schließen')"
                                        class="studip-dialog-close-button"
                                        :style="dialogCloseIcon"
                                        :title="$gettext('Schließen')"
                                        @click="closeDialog"
                                    >
                                    </span>
                                </header>
                                <section
                                    :id="dialogDescId"
                                    :style="{ height: contentHeight }"
                                    class="studip-dialog-content"
                                >
                                    <slot name="dialogContent"></slot>
                                    <div v-if="message">{{ message }}</div>
                                    <div v-if="question">{{ question }}</div>
                                    <div v-if="alert">{{ alert }}</div>
                                </section>
                                <footer class="studip-dialog-footer" ref="footer">
                                    <button
                                        v-if="buttonA"
                                        :title="buttonA.text"
                                        :class="[buttonA.class]"
                                        class="button"
                                        type="button"
                                        @click="confirmDialog"
                                    >
                                        {{ buttonA.text }}
                                    </button>
                                    <slot name="dialogButtons"></slot>
                                    <button
                                        v-if="buttonB"
                                        :title="buttonB.text"
                                        :class="[buttonB.class]"
                                        class="button"
                                        type="button"
                                        ref="buttonB"
                                        @click="closeDialog"
                                    >
                                        {{ buttonB.text }}
                                    </button>
                                </footer>
                            </div>
                        </vue-resizeable>
                    </div>
                </transition>
            </div>
        </focus-trap>
</template>

<script>
import { FocusTrap } from 'focus-trap-vue';
import VueResizeable from 'vue-resizable';
let uuid = 0;
const dialogPadding = 3;

export default {
    name: 'studip-dialog',
    components: {
        FocusTrap,
        VueResizeable,
    },
    props: {
        height: {type: String, default: '300'},
        width: {type: String, default: '450'},
        title: String,
        confirmText: String,
        closeText: String,
        confirmClass: String,
        closeClass: String,
        question: String,
        alert: String,
        message: String,
    },
    data() {
        const dialogId = uuid++;

        return {
            trap: true,
            dialogTitleId: `studip-dialog-title-${dialogId}`,
            dialogDescId: `studip-dialog-desc-${dialogId}`,

            currentWidth: 450,
            currentHeight: 300,
            minW: 100,
            minH: 100,
            left: 0,
            top: 0,
            dragSelector: ".studip-dialog-header",
            handlers: ["r", "rb", "b", "lb", "l", "lt", "t", "rt"],
            fit: false,
            footerHeight: 68,
        };
    },
    computed: {
        buttonA() {
            let button = false;
            if (this.message) {
                return false;
            }
            if (this.question || this.alert) {
                button = {};
                button.text = this.$gettext('Ja');
                button.class = 'accept';
            }
            if (this.confirmText) {
                button = {};
                button.text = this.confirmText;
                button.class = this.confirmClass;
            }

            return button;
        },
        buttonB() {
            let button = false;
            if (this.message) {
                button = {};
                button.text = this.$gettext('Ok');
                button.class = '';
            }
            if (this.question || this.alert) {
                button = {};
                button.text = this.$gettext('Nein');
                button.class = 'cancel';
            }
            if (this.closeText) {
                button = {};
                button.text = this.closeText;
                if (this.closeClass) {
                    button.class = this.closeClass;
                } else {
                    button.class = 'cancel';
                }
            }

            return button;
        },
        dialogTitle() {
            if (this.title) {
                return this.title;
            }
            if (this.alert || this.question) {
                return this.$gettext('Bitte bestätigen Sie die Aktion');
            }
            if (this.message) {
                return this.$gettext('Information');
            }
        },
        dialogWidth() {
            return this.currentWidth ? (this.currentWidth - dialogPadding * 4) + 'px' : 'unset';
        },
        dialogHeight() {
            return this.currentHeight ? (this.currentHeight - dialogPadding * 4) + 'px' : 'unset';
        },
        contentHeight() {
            return this.currentHeight ? this.currentHeight - this.footerHeight + 'px' : 'unset';
        },

        dialogCloseIcon() {
            return `background-image: url('` +
                STUDIP.ASSETS_URL + `/images/icons/white/decline.svg')`
        }
    },
    methods: {
        closeDialog() {
            this.$emit('close');
        },
        confirmDialog() {
            this.$emit('confirm');
        },
        initSize() {
            this.currentWidth = parseInt(this.width, 10);
            this.currentHeight = parseInt(this.height, 10);
            if (window.outerWidth > this.currentWidth) {
                this.left = (window.outerWidth - this.currentWidth) / 2;
            } else {
                this.left = 5;
                this.currentWidth = window.outerWidth - 16;
            }

            this.top = (window.outerHeight - this.currentHeight) / 2;
            this.footerHeight = this.$refs.footer.offsetHeight;
        },
        resizeHandler(data) {
            this.currentWidth = data.width;
            this.currentHeight = data.height;
            this.left = data.left;
            this.top = data.top;
        },

        checkEmpty(value) {
            return typeof value !== "number" ? 0 : value;
        }
    }
};
</script>
