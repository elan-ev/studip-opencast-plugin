// composables/useVideoDrawer.js
import { useStore } from 'vuex';

export function useVideoDrawer() {
    const store = useStore();

    const setShowDrawer = (show) => store.dispatch('videodrawer/setShowDrawer', show);
    const setSelectedVideo = (video) => store.dispatch('videodrawer/setSelectedVideo', video);

    const selectVideo = (video) => {
        setShowDrawer(true);
        setSelectedVideo(video);
    };

    return { setShowDrawer, setSelectedVideo, selectVideo };
}
