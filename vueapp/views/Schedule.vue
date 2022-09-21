<template>
    <div>
        <h2>Aufzeichnungen planen</h2>
        <ScheduleList></ScheduleList>
    </div>
</template>

<script>
import ScheduleList from "@/components/Schedule/ScheduleList";

export default {
    name: "Schedule",
    components: {
        ScheduleList
    }
};
</script>
