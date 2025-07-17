import { ref, watchEffect } from 'vue';
import { useStore } from 'vuex';

export function useAvatar(userId) {
    const store = useStore();
    const avatarUrl = ref(null);

    watchEffect(async () => {
        if (userId.value) {
            avatarUrl.value = await store.dispatch('avatar/fetchAvatar', userId.value);
        } else {
            avatarUrl.value = null;
        }
    });

    return { avatarUrl };
}
