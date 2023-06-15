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
                <form class="default">
                    <fieldset>
                        <legend>
                            {{ $gettext('Rechte') }}
                        </legend>
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
                                <tr v-for="(share, index) in videoShares.perms" v-bind:key="share.id">
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
                            @add="addPerm"
                            :selectedUsers="shareUsers"
                            />
                    </fieldset>
                </form>
                <form class="default">
                    <fieldset>
                        <legend>
                            {{ $gettext('Share Links') }}
                        </legend>

                        <table class="default" style="margin-bottom: 0;">
                            <colgroup>
                                <col style="width: 90%">
                                <col style="width: 10%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>
                                        {{ $gettext('Link') }}
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody v-if="videoShares.shares?.length > 0">
                                <tr v-for="(slink, index) in videoShares.shares" :key="index">
                                    <td>
                                        <input type="text" readonly
                                            :data-id="slink?.id ?? 0"
                                            ref="shareLink"
                                            :value="slink?.link ?? $gettext('Noch nicht erstellt!')"
                                            style="width: 98%;"/>
                                    </td>
                                    <td>
                                        <template v-if="slink.is_new === true">
                                            <studip-icon shape="clipboard" role="inactive"
                                                :title="$gettext('Share-Link noch nicht verfügbar')"/>
                                        </template>
                                        <template v-else>
                                            <studip-icon shape="clipboard" role="clickable"
                                                @click="copyLinkShare(slink.id)"
                                                :title="$gettext('Share-Link kopieren')"
                                                style="cursor: pointer;"/>
                                        </template>
                                        <studip-icon shape="remove" role="clickable"
                                            @click="removeLinkShare(index)"
                                            :title="$gettext('Share-Link löschen')"
                                            style="cursor: pointer; margin-left: 5px;"/>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="2">
                                        {{ $gettext('Es gibt bisher kein Share-Link') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">
                                        <StudipButton icon="add" @click.prevent="addLinkShare">
                                            {{ $gettext('Share-Link hinzufügen') }}
                                        </StudipButton>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon';
import StudipButton from "@studip/StudipButton";

import ShareWithUsers from './VideoAccess/ShareWithUsers';

export default {
    name: 'VideoAccess',

    components: {
        StudipDialog, StudipIcon,
        ShareWithUsers, StudipButton
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            shareUsers: []
        }
    },

    computed: {
        ...mapGetters(['videoShares'])
    },

    methods: {
        addPerm(user)
        {
            this.shareUsers.push(user);
        },

        removePerm(index)
        {
            this.videoShares.perms.splice(index, 1);
        },

        addLinkShare()
        {
            let dummyLink = {
                is_new: true
            }
            this.videoShares.shares.push(dummyLink);
            this.$store.dispatch('updateVideoShares', {
                token: this.event.token,
                shares: this.videoShares
            }).then(({ data }) => {
                this.$store.dispatch('addMessage', {
                        type: 'success',
                        text: this.$gettext('Share-Link erstellt!')
                    });
                this.initVideoShares();
            }).catch((er) => {
                console.log('Error while creating share link!', er);
            });
        },

        removeLinkShare(index)
        {
            this.videoShares.shares.splice(index, 1);
        },

        copyLinkShare(id) {
            let input = this.$refs.shareLink.find(elm => elm.dataset.id == id);
            if (input) {
                try {
                    input.select();
                    document.execCommand("copy");
                    document.getSelection().removeAllRanges();
                    this.$store.dispatch('addMessage', {
                        type: 'success',
                        text: this.$gettext('Der Link wurde in die Zwischenablage kopiert.')
                    });
                } catch(e) {
                    console.log(e);
                }
            }
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

        initVideoShares() {
            this.$store.dispatch('loadVideoShares', this.event.token)
            .then(() => {
                this.shareUsers = this.videoShares.perms
            });
        }
    },

    mounted () {
        this.initVideoShares();
    },
}
</script>