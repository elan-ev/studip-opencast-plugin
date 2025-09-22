<template>
    <div>
        <StudipDialog
            :title="$gettext('Video hinzufügen')"
            :confirmText="$gettext('Hinzufügen')"
            :confirmClass="uploadButtonClasses"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="840"
            width="870"
            class="oc--dialog-upload"
            @close="confirmCancel"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <template v-if="!uploadProgress">
                    <div class="oc--dialog-upload__wrapper">
                        <div class="oc--dialog-upload__info">
                            <div class="oc--dialog-upload__info-icon">
                                <StudipIcon shape="upload" :size="96" role="info" />
                            </div>
                            <div class="oc--dialog-upload__info-header">{{ $gettext('Information') }}</div>
                            <div class="oc--dialog-upload__info-content" v-html="infoText"></div>
                        </div>
                        <form class="default collapsable oc--dialog-upload__form" ref="upload-form">
                            <fieldset>
                                <legend>
                                    {{ $gettext('Videodetails') }}
                                </legend>

                                <label>
                                    <span class="required">
                                        {{ $gettext('Titel') }}
                                    </span>

                                    <input
                                        type="text"
                                        maxlength="255"
                                        name="title"
                                        id="titleField"
                                        v-model="upload.title"
                                        required
                                    />
                                </label>
                                <label>
                                    <span>
                                        {{ $gettext('vortragende Person') }}
                                    </span>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        id="presenter"
                                        name="presenter"
                                        v-model="upload.creator"
                                    />
                                </label>

                                <label>
                                    <span>
                                        {{ $gettext('Beschreibung') }}
                                    </span>
                                    <textarea
                                        cols="50"
                                        rows="5"
                                        id="description"
                                        name="description"
                                        v-model="upload.description"
                                    ></textarea>
                                </label>
                            </fieldset>

                            <fieldset class="collapsed">
                                <legend>
                                    {{ $gettext('Weitere Angaben') }}
                                </legend>
                                <label>
                                    <span class="required">
                                        {{ $gettext('Aufnahmezeitpunkt') }}
                                    </span>

                                    <input
                                        class="oc--datetime-input"
                                        type="datetime-local"
                                        name="recordDate"
                                        id="recordDate"
                                        v-model="upload.recordDate"
                                        required
                                    />
                                </label>
                                <label>
                                    <span>
                                        {{ $gettext('Mitwirkende') }}
                                    </span>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        id="contributor"
                                        name="contributor"
                                        v-model="upload.contributor"
                                    />
                                </label>
                                <label>
                                    <span>
                                        {{ $gettext('Betreff') }}
                                    </span>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        id="subject"
                                        name="subject"
                                        v-model="upload.subject"
                                    />
                                </label>

                                <label style="display: none">
                                    <span>
                                        {{ $gettext('Sprache') }}
                                    </span>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        id="language"
                                        name="language"
                                        v-model="upload.language"
                                    />
                                </label>
                            </fieldset>

                            <fieldset>
                                <legend>
                                    {{ $gettext('Video') }}
                                </legend>

                                <input
                                    v-show="false"
                                    type="file"
                                    class="video_upload"
                                    data-flavor="presenter/source"
                                    @change="previewFiles"
                                    ref="oc-file-presenter"
                                    :accept="uploadFileTypes"
                                />
                                <div
                                    v-if="!files['presenter/source'].length"
                                    class="oc--file-dropzone"
                                    @click="chooseFiles('oc-file-presenter')"
                                    @dragover.prevent="setDragOver($event, true)"
                                    @dragenter.prevent="setDragOver($event, true)"
                                    @dragleave.prevent="setDragOver($event, false)"
                                    @drop.prevent="handleDrop($event, 'oc-file-presenter')"
                                >
                                    <div class="oc--file-dropzone__description">
                                        <StudipIcon shape="upload" :size="48" />
                                        <div class="oc--file-dropzone__description-text">
                                            <span>{{ $gettext('Vortragende') }}</span>
                                            <small>{{
                                                $gettext('Datei hierher ziehen oder klicken, um auszuwählen')
                                            }}</small>
                                        </div>
                                    </div>
                                </div>
                                <VideoFilePreview
                                    v-else
                                    :files="files['presenter/source']"
                                    type="presenter"
                                    @remove="files['presenter/source'] = []"
                                    @choose="chooseFiles('oc-file-presenter')"
                                    :uploading="uploadProgress"
                                />

                                <input
                                    v-show="false"
                                    type="file"
                                    class="video_upload"
                                    data-flavor="presentation/source"
                                    @change="previewFiles"
                                    ref="oc-file-presentation"
                                    :accept="uploadFileTypes"
                                />
                                <div
                                    v-if="!files['presentation/source'].length"
                                    class="oc--file-dropzone"
                                    @click="chooseFiles('oc-file-presentation')"
                                    @dragover.prevent="setDragOver($event, true)"
                                    @dragenter.prevent="setDragOver($event, true)"
                                    @dragleave.prevent="setDragOver($event, false)"
                                    @drop.prevent="handleDrop($event, 'oc-file-presentation')"
                                >
                                    <div class="oc--file-dropzone__description">
                                        <StudipIcon shape="upload" :size="48" />
                                        <div class="oc--file-dropzone__description-text">
                                            <span>{{ $gettext('Folien') }}</span>
                                            <small>{{
                                                $gettext('Datei hierher ziehen oder klicken, um auszuwählen')
                                            }}</small>
                                        </div>
                                    </div>
                                </div>
                                <VideoFilePreview
                                    v-else
                                    :files="files['presentation/source']"
                                    type="presentation"
                                    @remove="files['presentation/source'] = []"
                                    @choose="chooseFiles('oc-file-presentation')"
                                    :uploading="uploadProgress"
                                />

                                <MessageBox v-if="fileUploadError" type="error">
                                    {{ $gettext('Sie müssen mindestens eine Datei auswählen!') }}
                                </MessageBox>

                                <MessageBox v-if="fileFormatError" type="error">
                                    {{
                                        $gettext('Dateien mit den Formaten WebM und MP4 können nicht gemischt werden!')
                                    }}
                                </MessageBox>
                            </fieldset>

                            <fieldset class="collapsed">
                                <legend>
                                    {{ $gettext('Hochladeoptionen') }}
                                </legend>
                                <label v-if="config && config['server'] && config['server'].length > 1">
                                    <span class="required"> {{ $gettext('Server') }}' </span>

                                    <select v-model="selectedServer" required>
                                        <option v-for="server in config['server']" :key="server.id" :value="server">
                                            #{{ server.id }} - {{ server.name }} (Opencast V {{ server.version }}.X)
                                        </option>
                                    </select>
                                </label>
                                <label>
                                    <span>
                                        {{ $gettext('Workflow') }}
                                    </span>

                                    <select v-model="selectedWorkflow" required>
                                        <option
                                            v-for="workflow in upload_workflows"
                                            v-bind:key="workflow.id"
                                            :value="workflow"
                                        >
                                            {{ workflow.displayname }}
                                        </option>
                                    </select>
                                </label>

                                <label v-if="upload_playlists && upload_playlists.length">
                                    <span>
                                        {{ $gettext('Wiedergabeliste') }}
                                    </span>

                                    <select v-model="upload.playlist_token" required>
                                        <option
                                            v-for="playlist in upload_playlists"
                                            v-bind:key="playlist.token"
                                            :value="playlist.token"
                                        >
                                            {{ playlist.title }}
                                        </option>
                                    </select>
                                </label>
                            </fieldset>
                        </form>
                    </div>
                </template>
                <div v-else class="oc--dialog-upload__progress-wrapper">
                    <div class="oc--dialog-upload__progress-header">
                        <StudipIcon shape="file-video" :size="128" />
                        <template v-if="uploadProgress.flavor == 'presenter/source'">
                            <span>
                                {{ $gettext('Vortragende') }}:
                                {{ files['presenter/source'].name }}
                            </span>
                        </template>
                        <template v-if="uploadProgress.flavor == 'presentation/source'">
                            <span>
                                {{ $gettext('Folien') }}:
                                {{ files['presentation/source'].name }}
                            </span>
                        </template>
                    </div>
                    <div class="oc--dialog-upload__progress">
                        <ProgressBar :progress="uploadProgress.progress" />
                    </div>
                </div>

                <MessageList :float="true" :dialog="true" />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog';
import StudipIcon from '@studip/StudipIcon.vue';
import MessageBox from '@/components/MessageBox';
import MessageList from '@/components/MessageList';
import VideoFilePreview from '@/components/Videos/VideoFilePreview';
import ProgressBar from '@/components/ProgressBar';

import UploadService from '@/common/upload.service';
import { format } from 'date-fns';
import { de } from 'date-fns/locale';
import { template } from 'lodash';

export default {
    name: 'VideoUpload',

    components: {
        StudipIcon,
        StudipDialog,
        MessageBox,
        VideoFilePreview,
        ProgressBar,
        MessageList,
    },

    emits: ['done', 'cancel'],

    props: ['currentUser'],

    data() {
        return {
            showAddEpisodeDialog: false,
            selectedServer: false,
            selectedWorkflow: false,
            fileUploadError: false,
            fileFormatError: false,
            upload: {
                creator: this.currentUser.fullname,
                contributor: '',
                playlist_token: null,
                recordDate: format(new Date(), "yyyy-MM-dd'T'HH:mm", { locale: de }),
                subject: this.$gettext('Medienupload, Stud.IP'),
            },
            files: {
                'presenter/source': [],
                'presentation/source': [],
            },
            uploadProgress: null,
        };
    },

    computed: {
        ...mapGetters({
            config: 'config/simple_config_list',
            course_config: 'config/course_config',
            cid: 'opencast/cid',
            playlist: 'playlists/playlist',
            playlists: 'playlists/playlists',
            currentLTIUser: 'opencast/currentLTIUser',
        }),

        upload_playlists() {
            let upload_playlists = [...this.playlists];

            if (!this.playlists.length) {
                return null;
            }

            if (!this.playlist) {
                upload_playlists.unshift({
                    token: null,
                    title: this.$gettext('Keiner Wiedergabeliste hinzufügen'),
                });
            }

            return upload_playlists;
        },

        upload_workflows() {
            return this.config['workflows'].filter(
                (wf) => wf['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER'] && wf['tag'] === 'upload'
            );
        },

        uploadFileTypes() {
            return this.selectedWorkflow?.settings?.upload_file_types || this.config.default_upload_file_types;
        },

        uploadFilesText() {
            let fileTypes = this.uploadFileTypes
                .split(',')
                .map((type) => type.trim())
                .filter((type) => type.search(/^\./) !== -1); // Only show types starting with a dot, e.g. ".mp4"

            return this.$gettext('Mindestens eine Aufzeichnung wird benötigt. Unterstützte Formate: %{ file_types }.', {
                file_types: fileTypes.join(', '),
            });
        },

        uploadButtonClasses() {
            if (this.uploadProgress) {
                return 'accept disabled';
            }

            return 'accept';
        },

        defaultWorkflow() {
            let wf_id = this.config['workflow_configs'].find(
                (wf_config) =>
                    wf_config['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER'] &&
                    wf_config['used_for'] === 'upload'
            )['workflow_id'];

            return this.config['workflows'].find((wf) => wf['id'] == wf_id);
        },

        infoText() {
            try {
                let info = JSON.parse(this.config.settings.OPENCAST_UPLOAD_INFO_TEXT_BODY);
                return info[this.config.user_language];
            } catch (e) {}

            return null;
        },
    },

    methods: {
        confirmCancel() {
            if (confirm(this.$gettext('Sind Sie sicher, dass Sie das Hochladen abbrechen möchten?'))) {
                if (this.uploadProgress) {
                    this.uploadService.cancel();
                }

                this.uploadService = null;
                this.uploadProgress = null;

                this.$emit('cancel');
            }
        },

        async accept() {
            if (this.uploadProgress) {
                return;
            }

            // make sure lti is authenticated
            await this.$store.dispatch('opencast/authenticateLti');

            if (!this.$refs['upload-form'].reportValidity()) {
                return false;
            }

            // validate file upload
            this.fileUploadError = false;
            if (!this.files['presenter/source'].length && !this.files['presentation/source'].length) {
                this.fileUploadError = true;
            }

            this.fileFormatError = false;

            if (this.files['presenter/source'].length && this.files['presentation/source'].length) {
                let ext1 = this.files['presenter/source'][0].name.split('.').pop();
                let ext2 = this.files['presentation/source'][0].name.split('.').pop();

                if ((ext1 == 'webm' && ext2 == 'mp4') || (ext2 == 'webm' && ext1 == 'mp4')) {
                    this.fileFormatError = true;
                }
            }

            if (this.fileUploadError || this.fileFormatError) {
                // scroll to error message to make it visible to the user
                this.$refs['upload-form'].parentNode.scrollTo({
                    top: 1000,
                    left: 0,
                    behavior: 'smooth',
                });

                return false;
            }

            // get correct upload endpoint url
            this.uploadService = new UploadService({
                ingest: this.selectedServer['ingest'],
            });

            let uploadData = this.upload;

            if (this.cid) {
                uploadData['seriesId'] = this.course_config['series']['series_id'];
            }

            uploadData['created'] = new Date(this.upload.recordDate).toISOString();

            let files = [];
            if (this.files['presenter/source'].length) {
                files.push({
                    file: this.files['presenter/source'][0],
                    flavor: 'presenter/source',
                    progress: {
                        loaded: 0,
                        total: this.files['presenter/source'][0].size,
                    },
                });
            }

            if (this.files['presentation/source'].length) {
                files.push({
                    file: this.files['presentation/source'][0],
                    flavor: 'presentation/source',
                    progress: {
                        loaded: 0,
                        total: this.files['presentation/source'][0].size,
                    },
                });
            }

            // Opencast LTI info of current user
            let ltiUploader = this.currentLTIUser[this.selectedServer['id']];

            let view = this;

            this.uploadService.upload(files, uploadData, this.selectedWorkflow.name, ltiUploader, {
                uploadProgress: (track, loaded, total) => {
                    view.uploadProgress = {
                        flavor: track.flavor,
                        progress: parseInt(Math.round((loaded / total) * 100)),
                    };
                },
                uploadDone: (episode_id, uploadData, workflow_id) => {
                    view.$emit('done');

                    // Add event to database
                    view.$store
                        .dispatch('videos/createVideo', {
                            episode: episode_id,
                            config_id: view.selectedServer.id,
                            title: uploadData.title,
                            description: uploadData.description,
                            state: 'running',
                            presenters: uploadData.creator,
                            contributors: uploadData.contributor,
                        })
                        .then(async ({ data }) => {
                            this.$store.dispatch('messages/addMessage', data.message);

                            // If a playlist is selected, connect event with playlist
                            if (data.event?.token && uploadData.playlist_token) {
                                let playlist = view.playlists.find((p) => p.token === uploadData.playlist_token);
                                if (playlist) {
                                    // Here we need to wait for this action to complete, in order to get the latest videos list in the playlist.
                                    await this.$store
                                        .dispatch('playlists/addVideosToPlaylist', {
                                            playlist: playlist.token,
                                            videos: [data.event.token],
                                            course_id: this.cid,
                                        })
                                        .catch(() => {
                                            this.$store.dispatch('messages/addMessage', {
                                                type: 'warning',
                                                text: this.$gettext(
                                                    'Das erstellte Video konnte der Wiedergabeliste nicht hinzugefügt werden.'
                                                ),
                                            });
                                        });
                                }
                            }

                            this.$store.dispatch('videos/setVideosReload', true);
                        });
                },
                onError: () => {
                    this.$store.dispatch('messages/addMessage', {
                        type: 'error',
                        text: this.$gettext(
                            'Beim Hochladen der Datei ist ein Fehler aufgetreten. Stellen Sie sicher, dass eine Verbindung zum Opencast Server besteht und probieren Sie es erneut.'
                        ),
                        dialog: true,
                    });
                },
            });
        },

        chooseFiles(id) {
            this.$refs[id].click();
        },

        previewFiles(event) {
            let flavor = event.target.attributes['data-flavor'].value;
            this.files[flavor] = event.target.files;
        },
        setDragOver(event, active) {
            event.currentTarget.classList.toggle('is-dragover', active);
        },
        handleDrop(event, refName) {
            const files = event.dataTransfer.files;
            if (!files.length) return;

            const input = this.$refs[refName];
            if (input) {
                input.files = files;

                const changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
            }
        },
    },

    mounted() {
        this.$store.dispatch('opencast/authenticateLti');
        this.$store.dispatch('config/simpleConfigListRead').then(() => {
            this.selectedServer = this.config['server'][this.config.settings['OPENCAST_DEFAULT_SERVER']];
            this.selectedWorkflow = this.defaultWorkflow;
        });

        if (this.cid) {
            this.$store.dispatch('config/loadCourseConfig', this.cid);
        }

        if (this.playlist) {
            this.upload.playlist_token = this.playlist.token;
        }
    },
};
</script>
