<template>
    <!--
    <pre>
        selectedUsers {{ selectedUsers }}
    </pre>
    -->
    <form class="default" v-if="shareUsers">
        <fieldset>
            <legend>
                {{ $gettext('Für Nutzende freigeben') }}
            </legend>
            <label>
                <studip-select :options="shareUsers" v-model="selectedUser"
                    label="fullname"
                    track-by="user_id"
                    :filterable="false"
                    @search="updateUserList"
                >
                    <template #list-header>
                        <li style="text-align: center">
                            <b>{{ $gettext('Nutzer/innen') }}</b>
                        </li>
                    </template>
                    <template #no-options="{ search, searching, loading }">
                        {{ $gettext('Keine Nutzenden gefunden!')}}
                    </template>
                    <template #selected-option="option">
                        <span class="vs__option">
                            {{ option.title_front }}
                            {{ option.Nachname ? option.Nachname + `, ` : `` }}
                            {{ option.Vorname }}
                            {{ option.title_rear }}
                            ({{ option.username }})
                        </span>
                    </template>
                    <template #option="option">
                        <span class="vs__option">
                            {{ option.title_front }}
                            {{ option.Nachname ? option.Nachname + `, ` : `` }}
                            {{ option.Vorname }}
                            {{ option.title_rear }}
                            ({{ option.username }})
                        </span>
                    </template>
                </studip-select>
            </label>

            <label>
                {{ $gettext('Berechtigung') }}
                <select v-model="selectedUserPerm">
                    <template v-for="perm in ['owner', 'write', 'read']">
                        <option :value="perm">
                            {{ $filters.permname(perm, $gettext) }}
                        </option>
                    </template>
                </select>
            </label>
        </fieldset>
        <footer>
            <StudipButton
                :disabled="selectedUser == null || selectedUserPerm == null"
                icon="accept"
                @click.prevent="addShareUser()"
            >
                {{ $gettext('Für Nutzer/in freigeben') }}
            </StudipButton>
        </footer>
    </form>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from '@studip/StudipIcon';
import StudipSelect from '@studip/StudipSelect';

export default {
    name: 'ShareWithUsers',

    components:
    {
        StudipButton,    StudipIcon,
        StudipSelect
    },

    props:
    {
        selectedUsers: {
            type: Object,
            default: []
        }
    },

    data()
    {
        return {
            selectedUser: null,
            selectedUserPerm: null,
        }
    },

    computed: {
        ...mapGetters(['userList', 'currentUser']),

        shareUsers()
        {
            // if there are no users to be added, hide list completely
            if (this.userList.legth == 0) {
                return;
            }

            // Remove already selected users from userlist to prevent trying to add them multiple times.
            // Also remove the current user form the list
            return this.userList.filter((perm) => {
                return this.selectedUsers.find(user => {
                    return user.user_id == perm.user_id
                }) === undefined
                    && this.currentUser.id !== perm.user_id;

            });
        }
    },

    methods:
    {
        updateUserList(search, loading)
        {
            this.$store.dispatch('loadUserList', search ? search : '%');
        },

        addShareUser()
        {
            if (Object.keys(this.selectedUser).length > 0
                && this.selectedUser.user_id
                && this.selectedUserPerm
            ) {
                this.selectedUser.perm = this.selectedUserPerm;
                this.$emit('add', this.selectedUser);

                this.selectedUser = null;
                this.selectedUserPerm = null;
            }
        }
    },

    mounted()
    {
        this.$store.dispatch('loadUserList', '%');
    }
}
</script>
