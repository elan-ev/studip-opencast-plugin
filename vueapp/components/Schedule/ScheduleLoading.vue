<template>
    <div ref="main">
        <table class="default oc--schedule-list">
            <colgroup>
                <col style="width: 2%">
                <col style="width: 30%">
                <template v-if="allow_schedule_alternate">
                    <col style="width: 28%">
                    <col style="width: 28%">
                </template>
                <template v-else>
                    <col style="width: 56%">
                </template>
                <col style="width: 5%">
                <col style="width: 7%">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th v-translate>Termin</th>
                    <template v-if="allow_schedule_alternate">
                        <th v-translate>Aufzeichnungszeitraum</th>
                    </template>
                    <th v-translate>Titel</th>
                    <th v-translate>Status</th>
                    <th v-translate>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td><span class="oc--loadingbar oc--loadingbar-title"></span></td>
                    <template v-if="allow_schedule_alternate">
                        <td><span class="oc--loadingbar"></span></td>
                        <td><span class="oc--loadingbar"></span></td>
                    </template>
                    <template v-else>
                        <td><span class="oc--loadingbar"></span></td>
                    </template>
                    <td><span class="oc--loadingbar"></span></td>
                    <td><span class="oc--loadingbar"></span></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
    name: "ScheduleLoading",

    props: ['allow_schedule_alternate']
}
</script>