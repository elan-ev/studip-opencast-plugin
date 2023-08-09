<template>
    <nav v-if="shouldCollapse" class="action-menu">
        <button class="action-menu-icon" :title="title" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="action-menu-content">
            <div class="action-menu-title">
                {{ $gettext('Aktionen') }}
            </div>
            <ul class="action-menu-list">
                <li v-for="item in navigationItems" :key="item.id" class="action-menu-item">
                    <a v-if="item.type === 'link'" v-bind="linkAttributes(item)" v-on="linkEvents(item)" href="#prevent-default">
                        <studip-icon v-if="item.icon !== false" :shape="item.icon.shape" :role="item.icon.role"></studip-icon>
                        <span v-else class="action-menu-no-icon"></span>

                        {{ item.label }}
                    </a>
                    <label v-else-if="item.icon" class="undecorated" v-bind="linkAttributes(item)" v-on="linkEvents(item)">
                        <studip-icon :shape="item.icon.shape" :role="item.icon.role" :name="item.name" :title="item.label" v-bind="item.attributes ?? {}"></studip-icon>
                        {{ item.label }}
                    </label>
                    <template v-else>
                        <span class="action-menu-no-icon"></span>
                        <button :name="item.name" v-bind="Object.assign(item.attributes ?? {}, linkAttributes(item))" v-on="linkEvents(item)">
                            {{ item.label }}
                        </button>
                    </template>
                </li>
            </ul>
        </div>
    </nav>
    <nav v-else>
        <a v-for="item in navigationItems" :key="item.id" v-bind="linkAttributes(item)" v-on="linkEvents(item)" href="#prevent-default">
            <studip-icon :title="item.label" :shape="item.icon.shape" :role="item.icon.role" :size="20"></studip-icon>
        </a>
    </nav>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue'

export default {
    name: 'studip-action-menu',

    components: {
        StudipIcon
    },

    props: {
        items: Array,
        collapseAt: {
            default: null,
        },
        context: {
            type: String,
            default: ''
        }
    },
    data () {
        return {
            open: false
        };
    },
    methods: {
        linkAttributes (item) {
            let attributes = item.attributes;
            attributes.class = item.classes;

            if (item.disabled) {
                attributes.disabled = true;
            }

            if (item.url) {
                attributes.href = item.url;
            }

            return attributes;
        },
        linkEvents (item) {
            let events = {};
            if (item.emit) {
                events.click = (e) => {
                    e.preventDefault();
                    this.$emit.apply(this, [item.emit].concat(item.emitArguments));
                    this.close();
                };
            }
            return events;
        },
        close () {
            STUDIP.ActionMenu.closeAll();
        }
    },
    computed: {
        navigationItems () {
            return this.items.map((item) => {
                let classes = item.classes ?? '';
                if (item.disabled) {
                    classes += " action-menu-item-disabled";
                }
                return {
                    label: item.label,
                    url: item.url || '#',
                    emit: item.emit || false,
                    emitArguments: item.emitArguments || [],
                    icon: item.icon ? {
                        shape: item.icon,
                        role: item.disabled ? 'inactive' : 'clickable'
                    } : false,
                    type: item.type || 'link',
                    name: item.name ?? null,
                    classes: classes.trim(),
                    attributes: item.attributes || {},
                    disabled: item.disabled,
                };
            });
        },
        shouldCollapse () {
            const collapseAt = this.collapseAt ?? this.getStudipConfig('ACTIONMENU_THRESHOLD');

            if (collapseAt === false) {
                return false;
            }
            if (collapseAt === true) {
                return true;
            }
            return Number.parseInt(collapseAt) <= this.items.length;
        },
        title () {
            return this.context ? this.$gettextInterpolate(this.$gettext('Aktionsmenü für %{context}'), {context: this.context}) : this.$gettext('Aktionsmenü');
        }
    }
}
</script>
