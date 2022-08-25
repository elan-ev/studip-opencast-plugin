<template>
    <div>
        <MessageList/>
        <h2>Aufzeichnungen planen</h2>
        <ScheduleList></ScheduleList>
    </div>
</template>

<script>
import ScheduleList from "@/components/Schedule/ScheduleList";
import MessageList from "@/components/MessageList";

export default {
    name: "Schedule",
    components: {
        ScheduleList,
        MessageList
    }
};
</script>
