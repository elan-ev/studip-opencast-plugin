<template>
    <span :class="{
        'oc--opencast-icon-small': small
    }">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
            <polygon fill="#24425D" points="5.8,2.4 5.8,2.4 5.8,5 5.8,5.1 5.8,5.1 3.0,8 5.8,11 5.8,13.6 5.8,13.6 5.8,13.6 0.4,8 0.4,8 0.4,8"/>
            <polygon fill="#4BB07B" points="5.8,2.4 5.8,2.4 5.8,5 5.8,5.1 5.8,5.1 8.8,8 5.8,11 5.8,13.6 5.8,13.6 5.8,13.6 11.4,8 11.4,8 11.4,8"/>
            <polygon fill="#4BB07B" points="10.2,2.4 10.2,2.4 10.2,5 10.2,5.1 10.2,5.1 13.2,8 10.2,11 10.2,13.6 10.2,13.6 10.2,13.6 15.8,8 15.8,8 15.8,8 "/>
        </svg>
    </span>
</template>

<script>
export default {
    name: "OpencastIcon",

    props: {
        small: {
            type: Boolean,
        },
    }
}
</script>
