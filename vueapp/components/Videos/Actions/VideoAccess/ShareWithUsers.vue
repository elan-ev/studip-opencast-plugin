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
                    label="user"
                    @option:selected="setSelectedUser"
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
                        </span>
                    </template>
                    <template #option="option">
                        <span class="vs__option">
                            {{ option.title_front }}
                            {{ option.Nachname ? option.Nachname + `, ` : `` }}
                            {{ option.Vorname }}
                            {{ option.title_rear }}
                        </span>
                    </template>
                </studip-select>
            </label>

            <label>
                {{ $gettext('Berechtigung') }}
                <select v-model="selectedUser.perm">
                    <option value="owner">
                        {{ $gettext('Besitzer/in') }}
                    </option>

                    <option value="write">
                        {{ $gettext('Schreibrechte') }}
                    </option>

                    <option value="read">
                        {{ $gettext('Leserechte') }}
                    </option>
                </select>
            </label>
        </fieldset>
        <footer>
            <StudipButton
                :disabled="selectedUser == null"
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
            selectedUser: {},
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

            // if no users been selected yet, return complete userlist
            if (this.selectedUsers.length == 0) {
                return this.userList;
            }

            // otherwise, remove already selected users from userlist to prevent trying to add them multiple times
            return this.userList.filter((perm) => {
                return this.selectedUsers.find(user => {
                    return user.user_id == perm.user_id
                }) === undefined
                    && this.currentUser.id !== perm.user_id;
            });
        },

        setSelectedUser(user)
        {
            if (!user.user_id) {
                return;
            }

            this.selectedUser.user_id = user.user_id;
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
                && this.selectedUser.perm
                && this.selectedUser.user_id
            ) {
                this.$emit('add', this.selectedUser);

                this.selectedUser = {};
            }
        }
    },

    mounted()
    {
        this.$store.dispatch('loadUserList', '%');
    }
}
</script>
