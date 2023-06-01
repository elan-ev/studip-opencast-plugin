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

                    <div v-for="language in languages">
                        <div v-if="!files[language.flavor] && !uploadProgress">
                            <StudipButton icon="accept" v-translate @click.prevent="chooseFiles('oc-file-'+language.lang)">
                                Untertitel für {{ language.lang }}
                            </StudipButton>
                            <input type="file" class="caption_upload" :data-flavor="language.flavor"
                                @change="previewFiles" :ref="'oc-file-'+language.lang"
                                accept=".vtt">
                        </div>
                        <VideoFilePreview v-else :files="files[language.flavor]"
                            type="caption"  @remove="delete files[language.flavor]"
                            :uploading="uploadProgress"
                        />

                        <ProgressBar v-if="uploadProgress && uploadProgress.flavor == language.flavor" :progress="uploadProgress.progress" />
                    </div>

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

    props: ['event'],

    data () {
        return {
            showAddEpisodeDialog: false,
            selectedServer: false,
            fileUploadError: false,
            files: {},
            uploadProgress: null,
            showConfirmDialog: false,
            languages: []
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
            this.fileUploadError = true;
            this.languages.every(language => {
                if (this.files[language.flavor] && this.files[language.flavor].length) {
                    this.fileUploadError = false;
                    return false;
                }
                return true;
            });

            if (this.fileUploadError) {
                // scroll to error message to make it visible to the user
                this.$refs['upload-form'].parentNode.scrollTo({
                    top: 1000,
                    left: 0,
                    behavior: 'smooth'
                });

                return false;
            }

            // get correct upload endpoint url
            this.uploadService = new UploadService(this.selectedServer['apievents']);

            let files = [];
            for (const [key, value] of Object.entries(this.files)) {
                files.push({
                    file: value[0],
                    flavor: key,
                    overwriteExisting: true,
                    progress: {
                        loaded: 0,
                        total: value.size
                    }
                });
            }

            let view = this;

            this.uploadService.uploadCaptions(files, this.event.episode, {
                    uploadProgress: (track, loaded, total) => {
                        view.uploadProgress = {
                            flavor: track.flavor,
                            progress: parseInt(Math.round((loaded / total) * 100 ))
                        }
                    },
                    uploadDone: () => {
                        view.$emit('done');
                    }
                }
            );
        },

        decline() {
            this.$emit('cancel');
        },

        chooseFiles(id) {
            this.$refs[id][0].click();
        },

        previewFiles(event) {
            let flavor = event.target.attributes['data-flavor'].value;
            let language = this.languages.find(language => language.flavor === flavor).lang;

            this.files[flavor] = event.target.files;
            this.files[flavor]['language'] = language;
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
        this.$store.dispatch('simpleConfigListRead').then(() => {
            this.selectedServer = this.config['server'][this.config.settings['OPENCAST_DEFAULT_SERVER']];
        });

        // Add support for StudIP languages
        for (let key in window.OpencastPlugin.STUDIP_LANGUAGES) {
            this.languages.push({
                lang: window.OpencastPlugin.STUDIP_LANGUAGES[key].name,
                flavor: 'captions/source+' + key.split('_')[0]
            });
        }

        if (this.event?.publication?.downloads?.caption) {
            for (var flavor in this.event.publication.downloads.caption) {
                let language = this.languages.find(language => language.flavor === flavor);

                if (language) {
                    this.files[flavor] = [];
                    this.files[flavor].push({
                        'url': this.event.publication.downloads.caption[flavor].url,
                        'name': this.event.publication.downloads.caption[flavor].url.split('/').pop()
                    });
                    this.files[flavor]['language'] = language.lang;
                }
            }
        }
    }
}
</script>
