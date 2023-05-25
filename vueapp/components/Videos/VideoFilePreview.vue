<template>
    <div>
        <div v-for="file in files" class="oc--file-preview" :key="file.id">
            <h3 class="oc--file-type" v-if="type == 'presenter'" v-translate>
                Vortragende*r
            </h3>
            <h3 class="oc--file-type" v-if="type == 'presentation'" v-translate>
                Folien
            </h3>
            <h3 class="oc--file-type" v-if="type == 'caption'" v-translate>
                Untertitel für {{ files.language }}
            </h3>

            <span class="oc--file-name">
                <b v-translate>Name:</b> {{ file.name }}
            </span>

            <span class="oc--file-size" v-if="file.size">
                <b v-translate>Größe:</b> {{ $filters.filesize(file.size) }}
            </span>

            <span>
                <button class='button cancel'
                    type=button v-translate @click="$emit('remove', file)"
                    v-if="!uploading"
                >
                    Entfernen
                </button>
            </span>

            <span v-if="file.url">
                <a :href="file.url">
                    <button class='button download'
                        type=button v-translate
                    >
                        Herunterladen
                    </button>
                </a>
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FilePreview',

    props: ['files', 'type', 'uploading']
}
</script>
