<template>
    <div>
        <StudipDialog
            :title="$gettext('Video freigeben')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="updateShares"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="videoShares.perms?.length > 0">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Nutzer/in') }}
                            </th>
                            <th>
                                {{ $gettext('Rechte') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="share in videoShares.perms" v-bind:key="share.id">
                            <td>

                                {{ share.fullname }}
                            </td>
                            <td>
                                {{ permToText(share.perm) }}
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="removePerm(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <ShareWithUsers
                    @add="addUserToList"
                    :selectedUsers="videoShares.perms"
                />

            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon';

import ShareWithUsers from './VideoAccess/ShareWithUsers';

export default {
    name: 'VideoAccess',

    components: {
        StudipDialog, StudipIcon,
        ShareWithUsers
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters(['videoShares'])
    },

    methods: {

        addUserToList() {

        },

        addLinkShare() {

        },

        removePerm(index) {
            this.videoShares.perms.splice(index, 1);
        },

        decline() {
            this.$emit('cancel');
        },

        permToText(perm) {
            let translations = {
                'owner': this.$gettext('Besitzer/in'),
                'write': this.$gettext('Schreibrechte'),
                'read':  this.$gettext('Leserechte'),
                'share': this.$gettext('Kann weiterteilen')
            }

            return translations[perm];
        },

        updateShares() {
            this.$store.dispatch('updateVideoShares', {
                token: this.event.token,
                shares: this.videoShares
            })
            .then(({ data }) => {
                this.$store.dispatch('addMessage', this.$gettext('Freigaben gespeichert!'));
                this.$emit('done', 'refresh');
            }).catch(() => {
                this.$emit('cancel');
            });
        },
    },

    mounted () {
        this.$store.dispatch('loadVideoShares', this.event.token);
    },
}
</script>