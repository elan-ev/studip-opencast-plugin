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
                            {{ option.Nachname }},
                            {{ option.Vorname }}
                            {{ option.title_rear }}
                        </span>
                    </template>
                    <template #option="option">
                        <span class="vs__option">
                            {{ option.title_front }}
                            {{ option.Nachname }},
                            {{ option.Vorname }}
                            {{ option.title_rear }}
                        </span>
                    </template>
                </studip-select>
            </label>

            <label>
                {{ $gettext('Berechtigung') }}
                <select v-model="selectedUser.perm">
                    <option value="onwer">
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
                @click.prevent=" this.$emit('add', selectedUser)"
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

    components: {
        StudipButton,    StudipIcon,
        StudipSelect
    },

    props: {
        selectedUsers: {
            type: Object,
            default: []
        }
    },

    data() {
        return {
            selectedUser: {
                'perm': 'read'
            },
        }
    },

    computed: {
        ...mapGetters(['userList', 'currentUser']),

        shareUsers() {
            return this.userList.filter((perm) => {
                return this.selectedUsers.find(user => user.user_id == perm.user_id) === undefined
                    && this.currentUser.id !== perm.user_id;
            });
        },

        setSelectedUser(user) {
            console.log('setCurrentUser', user);
            this.selectedUser.user_id = user.user_id;
        }
    },

    methods: {
        updateUserList(search, loading)
        {
            this.$store.dispatch('loadUserList', search ? search : '%');
        }
    },

    mounted() {
        this.$store.dispatch('loadUserList', '%');
    }
}
</script>
