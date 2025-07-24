import { computed } from 'vue';

export function useUrlHelper() {
    const previewSrc = (video) => computed(() => 
        video.preview ?? STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + video.token
    );


    return {
        previewSrc,
    };
}
