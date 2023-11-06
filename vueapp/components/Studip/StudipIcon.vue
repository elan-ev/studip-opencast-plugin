<template>
    <input v-if="name" type="image" :name="name" :src="url"
           :width="size" :height="size" v-bind="$attrs">
    <img v-else class="oc--image-button" :src="url" :width="size" :height="size" v-bind="$attrs">
</template>

<script>
    export default {
        name: 'studip-icon',
        props: {
            shape: String,
            role: {
                type: String,
                required: false,
                default: 'clickable'
            },
            size: {
                required: false,
                default: 16
            },
            name: {
                type: String,
                required: false
            }
        },
        computed: {
            url: function () {
                if (this.shape.indexOf("http") === 0) {
                    return this.shape;
                }
                let path = this.shape.split('+').reverse().join('/');

                if (this.shape == 'clipboard') {
                    return window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/clipboard.svg';
                } else {
                    return `${STUDIP.ASSETS_URL}images/icons/${this.color}/${path}.svg`;
                }
            },
            color: function () {
                switch (this.role) {
                    case 'info':
                        return 'black';

                    case 'inactive':
                        return 'grey';

                    case 'accept':
                    case 'status-green':
                        return 'green';

                    case 'attention':
                    case 'new':
                    case 'status-red':
                        return 'red';

                    case 'info_alt':
                        return 'white';

                    case 'sort':
                    case 'status-yellow':
                        return 'yellow';

                    case 'lightblue':
                        return 'lightblue';

                    case 'clickable':
                    case 'navigation':
                    default:
                        return 'blue';
                }
            }
        }
    }
</script>
