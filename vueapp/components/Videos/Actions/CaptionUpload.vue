<template>
    <div>
        <ConfirmDialog v-if="showConfirmDialog"
            :title="$gettext('Hochladen abbrechen')"
            :message="$gettext('Sind sie sicher, dass sie das Hochladen abbrechen möchten?')"
            @done="decline"
            @cancel="showConfirmDialog = false"
        />
        <StudipDialog v-else
            :title="$gettext('Untertitel hinzufügen')"
            :confirmText="$gettext('Hochladen')"
            :confirmClass="uploadButtonClasses"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="showConfirmDialog=true"
            @confirm="accept"
        >
            <template v-slot:dialogContent ref="upload-dialog">
                <form class="default" style="max-width: 50em;" ref="upload-form">

                    <label v-if="config && config['server'] && config['server'].length > 1">
                        <span class="required" v-translate>
                            Server auswählen:
                        </span>

                        <select v-model="selectedServer" required>
                            <option v-for="server in config['server']"
                                :key="server.id"
                                :value="server"
                            >
                                #{{ server.id }} - {{ server.name }} (Opencast V {{ server.version }}.X)
                            </option>

                        </select>
                    </label>

                    <div v-if="!files.length && !uploadProgress">
                        <StudipButton icon="accept" v-translate  @click.prevent="chooseFiles('oc-file-presentation')">
                            Dateien hinzufügen
                        </StudipButton>
                        <input type="file" class="caption_upload"
                            @change="previewFiles" ref="oc-file-presentation"
                            accept=".vtt">
                    </div>
                    <VideoFilePreview v-else :files="files"
                        type="caption"  @remove="files=[]"
                        :uploading="uploadProgress"
                    />

                    <ProgressBar v-if="uploadProgress" :progress="uploadProgress.progress" />

                    <MessageBox v-if="fileUploadError" type="error" v-translate>
                        Sie müssen mindestens eine Datei auswählen!
                    </MessageBox>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageBox from '@/components/MessageBox'
import VideoFilePreview from '@/components/Videos/VideoFilePreview'
import ProgressBar from '@/components/ProgressBar'
import ConfirmDialog from '@/components/ConfirmDialog'

import UploadService from '@/common/upload.service'
import { format } from 'date-fns'
import { de } from 'date-fns/locale'

export default {
    name: 'CaptionUpload',

    components: {
        StudipDialog,
        MessageBox,
        StudipButton,
        VideoFilePreview,
        ProgressBar,
        ConfirmDialog
    },

    emits: ['done', 'cancel'],

    props: ['currentUser'],

    data () {
        return {
            showAddEpisodeDialog: false,
            selectedServer: false,
            fileUploadError: false,
            files: [],
            uploadProgress: null,
            showConfirmDialog: false
        }
    },

    computed: {
        ...mapGetters({
            'config'       : 'simple_config_list',
            'course_config': 'course_config'
        }),

        uploadButtonClasses() {
            if (this.uploadProgress) {
                return 'accept disabled';
            }

            return 'accept';
        },
    },

    methods: {
        async accept() {
            if (this.uploadProgress) {
                return;
            }

            // make sure lti is authenticated
            await this.$store.dispatch('authenticateLti');

            if (!this.$refs['upload-form'].reportValidity()) {
                return false;
            }
            
            // validate file upload
            this.fileUploadError = false;
            if (!this.files.length) {
                this.fileUploadError = true;

                // scroll to error message to make it visible to the user
                this.$refs['upload-form'].parentNode.scrollTo({
                    top: 1000,
                    left: 0,
                    behavior: 'smooth'
                });

                return false;
            }

            // get correct upload endpoint url
            this.uploadService = new UploadService(this.selectedServer['ingest']);

            let files = [];
            files.push({
                file: this.files[0],
                flavor: 'captions/source+en',
                progress: {
                    loaded: 0,
                    total: this.files[0].size
                }
            });

            let view = this;

            this.uploadService.addCaptions(files, {
                uploadProgress: (track, loaded, total) => {
                    view.uploadProgress = {
                        flavor: track.flavor,
                        progress: parseInt(Math.round((loaded / total) * 100 ))
                    }
                },
                uploadDone: (episode_id, workflow_id) => {
                    view.$emit('done');
                    view.$store.dispatch('createLogEvent', {
                        event: 'upload',
                        data: {
                            episode_id: episode_id,
                            workflow_id: workflow_id
                        }
                    })
                }
            });
        },

        decline() {
            this.$emit('cancel');
        },

        chooseFiles(id) {
            this.$refs[id].click();
        },

        previewFiles(event) {
            this.files = event.target.files;
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
        this.$store.dispatch('simpleConfigListRead').then(() => {
            this.selectedServer = this.config['server'][this.config.settings['OPENCAST_DEFAULT_SERVER']];
        });
    }
}
</script>
