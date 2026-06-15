export function isDownloadAllowed({ downloadSetting, playlist, event, currentUser })
{
    if (['root', 'admin'].includes(currentUser.status)) {
        return true;
    }

    if (downloadSetting === 'never') {
        return false;
    }

    if (event?.perm === 'owner' || event?.perm === 'write') {
        return true;
    }

    if (playlist?.allow_download !== undefined) {
        return playlist.allow_download;
    }

    return downloadSetting === 'allow';
}
