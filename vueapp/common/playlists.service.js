import axios from "@/common/axios.service";
import store from "../store"

/**
 * This object manages the Opencast playlists via LTI
 */
class PlaylistsService {
    server;
    service_url;

    constructor(server) {
        this.server = server;
        this.service_url = server['apiplaylists'];
    }

    /**
     * Get playlist from Opencast
     *
     * @param id identifier of playlist
     */
    get(id) {
        return axios({
            method: 'GET',
            url: this.service_url + `/${id}`,
            withCredentials: true,
        })
    }

    /**
     * Get default ACLs array for a playlist
     *
     * @param playlistId playlist identifier. If null, only user role will be added.
     * @return {Array} access control list
     */
    getDefaultACL(playlistId = null) {
        const user = store.getters.currentLTIUser[this.server['id']];
        let acls = [
            {
                allow: true,
                role: user.userRole,
                action: 'read'
            },
            {
                allow: true,
                role: user.userRole,
                action: 'write'
            },
        ];

        if (playlistId) {
            acls.push(
                {
                    allow: true,
                    role: `STUDIP_PLAYLIST_${playlistId}_read`,
                    action: 'read'
                },
                {
                    allow: true,
                    role: `STUDIP_PLAYLIST_${playlistId}_write`,
                    action: 'read'
                },
                {
                    allow: true,
                    role: `STUDIP_PLAYLIST_${playlistId}_write`,
                    action: 'write'
                }
            );
        }

        return acls;
    }

    /**
     * Create a playlist in Opencast
     *
     * @param title title of playlist
     * @param description description of playlist
     * @param creator creator name of playlist
     * @param {Array} entries playlist entries such as events
     */
    create(title, description, creator, entries) {
        let playlist = {
            title: title,
            description: description,
            creator: creator,
            entries: entries,
            accessControlEntries: this.getDefaultACL()
        }

        // Create playlist
        return axios({
            url: this.service_url + '/',
            method: 'POST',
            data: new URLSearchParams({
                playlist: JSON.stringify(playlist)
            }),
            withCredentials: true,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            }
        }).then(({ data }) => {
            // Update created playlist with ACLs
            return this.update(
                data.id,
                data.title,
                data.description,
                data.creator,
                data.entries,
                this.getDefaultACL(data.id)
            );
        });
    }

    /**
     * Update a playlist in Opencast
     *
     * @param id identifier of playlist to be updated
     * @param title title of playlist
     * @param description description of playlist
     * @param creator creator name of playlist
     * @param {Array} entries playlist entries such as events
     * @param {Array} acls ACLs array to be updated. If empty, the default ACLs will be set.
     */
    update(id, title, description, creator, entries, acls=[]) {
        if (acls.length === 0) {
            acls = this.getDefaultACL(id);
        }

        // TODO: Why is it necessary to remove the id?
        acls.forEach((acl) => delete acl.id);

        let playlist = {
            title: title,
            description: description,
            creator: creator,
            entries: entries,
            accessControlEntries: acls,
        }

        // Update playlist
        return axios({
            url: this.service_url + `/${id}`,
            method: 'PUT',
            data: new URLSearchParams({
                playlist: JSON.stringify(playlist)
            }),
            withCredentials: true,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            }
        });
    }


    /**
     * Update entries of the playlist in Opencast
     *
     * @param id identifier of playlist to be updated
     * @param {Array} entries playlist entries replacing existing entries
     */
    updateEntries(id, entries) {
        return axios({
            url: this.service_url + `/${id}/entries`,
            method: 'POST',
            data: new URLSearchParams({
                playlistEntries: JSON.stringify(entries),
            }),
            withCredentials: true,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            }
        });
    }

    /**
     * Delete playlist in Opencast
     *
     * @param id identifier of playlist to be deleted
     */
    delete(id) {
        return axios({
            url: this.service_url + `/${id}`,
            method: 'DELETE',
            withCredentials: true,
        });
    }
}

export default PlaylistsService;
