<template>
    <input v-if="name" type="image" :name="name" :src="url"
           :width="size" :height="size" v-bind="$attrs" v-on="$listeners">
    <img v-else :src="url" :width="size" :height="size" v-bind="$attrs" v-on="$listeners">
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
                return `${STUDIP.ASSETS_URL}images/icons/${this.color}/${this.shape}.svg`;
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
