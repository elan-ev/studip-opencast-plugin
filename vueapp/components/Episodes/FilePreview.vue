<template>
    <div>
        <div v-for="file in files" class="oc--file-preview">
            <h3 class="oc--file-type" v-if="type == 'presenter'" v-translate>
                Vortragende*r
            </h3>
            <h3 class="oc--file-type" v-if="type == 'presentation'" v-translate>
                Folien
            </h3>

            <span class="oc--file-name">
                <b v-translate>Name:</b> {{ file.name }}
            </span>

            <span class="oc--file-size">
                <b v-translate>Größe:</b> {{ file.size }}
            </span>



            <span>
                <button class='button cancel'
                    type=button v-translate @click="$emit('remove', file)"
                >
                    Entfernen
                </button>
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'upload-file-preview',

    props: ['files', 'type']
}
</script>
