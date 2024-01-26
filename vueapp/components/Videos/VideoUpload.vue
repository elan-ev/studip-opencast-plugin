<template>
    <div>
        <StudipDialog
            :title="$gettext('Medien hochladen')"
            :confirmText="$gettext('Hochladen')"
            :confirmClass="uploadButtonClasses"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="confirmCancel"
            @confirm="accept"
        >
            <template v-slot:dialogContent ref="upload-dialog">
                <form class="default" style="max-width: 50em;" ref="upload-form">
                    <fieldset v-if="!uploadProgress">
                        <legend v-translate>
                            Allgemeine Angaben
                        </legend>
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

                        <label>
                            <span class="required" v-translate>
                                Titel
                            </span>

                            <input type="text" maxlength="255"
                                name="title" id="titleField" v-model="upload.title" required>
                        </label>

                        <label v-if="upload_playlists && upload_playlists.length">
                            <span v-translate>
                                Zu Wiedergabeliste hinzufügen
                            </span>

                            <select v-model="upload.playlist_token" required>
                                <option v-for="playlist in upload_playlists"
                                    v-bind:key="playlist.token"
                                    :value="playlist.token">
                                    {{ playlist.title }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span class="required" v-translate>
                                Aufnahmezeitpunkt
                            </span>

                            <input class="oc--datetime-input" type="datetime-local" name="recordDate"
                                id="recordDate" v-model="upload.recordDate" required>
                        </label>

                        <label>
                            <span v-translate>
                                Mitwirkende
                            </span>
                            <input type="text" maxlength="255" id="contributor" name="contributor"
                                   v-model="upload.contributor">
                        </label>

                        <label>
                            <span v-translate>
                                Thema
                            </span>
                            <input type="text" maxlength="255" id="subject"
                                   name="subject" v-model="upload.subject">
                        </label>

                        <label style="display:none">
                            <span v-translate>
                                Sprache
                            </span>
                            <input type="text" maxlength="255" id="language" name="language"
                                v-model="upload.language">
                        </label>

                        <label>
                            <span v-translate>
                                Beschreibung
                            </span>
                            <textarea cols="50" rows="5"
                                id="description" name="description" v-model="upload.description"></textarea>
                        </label>

                    </fieldset>

                    <fieldset>
                        <legend v-translate>
                            Video(s)
                        </legend>

                        <label v-if="!uploadProgress">
                            <span v-translate>
                                Workflow
                            </span>

                            <select v-model="selectedWorkflow" required>
                                <option v-for="workflow in upload_workflows"
                                    v-bind:key="workflow.id"
                                    :value="workflow">
                                    {{ workflow.displayname }}
                                </option>
                            </select>
                        </label>

                        <label for="video_upload">
                            <span class="required" v-translate>
                                Datei(en)
                            </span>
                            <p class="help">
                                {{ uploadFilesText }}
                            </p>
                        </label>

                        <div v-if="!files['presenter/source'].length && !uploadProgress">
                            <label class="oc--file-upload">
                                <StudipButton class="wrap-button" icon="accept" @click.prevent="chooseFiles('oc-file-presenter')">
                                    {{ $gettext('Aufzeichnung des/der Vortragende*n hinzufügen') }}
                                </StudipButton>
                                <input type="file" class="video_upload" data-flavor="presenter/source"
                                    @change="previewFiles" ref="oc-file-presenter"
                                    :accept=uploadFileTypes>
                            </label>

                                   <!--
                            <div style="display:none" class="invalid_media_type_warning">
                              <?= MessageBox::error(
                                  $_('Die gewählte Datei kann von Opencast nicht verarbeitet werden.'),
                                  [
                                      $_('Unterstützte Formate sind .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v, .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2.')
                                  ]
                              ) ?>
                          </div>-->
                        </div>
                        <VideoFilePreview v-else :files="files['presenter/source']"
                            type="presenter" @remove="files['presenter/source']=[]"
                            :uploading="uploadProgress"
                        />

                        <ProgressBar v-if="uploadProgress && uploadProgress.flavor == 'presenter/source'" :progress="uploadProgress.progress" />

                        <div v-if="!files['presentation/source'].length && !uploadProgress">
                            <label class="oc--file-upload">
                                <StudipButton class="wrap-button" icon="accept"  @click.prevent="chooseFiles('oc-file-presentation')">
                                    {{ $gettext('Aufzeichnung der Folien hinzufügen') }}
                                </StudipButton>
                                <input type="file" class="video_upload" data-flavor="presentation/source"
                                    @change="previewFiles" ref="oc-file-presentation"
                                    :accept=uploadFileTypes>
                            </label>
                                      <!--
                            <div style="display:none" class="invalid_media_type_warning">
                              <?= MessageBox::error(
                                  $_('Die gewählte Datei kann von Opencast nicht verarbeitet werden.'),
                                  [
                                      $_('Unterstützte Formate sind .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v, .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2.')
                                  ]
                               ) ?>
                           </div>-->
                        </div>
                        <VideoFilePreview v-else :files="files['presentation/source']"
                            type="presentation"  @remove="files['presentation/source']=[]"
                            :uploading="uploadProgress"
                        />

                        <ProgressBar v-if="uploadProgress && uploadProgress.flavor == 'presentation/source'" :progress="uploadProgress.progress" />

                        <MessageBox v-if="fileUploadError" type="error" v-translate>
                            Sie müssen mindestens eine Datei auswählen!
                        </MessageBox>
                    </fieldset>

                    <MessageBox type="info" v-if="infoText" v-html="infoText">
                    </MessageBox>
                </form>

                <MessageList :float="true" :dialog="true"/>
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
import VideoFilePreview from '@/components/Videos/VideoFilePreview'
import ProgressBar from '@/components/ProgressBar'

import UploadService from '@/common/upload.service'
import { format } from 'date-fns'
import { de } from 'date-fns/locale'

export default {
    name: 'VideoUpload',

    components: {
        StudipDialog,
        MessageBox,
        StudipButton,
        VideoFilePreview,
        ProgressBar,
        MessageList
    },

    emits: ['done', 'cancel'],

    props: ['currentUser'],

    data () {
        return {
            showAddEpisodeDialog: false,
            selectedServer: false,
            selectedWorkflow: false,
            fileUploadError: false,
            upload: {
                creator: this.currentUser.username,
                contributor: this.currentUser.fullname,
                playlist_token: null,
                recordDate: format(new Date(), "yyyy-MM-dd'T'HH:mm", { locale: de}),
                subject: this.$gettext('Medienupload, Stud.IP')
            },
            files: {
                'presenter/source': [],
                'presentation/source': []
            },
            uploadProgress: null,
        }
    },

    computed: {
        ...mapGetters({
            'config'       : 'simple_config_list',
            'course_config': 'course_config',
            'cid'          : 'cid',
            'playlist'     : 'playlist',
            'playlists'    : 'playlists'
        }),

        upload_playlists() {
            let upload_playlists = this.playlists

            if (!this.playlists.length) {
                return null;
            }

            if (!this.playlist) {
                upload_playlists.unshift({
                    token: null,
                    title: this.$gettext('Keiner Wiedergabeliste hinzufügen')
                })
            }

            return upload_playlists;
        },

        upload_workflows() {
            return this.config['workflows'].filter(wf => wf['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER'] && wf['tag'] === 'upload');
        },

        uploadFileTypes() {
            return this.selectedWorkflow?.settings?.upload_file_types || this.config.default_upload_file_types;
        },

        uploadFilesText() {
            let fileTypes = this.uploadFileTypes.split(',')
                .map(type => type.trim())
                .filter(type => type.search(/^\./) !== -1);    // Only show types starting with a dot, e.g. ".mp4"

            return this.$gettext('Mindestens eine Aufzeichnung wird benötigt. Unterstützte Formate: %{ file_types }.', {
                file_types: fileTypes.join(', '),
            })
        },

        uploadButtonClasses() {
            if (this.uploadProgress) {
                return 'accept disabled';
            }

            return 'accept';
        },

        defaultWorkflow() {
            let wf_id = this.config['workflow_configs'].find(wf_config =>
                wf_config['config_id'] == this.config.settings['OPENCAST_DEFAULT_SERVER']
                    && wf_config['used_for'] === 'upload'
            )['workflow_id'];

            return this.config['workflows'].find(wf => wf['id'] == wf_id);
        },

        infoText()
        {
            try {
                let info = JSON.parse(this.config.settings.OPENCAST_UPLOAD_INFO_TEXT_BODY);
                return info[this.config.user_language];
            } catch (e) {

            }

            return null;
        }
    },

    methods: {
        confirmCancel()
        {
            if (confirm(this.$gettext('Sind sie sicher, dass sie das Hochladen abbrechen möchten?'))) {
                if (this.uploadProgress) {
                    this.uploadService.cancel();
                }

                this.uploadService  = null;
                this.uploadProgress = null;

                this.$emit('cancel');
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
            this.fileUploadError = false;
            if (
                !this.files['presenter/source'].length &&
                !this.files['presentation/source'].length
            ) {
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

            let uploadData         = this.upload;

            if (this.cid) {
                uploadData['seriesId'] = this.course_config['series']['series_id'];
            }

            uploadData['created']  = new Date(this.upload.recordDate).toISOString();

            let files = [];
            if (this.files['presenter/source'].length) {
                files.push({
                    file: this.files['presenter/source'][0],
                    flavor: 'presenter/source',
                    progress: {
                        loaded: 0,
                        total: this.files['presenter/source'][0].size
                    }
                })
            }

            if (this.files['presentation/source'].length) {
                files.push({
                    file: this.files['presentation/source'][0],
                    flavor: 'presentation/source',
                    progress: {
                        loaded: 0,
                        total: this.files['presentation/source'][0].size
                    }
                })
            }

            let view = this;

            this.uploadService.upload(files, uploadData, this.selectedWorkflow.name, {
                uploadProgress: (track, loaded, total) => {
                    view.uploadProgress = {
                        flavor: track.flavor,
                        progress: parseInt(Math.round((loaded / total) * 100 ))
                    }
                },
                uploadDone: (episode_id, uploadData, workflow_id) => {
                    view.$emit('done');
                    view.$store.dispatch('createLogEvent', {
                        event: 'upload',
                        data: {
                            episode_id: episode_id,
                            workflow_id: workflow_id
                        }
                    });

                    // Add event to database
                    view.$store.dispatch('createVideo', {
                        'episode': episode_id,
                        'config_id': view.selectedServer.id,
                        'title': uploadData.title,
                        'description': uploadData.description,
                        'state': 'running'
                    })
                    .then(({ data }) => {
                        this.$store.dispatch('addMessage', data.message);

                        // If a playlist is selected, connect event with playlist
                        if (data.event?.token && uploadData.playlist_token) {
                            let playlist = view.playlists.find(p => p.token === uploadData.playlist_token);
                            if (playlist) {
                                this.$store.dispatch('addVideosToPlaylist', {
                                    playlist: playlist.token,
                                    videos: [data.event.token],
                                })
                            }
                        }

                        this.$store.dispatch('setVideosReload', true);
                    });
                },
                onError: (response) => {
                    this.$store.dispatch('addMessage', {
                        type: 'error',
                        text: response,
                        dialog: true
                    });
                }
            });
        },

        chooseFiles(id) {
            this.$refs[id].click();
        },

        previewFiles(event) {
            let flavor = event.target.attributes['data-flavor'].value;
            this.files[flavor] = event.target.files;
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
        this.$store.dispatch('simpleConfigListRead').then(() => {
            this.selectedServer = this.config['server'][this.config.settings['OPENCAST_DEFAULT_SERVER']];
            this.selectedWorkflow = this.defaultWorkflow;
        })

        if (this.cid) {
            this.$store.dispatch('loadCourseConfig', this.cid);
        }

        if (this.playlist) {
            this.upload.playlist_token = this.playlist.token;
        }
    }
}
</script>
