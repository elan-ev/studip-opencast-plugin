<template>
    <div>
        <StudipDialog
            :title="$gettext('Episode hinzufügen')"
            :confirmText="$gettext('Hochladen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default"
                    style="max-width: 50em;"
                >
                    <fieldset>
                        <legend v-translate>
                            Allgemeine Angaben
                        </legend>
                        <label>
                            <span class="required" v-translate>
                                Serie auswählen:
                            </span>

                            <select>
                                <option v-for="series in course_series"
                                    :value="series.series_id"
                                >
                                    {{ series.details.title }}
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


                        <label>
                            <span class="required" v-translate>
                                Vortragende
                            </span>

                            <input type="text" maxlength="255"
                                name="creator" id="creator" v-model="upload.creator" required>
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

                        <label>
                            <span v-translate>
                                Workflow
                            </span>

                            <select v-model="upload.workflow">
                                <option v-for="workflow in upload_workflows"
                                    :value="workflow.id">
                                    {{ workflow.name }}
                                </option>
                            </select>
                        </label>

                        <label for="video_upload">
                            <span class="required" v-translate>
                                Datei(en)
                            </span>
                            <p class="help" v-translate>
                                Mindestens ein Video wird benötigt. Unterstützte Formate sind
                                .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v,
                                .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2
                            </p>
                        </label>

                        <div v-if="!files['presenter/source'].length">
                            <StudipButton icon="accept" v-translate @click.prevent="chooseFiles('oc-file-presenter')">
                                Aufzeichnung des/der Vortragende*n hinzufügen
                            </StudipButton>
                            <input type="file" class="video_upload" data-flavor="presenter/source"
                                @change="previewFiles" ref="oc-file-presenter"
                                accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">

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
                        <FilePreview v-else :files="files['presenter/source']"
                            type="presenter" @remove="files['presenter/source']=[]"/>

                        <div v-if="!files['presentation/source'].length">
                            <StudipButton icon="accept" v-translate  @click.prevent="chooseFiles('oc-file-presentation')">
                                Aufzeichnung der Folien hinzufügen
                            </StudipButton>
                            <input type="file" class="video_upload" data-flavor="presentation/source"
                                @change="previewFiles" ref="oc-file-presentation"
                                accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">
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
                        <FilePreview v-else :files="files['presentation/source']"
                            type="presentation"  @remove="files['presentation/source']=[]"/>
                    </fieldset>

                    <MessageBox type="info" v-translate>
                            <b>Laden Sie nur Medien hoch, an denen Sie das Nutzungsrecht besitzen!</b><br />
                            Nach §60 UrhG dürfen nur maximal 5-minütige Sequenzen aus urheberrechtlich geschützten
                            Filmen oder Musikaufnahmen bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen.
                            <a href="https://elan-ev.de/themen_p60.php" target="_blank">
                                §60 UrhG Zusammenfassung
                            </a><br />
                            Medien, bei denen Urheberrechtsverstöße vorliegen, werden ohne vorherige Ankündigung umgehend gelöscht.
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
import FilePreview from '@/components/Episodes/FilePreview'

import { format } from 'date-fns'
import { de } from 'date-fns/locale'

export default {
    name: 'EpisodeAdd',

    components: {
        StudipDialog,   MessageBox,     StudipButton,
        FilePreview
    },

    props: ['currentUser'],

    data () {
        return {
            showAddEpisodeDialog: false,
            upload: {
                creator: this.currentUser.fullname,
                contributor: this.currentUser.fullname,
                workflow: 'upload',
                recordDate: format(new Date(), "yyyy-MM-dd'T'HH:ii", { locale: de}),
                subject: this.$gettext('Medienupload, Stud.IP')
            },
            files: {
                'presenter/source': [],
                'presentation/source': []
            }
        }
    },

    computed: {
        ...mapGetters(['course_series']),

        upload_workflows() {
            // TODO
            return [{
                id:   'upload',
                name: 'Standard'
            }]
        }
    },

    methods: {
        accept() {
            this.$store.dispatch('addEvent',
                {
                    id: this.upload['id'],
                    title: this.upload['title'],
                    author: this.upload['author'],
                    type: this.upload['type']
                }
            );
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
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
        this.$store.dispatch('loadCourseSeries');
    }
}
</script>
