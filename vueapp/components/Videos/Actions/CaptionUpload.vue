<template>
    <div>
        <VideoChangeWarning
            v-if="!showCaptionsDialog"
            :event="event"
            :title="$gettext('Auswirkung der Bearbeitung von Untertiteln')"
            @done="showCaptionsDialog = true"
            @cancel="decline"
        />

        <StudipDialog
            v-if="showCaptionsDialog"
            :title="$gettext('Untertitel bearbeiten')"
            :confirmText="$gettext('Hochladen')"
            :confirmClass="uploadButtonClasses"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @done="decline"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent ref="upload-dialog">
                <form class="default" style="max-width: 50em;" ref="upload-form">
                    <fieldset>
                        <legend >
                                {{ $gettext('Untertiteldatei(en)') }} - {{ $gettext('Workflow') }}: {{ defaultWorkflow.displayname }}
                        </legend>

                        <p class="help">
                            {{ $gettext('Unterstützt wird das WebVTT Format mit der Endung .vtt') }}
                        </p>

                        <div v-for="language in languages">
                            <fieldset v-if="!uploadProgress">
                                <legend class="oc--file-type">
                                    {{
                                        $gettext('Untertitel für %{ lang }', {
                                            lang: language.lang
                                        })
                                    }}
                                </legend>

                                <div class="oc--file-preview" v-if="files[language.tag] && files[language.tag].size">
                                    <span class="oc--file-name">
                                        <b>{{ $gettext('Name:') }}</b> {{ files[language.tag].name }}
                                    </span>

                                    <span class="oc--file-size" v-if="files[language.tag].size">
                                        <b>{{ $gettext('Größe:') }}</b> {{files[language.tag].size }}
                                    </span>
                                </div>

                                <div class="oc--button-bar">
                                    <label v-if="files[language.tag] && files[language.tag].url">
                                        <a :href="files[language.tag].url">
                                            <button class='button download' type=button>
                                                {{ $gettext('Herunterladen') }}
                                            </button>
                                        </a>
                                    </label>

                                    <label v-if="files[language.tag]">
                                        <StudipButton icon="trash" @click.prevent="removeCaption(language.tag)">
                                            {{ $gettext('Löschen') }}
                                        </StudipButton>
                                    </label>

                                    <label class="oc--file-upload">
                                        <StudipButton icon="add" @click.prevent="chooseFiles('oc-file-' + language.lang)">
                                            {{ $gettext('Untertiteldatei auswählen') }}
                                        </StudipButton>
                                        <input
                                            type="file" class="caption_upload"
                                            :data-tag="language.tag"
                                            @change="previewFiles" :ref="'oc-file-' + language.lang"
                                            accept=".vtt"
                                        >
                                    </label>
                                </div>
                            </fieldset>

                            <ProgressBar v-if="uploadProgress && uploadProgress[language.tag]" :progress="uploadProgress[language.tag].progress" />
                        </div>
                    </fieldset>

                    <a v-if="event.publication.annotation_tool" :href="annotation_tool_link" target="_blank">
                        {{ $gettext('Link zum Opencast Untertiteleditor') }}
                        <studip-icon shape="share" role="clickable"/>
                    </a>

                    <MessageList :float="true" :dialog="true"/>

                    <MessageBox v-if="fileUploadError" type="error">
                        {{ $gettext('Sie müssen mindestens eine Datei auswählen!') }}
                    </MessageBox>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { toRaw } from "vue";

import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageBox from '@/components/MessageBox'
import MessageList from '@/components/MessageList';
import ProgressBar from '@/components/ProgressBar'
import UploadService from '@/common/upload.service'
import VideoChangeWarning from '@/components/Videos/VideoChangeWarning';
import StudipIcon from '@/components/Studip/StudipIcon'

export default {
    name: 'CaptionUpload',

    components: {
        StudipDialog,
        MessageBox,
        StudipButton,
        ProgressBar,
        MessageList,
        VideoChangeWarning,
        StudipIcon
    },

    emits: ['done', 'cancel'],

    props: ['event'],

    data () {
        return {
            showCaptionsDialog: false,
            selectedServer: false,
            fileUploadError: false,
            files: {},
            uploadProgress: null,
            languages: []
        }
    },

    computed: {
        ...mapGetters({
            'config'       : 'simple_config_list',
            'course_config': 'course_config',
            'videoCaptions': 'videoCaptions',
            'config'       : 'simple_config_list',
        }),

        uploadButtonClasses() {
            if (this.uploadProgress && Object.keys(this.uploadProgress).length > 0) {
                return 'accept disabled';
            }

            return 'accept';
        },

        annotation_tool_link() {
            let redirectUrl = window.OpencastPlugin.REDIRECT_URL + '/perform';
            let action = '/annotation/' + this.event.token;

            if (redirectUrl && this.event.publication.annotation_tool) {
                return redirectUrl + action;
            }
        },

        defaultWorkflow() {

            let workflow = this.config['workflow_configs'].find(wf_config =>
                wf_config['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER']
                    && wf_config['used_for'] === 'subtitles'
            )

            if (workflow) {
                let wf_id = workflow['workflow_id'];

                return this.config['workflows'].find(wf => wf['id'] == wf_id);
            }

            return 'republish-metadata';
        },

        upload_workflows() {
            return this.config['workflows'].filter(wf => wf['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER'] && wf['tag'] === 'upload');
        },
    },

    methods: {
        removeCaption(tag) {
            if (this.uploadProgress && Object.keys(this.uploadProgress).length > 0) {
                return;
            }

            if (confirm(this.$gettext('Sind sie sicher?'))) {
                let files = [{
                    file: undefined,
                    tag: tag,
                    overwriteExisting: true,
                    progress: {
                        loaded: 0,
                        total: 0
                    }
                }];

                if (this.files[tag].url) {
                    let view = this;
                    // get correct upload endpoint url
                    this.uploadService = new UploadService({
                        'apievents':    this.selectedServer['apievents'],
                        'apiworkflows': this.selectedServer['apiworkflows'],
                    });

                    this.uploadService.uploadCaptions(files, this.event.episode, this.defaultWorkflow.name, {
                        uploadProgress: {
                            [tag]: () => {},
                        },
                        uploadDone: () => {
                            delete view.files[tag]
                        }
                    });
                } else {
                    delete this.files[tag]
                }

            }
        },

        async accept() {
            if (this.uploadProgress && Object.keys(this.uploadProgress).length > 0) {
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
                if (this.files[language.tag]) {
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
            this.uploadService = new UploadService({
                'apievents':    this.selectedServer['apievents'],
                'apiworkflows': this.selectedServer['apiworkflows'],
            });

            let files = [];
            for (const [key, value] of Object.entries(this.files)) {
                if (value['file']) {
                    files.push({
                        file: value['file'],
                        tag: value['tag'],
                        overwriteExisting: true,
                        progress: {
                            loaded: 0,
                            total: value.size
                        }
                    });
                }
            }

            let view = this;

            this.uploadService.uploadCaptions(files, this.event.episode, this.defaultWorkflow.name, {
                    uploadProgress: (track, loaded, total) => {
                        view.uploadProgress = {
                            tag: track.tag,
                            progress: parseInt(Math.round((loaded / total) * 100 ))
                        }
                    },
                    uploadDone: () => {
                        this.$store.dispatch('addMessage', {
                            type: 'success',
                            text: this.$gettext('Die Datei wurde erfolgreich hochgeladen.')
                        });
                        view.$emit('done');
                    },
                    onError: (response) => {
                        this.$store.dispatch('addMessage', {
                            type: 'error',
                            text: this.$gettext('Beim Hochladen der Datei ist ein Fehler aufgetreten. Stellen Sie sicher, dass eine Verbindung zum Opencast Server besteht und probieren Sie es erneut.'),
                            dialog: true
                        });
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
            let tag    = event.target.attributes['data-tag'].value;

            let language = this.languages.find(language => language.tag === tag).lang;

            this.files[tag] = {
                name: event.target.files[0].name,
                size: this.$filters.filesize(event.target.files[0].size),
                file: event.target.files[0],
                tag:  tag
            }
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
        this.$store.dispatch('simpleConfigListRead').then(() => {
            this.selectedServer = this.config['server'][this.config.settings['OPENCAST_DEFAULT_SERVER']];
        });
        this.$store.dispatch('loadCaption', this.event.token);

        // Add support for StudIP languages
        for (let key in window.OpencastPlugin.STUDIP_LANGUAGES) {
            this.languages.push({
                lang: window.OpencastPlugin.STUDIP_LANGUAGES[key].name,
                flavor: 'captions/prepared',
                tag: 'lang:' + key
            });
        }
    },

    watch: {
        videoCaptions(newCaptions) {
            if (Object.keys(newCaptions).length > 0) {
            for (var tag in newCaptions) {
                let language = this.languages.find(language => language.tag === tag);

                if (language) {
                    this.files[tag] = {
                        'url': newCaptions[tag].url,
                        'name': newCaptions[tag].url.split('/').pop()
                    };
                    this.files[tag]['language'] = language.lang;
                }
            }
        }
        }
    }
}
</script>
