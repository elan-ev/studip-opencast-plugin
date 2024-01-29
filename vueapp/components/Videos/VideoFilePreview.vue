<template>
    <div>
        <div v-for="file in files" class="oc--file-preview" :key="file.id">
            <h3 class="oc--file-type" v-if="type == 'presenter'">
                {{ $gettext('Vortragende*r') }}
            </h3>
            <h3 class="oc--file-type" v-if="type == 'presentation'">
                {{ $gettext('Folien') }}
            </h3>

            <span class="oc--file-name">
                <b>
                    {{ $gettext('Name:') }}
                </b> 
                {{ file.name }}
            </span>

            <span class="oc--file-size" v-if="file.size">
                <b>
                    {{ $gettext('Größe:') }}
                </b> 
                {{ $filters.filesize(file.size) }}
            </span>

            <span>
                <button class='button cancel'
                    type=button @click="$emit('remove', file)"
                    v-if="!uploading"
                >
                    {{ $gettext('Entfernen') }}
                </button>
            </span>

            <span v-if="file.url">
                <a :href="file.url">
                    <button class='button download'
                        type=button
                    >
                        {{ $gettext('Herunterladen') }}
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
