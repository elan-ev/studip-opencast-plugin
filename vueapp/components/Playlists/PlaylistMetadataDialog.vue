<template>
    <StudipDialog
        :title="$gettext('Wiedergabeliste bearbeiten')"
        :confirmText="$gettext('Speichern')"
        :confirmClass="'accept'"
        :closeText="$gettext('Abbrechen')"
        :closeClass="'cancel'"
        height="500"
        @close="$emit('cancel')"
        @confirm="updatePlaylist"
    >
        <template v-slot:dialogContent>
            <form class="default" ref="formRef">
                <label>
                    <span class="required">{{ $gettext('Titel') }}</span>
                    <input type="text" v-model="eplaylist.title" required />
                </label>
                <label>
                    {{ $gettext('Beschreibung') }}
                    <textarea v-model="eplaylist.description"></textarea>
                </label>
                <label>
                    {{ $gettext('Schlagworte') }}
                    <TagBar :taggable="eplaylist" @update="updateTags" />
                </label>
            </form>
        </template>
    </StudipDialog>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import StudipDialog from '@studip/StudipDialog';
import TagBar from '@/components/TagBar.vue';
import { useStore } from 'vuex';

const emit = defineEmits(['done', 'cancel']);

const props = defineProps({
    playlist: { type: Object, required: true },
});
const eplaylist = ref({});
const formRef = ref(null);
const store = useStore();

onMounted(() => {
    eplaylist.value = JSON.parse(JSON.stringify(props.playlist));
});

const updatePlaylist = () => {
    if (!formRef.value?.reportValidity()) {
        return false;
    }

    const updatedPlaylist = {
        ...props.playlist,
        title: eplaylist.value.title,
        description: eplaylist.value.description,
        tags: JSON.parse(JSON.stringify(eplaylist.value.tags)),
    };

    store.dispatch('playlists/updatePlaylist', updatedPlaylist).then(() => {
        store.dispatch('playlists/updateAvailableTags', updatedPlaylist);
    });

    emit('done', updatedPlaylist);
};

const updateTags = () => {
    eplaylist.value.tags = eplaylist.value.tags.map((tag) => {
        return typeof tag === 'string' || !tag.tag ? { tag: typeof tag === 'string' ? tag : tag.tag } : tag;
    });
};
</script>
