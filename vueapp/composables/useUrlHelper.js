export function useUrlHelper() {
    const previewSrc = (video) => STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + video.token;
    return {
        previewSrc,
    };
}
