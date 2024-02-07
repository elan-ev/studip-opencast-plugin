<template>
    <div>
        <table v-if="schedule_list.length" class="default oc--schedule-list">
            <colgroup>
                <col style="width: 2%">
                <col style="width: 30%">
                <template v-if="allow_schedule_alternate">
                    <col style="width: 25%">
                    <col style="width: 25%">
                </template>
                <template v-else>
                    <col style="width: 50%">
                </template>
                <col style="width: 5%">
                <col style="width: 13%">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>{{ $gettext('Termin') }}</th>
                    <template v-if="allow_schedule_alternate">
                        <th>{{ $gettext('Aufzeichnungszeitraum') }}</th>
                    </template>
                    <th>{{ $gettext('Titel') }}</th>
                    <th class="oc-schedule-status">{{ $gettext('Status') }}</th>
                    <th class="oc-schedule-actions">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(date, index) in schedule_list" :key="index">
                    <td>
                        <input type="checkbox" :ref="setBulkRef" :id="`dates[${date.termin_id}`" :value="date.termin_id" :data-index="index" :disabled="!date.allow_bulk">
                    </td>
                    <td v-html="date.termin_title"></td>
                    <template v-if="allow_schedule_alternate && date?.recording_period">
                        <template v-if="typeof date.recording_period === 'string'">
                            <td><span style="color: lightgray">{{ date.recording_period }}</span></td>
                        </template>
                        <template v-else>
                            <td class="oc-schedule-slider">
                                <div class="slider-text">
                                    {{ getSliderText(date.recording_period.start, date.recording_period.end) }}
                                </div>
                                <StudipSlider
                                    :value="[date.recording_period.start, date.recording_period.end]"
                                    @sliderChanged="getSliderValue"
                                    :callbackParams="{
                                        index: index,
                                    }"
                                    :id="`schedule_slider_${index}`"
                                    :min="date.recording_period.range_start"
                                    :max="date.recording_period.range_end"
                                    :step="5"
                                    :tooltips="false"
                                />
                            </td>
                        </template>
                    </template>
                    <td>{{ date.title }}</td>
                    <td class="oc-schedule-status">
                        <span v-if="date.status?.info" :title="date.status.title" :class="[date.status?.info_class]">{{ date.status.info }}</span>
                        <StudipIcon v-else :shape="date.status.shape" :role="date.status.role" :title="date.status.title"/>
                    </td>
                    <td class="oc-schedule-actions">
                        <div class="oc-schedule-action-item-wrapper">
                            <template v-for="(action, index) in date.actions" :key="index">
                                <a v-if="index != 'expire'" href="#" @click.stop="performAction($event, index, date.termin_id)" class="oc-schedule-action-item">
                                    <StudipIcon v-if="index != 'scheduleLive'" :shape="action.shape" :role="action.role" :title="action?.title ? action.title : ''"/>
                                    <span v-else style="font-weight: bold" :title="action?.title ? action.title : ''">
                                        {{ action.info }}
                                    </span>
                                </a>
                                <StudipIcon v-else :shape="action.shape" :role="action.role" :title="action?.title ? action.title : ''" class="oc-schedule-action-item"/>
                            </template>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="thead"><input type="checkbox" ref="checkall" id="checkall" @click="bulkSelection($event)"></td>
                    <td class="thead" :colspan="allow_schedule_alternate ? 6 : 5">
                        <select v-model="bulkAction">
                            <option value="" disabled selected>{{ $gettext('Bitte wählen Sie eine Aktion.') }}</option>
                            <option v-for="(opt, i) in get_bulk_actions" :key="i" :value="opt.value">
                                {{ opt.text }}
                            </option>
                        </select>
                        <StudipButton icon="accept" @click.prevent="performBulkAction">
                            <span>{{ $gettext('Übernehmen') }}</span>
                        </StudipButton>
                        <StudipButton icon="cancel" @click.prevent="resetBulk">
                            <span>{{ $gettext('Abbrechen') }}</span>
                        </StudipButton>
                    </td>
                </tr>
            </tfoot>
        </table>
        <MessageBox v-else-if="!schedule_loading" type="info">
            {{ $gettext('Es gibt bisher keine Termine.') }}
        </MessageBox>
         <ScheduleLoading v-else :allow_schedule_alternate="allow_schedule_alternate"/>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import MessageBox from '@/components/MessageBox.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import StudipSlider from '@studip/StudipSlider.vue';
import StudipButton from '@studip/StudipButton.vue';
import ScheduleLoading from "@/components/Schedule/ScheduleLoading";

export default {
    name: "ScheduleList",

    components: {
        MessageBox,
        StudipIcon,
        StudipSlider,
        StudipButton,
        ScheduleLoading
    },

    data() {
        return {
            sliderRefs: [],
            bulkRefs: [],
            bulkAction: '',
            refreshTimeout: null,
        }
    },

    computed: {
        ...mapGetters(["schedule_list", "allow_schedule_alternate", "cid", 'schedule_loading']),

        get_bulk_actions() {
            let bulk_actions = [
                {value: 'schedule', text: this.$gettext('Aufzeichnungen planen')},
                {value: 'live', text: this.$gettext('LIVE-Aufzeichnungen planen')},
                {value: 'update', text: this.$gettext('Aufzeichnungen aktualisieren')},
                {value: 'unschedule', text: this.$gettext('Aufzeichnungen stornieren')},
            ];

            return bulk_actions;
        }
    },

    methods: {
        performBulkAction() {
            if (!this.cid) {
                // This should not have happened, if so, we do nothing.
                this.addMesssage('error', this.$gettext('Es ist ein Fehler aufgetreten'), true);
                console.log('There is no cid!');
                return;
            }
            if (this.bulkAction == '') {
                this.addMesssage('error', this.$gettext('Es ist ein Fehler aufgetreten'), true);
                console.log('No action available!');
                return;
            }

            let selected_refs = this.bulkRefs.filter((ref) => {
                return !ref.disabled && ref.checked;
            });
            if (!selected_refs.length) {
                this.addMesssage('error', this.$gettext('Es ist ein Fehler aufgetreten'), true);
                console.log('No Selected items!');
                return;
            }
            let termin_ids = selected_refs.map(ref => ref.value);
            let params = {
                action: this.bulkAction,
                termin_ids: termin_ids,
            };
            this.$store.dispatch('bulkScheduling', params).then(({ data }) => {
                this.$store.dispatch('clearMessages');
                if (data?.message) {
                    this.addMesssage(data.message.type, data.message.text, true);
                }
                this.$store.dispatch('getScheduleList');
            });

        },

        resetBulk() {
            this.bulkAction = '';
            this.$refs.checkall.checked = false;
            this.performBulkSelection(false);
        },

        bulkSelection(event) {
            let state = event.target.checked;
            this.performBulkSelection(state);
        },

        performBulkSelection(state) {
            this.bulkRefs.forEach((el) => {
                if (el.disabled == false) {
                    el.checked = state;
                }
            });
        },

        setBulkRef(el) {
            let element_index = el?.dataset?.index ? el.dataset.index : false;
            if (element_index) {
                let existing_ref = this.bulkRefs.find((ref) => {
                    return ref?.dataset?.index && ref.dataset.index == element_index;
                });
                if (!existing_ref) {
                    this.bulkRefs.push(el);
                }
            }
        },

        performAction(event, dispatchAction, termin_id) {
            if (event) {
                event.preventDefault();
            }

            if (!this.cid) {
                // This should not have happened, if so, we do nothing.
                this.addMesssage('error', this.$gettext('Es ist ein Fehler aufgetreten'), true);
                console.log('There is no cid!');
                return;
            }

            if (!Object.keys(this.$store._actions).includes(dispatchAction)) {
                this.addMesssage('error', this.$gettext('Es ist ein Fehler aufgetreten'), true);
                console.log('No action available!');
                return;
            }

            if (dispatchAction == 'unschedule' && !confirm(
                    this.$gettext('Sind sie sicher, dass sie die geplante Aufzeichnung entfernen möchten?')
            )) {
                return;
            }

            this.$store.dispatch(dispatchAction, termin_id).then(({ data }) => {
                if (data?.message && dispatchAction != 'unschedule') {
                    this.addMesssage(data.message.type, data.message.text, true);
                    if (data.message.type == 'success') {
                        this.$store.dispatch('getScheduleList');
                    }
                } else {
                    if (dispatchAction == 'unschedule') {
                        this.addMesssage('success', this.$gettext('Die geplante Aufzeichnung wurde entfernt.'), true);
                    }
                    this.$store.dispatch('getScheduleList');
                }
            });
        },

        getSliderValue(args) {
            this.schedule_list[args.index]['recording_period']['start'] = parseInt(args.value[0]);
            this.schedule_list[args.index]['recording_period']['end'] = parseInt(args.value[1]);

            let params = {
                termin_id: this.schedule_list[args.index]['termin_id'],
                start: this.schedule_list[args.index]['recording_period']['start'],
                end: this.schedule_list[args.index]['recording_period']['end'],
            }
            this.$store.dispatch('updateRecordingPeriod', params).then(({ data }) => {
                this.addMesssage(data.message.type, data.message.text, true);
            });
        },

        getSliderText(start, end) {
            let start_int = parseInt(start);
            let end_int = parseInt(end);
            if (!start_int || !end_int) {
                return '';
            }
            return  Math.floor(start_int / 60).toString().padStart(2, '0')
                    + ':' + (start_int - Math.floor(start_int / 60) * 60).toString().padStart(2, '0')
                    + ' - ' + Math.floor(end_int / 60).toString().padStart(2, '0')
                    + ':' + (end_int - Math.floor(end_int / 60) * 60).toString().padStart(2, '0') ;
        },

        addMesssage(type, text, is_new = true) {
            if (is_new) {
                this.$store.dispatch('clearMessages');
            }
            this.$store.dispatch('addMessage', {
                'type': type,
                'text': text
            });
        },

        initLivestreamRefreshTimer() {
            let nearest_refresh_time = 0;
            for (let date of this.schedule_list) {
                if (date?.status?.referesh_at) {
                    let refresh_at = parseInt(date.status.referesh_at);
                    if (nearest_refresh_time == 0 || nearest_refresh_time > refresh_at) {
                        nearest_refresh_time = refresh_at;
                    }
                }
            }
            if (nearest_refresh_time == 0) {
                if (this.refreshTimeout != null) {
                    window.clearTimeout(this.refreshTimeout);
                    this.refreshTimeout = null;
                }
                return;
            }
            let now = (new Date()).getTime();
            let timeout = (nearest_refresh_time * 1000) - now;
            this.refreshTimeout = setTimeout(() => {
                this.$store.dispatch('getScheduleList');
            }, timeout);
        }
    },

    mounted () {
        this.initLivestreamRefreshTimer();
    },

    updated () {
        this.initLivestreamRefreshTimer();
    },

    beforeDestroy() {
        if (this.refreshTimeout != null) {
            window.clearTimeout(this.refreshTimeout);
        }
    },
}
</script>
