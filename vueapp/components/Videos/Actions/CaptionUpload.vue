<template>
    <div>
        <StudipDialog
            :title="$gettext('Untertitel hinzufügen')"
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
                                {{ $gettext('Datei(en)') }}
                        </legend>

                        <p class="help" v-translate>
                            Unterstützt wird das WebVTT Format mit der Endung .vtt
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

                                <div class="oc--file-preview" v-if="files[language.flavor] && files[language.flavor].size">
                                    <span class="oc--file-name">
                                        <b>{{ $gettext('Name:') }}</b> {{ files[language.flavor].name }}
                                    </span>

                                    <span class="oc--file-size" v-if="files[language.flavor].size">
                                        <b>{{ $gettext('Größe:') }}</b> {{files[language.flavor].size }}
                                    </span>
                                </div>

                                <div class="oc--button-bar">
                                    <label v-if="files[language.flavor] && files[language.flavor].url">
                                        <a :href="files[language.flavor].url">
                                            <button class='button download' type=button>
                                                {{ $gettext('Herunterladen') }}
                                            </button>
                                        </a>
                                    </label>

                                    <label v-if="files[language.flavor]">
                                        <StudipButton icon="trash" @click.prevent="removeCaption(language.flavor)">
                                            {{ $gettext('Löschen') }}
                                        </StudipButton>
                                    </label>

                                    <label class="oc--file-upload">
                                        <StudipButton icon="accept" @click.prevent="chooseFiles('oc-file-' + language.lang)">
                                            {{ $gettext('Untertiteldatei auswählen') }}
                                        </StudipButton>
                                        <input
                                            type="file" class="caption_upload" :data-flavor="language.flavor"
                                            @change="previewFiles" :ref="'oc-file-' + language.lang"
                                            accept=".vtt"
                                        >
                                    </label>
                                </div>
                            </fieldset>

                            <ProgressBar v-if="uploadProgress && uploadProgress.flavor == language.flavor" :progress="uploadProgress.progress" />
                        </div>
                    </fieldset>

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

import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageBox from '@/components/MessageBox'
import MessageList from '@/components/MessageList';
import ProgressBar from '@/components/ProgressBar'
import UploadService from '@/common/upload.service'

export default {
    name: 'CaptionUpload',

    components: {
        StudipDialog,
        MessageBox,
        StudipButton,
        ProgressBar,
        MessageList
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
            languages: []
        }
    },

    computed: {
        ...mapGetters({
            'config'       : 'simple_config_list',
            'course_config': 'course_config',
            'videoCaptions': 'videoCaptions'
        }),

        uploadButtonClasses() {
            if (this.uploadProgress) {
                return 'accept disabled';
            }

            return 'accept';
        },
    },

    methods: {
        removeCaption(flavor) {
            if (this.uploadProgress) {
                return;
            }

            if (confirm(this.$gettext('Sind sie sicher?'))) {
                let files = [{
                    file: undefined,
                    flavor: flavor,
                    overwriteExisting: true,
                    progress: {
                        loaded: 0,
                        total: 0
                    }
                }];

                if (this.files[flavor].url) {
                    let view = this;
                    // get correct upload endpoint url
                    this.uploadService = new UploadService(this.selectedServer['apievents']);

                    this.uploadService.uploadCaptions(files, this.event.episode, {
                        uploadProgress: () => {},
                        uploadDone: () => {
                            delete view.files[flavor]
                    }})
                } else {
                    delete this.files[flavor]
                }

            }
        },

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
                if (this.files[language.flavor]) {
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
                if (value['file']) {
                    files.push({
                        file: value['file'],
                        flavor: key,
                        overwriteExisting: true,
                        progress: {
                            loaded: 0,
                            total: value.size
                        }
                    });
                }
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
                        this.$store.dispatch('addMessage', {
                            type: 'success',
                            text: this.$gettext('Die Datei wurde erfolgreich hochgeladen.')
                        });
                        view.$emit('done');
                    },
                    onError: (response) => {
                        this.$store.dispatch('addMessage', {
                            type: 'error',
                            text: response,
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
            let flavor = event.target.attributes['data-flavor'].value;
            let language = this.languages.find(language => language.flavor === flavor).lang;

            this.files[flavor] = {
                name: event.target.files[0].name,
                size: this.$filters.filesize(event.target.files[0].size),
                file: event.target.files[0]
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
                flavor: 'captions/source+' + key.split('_')[0]
            });
        }
    },

    watch: {
        videoCaptions(newCaptions) {
            if (Object.keys(newCaptions).length > 0) {
            for (var flavor in newCaptions) {
                let language = this.languages.find(language => language.flavor === flavor);

                if (language) {
                    this.files[flavor] = {
                        'url': newCaptions[flavor].url,
                        'name': newCaptions[flavor].url.split('/').pop()
                    };
                    this.files[flavor]['language'] = language.lang;
                }
            }
        }
        }
    }
}
</script>
