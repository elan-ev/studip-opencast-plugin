<template>
    <div>
        <ScheduleLoading />
        <MessageList/>
        <h2>Aufzeichnungen planen</h2>
        <ScheduleList></ScheduleList>
    </div>
</template>

<script>
import ScheduleList from "@/components/Schedule/ScheduleList";
import ScheduleLoading from "@/components/Schedule/ScheduleLoading";
import MessageList from "@/components/MessageList";

export default {
    name: "Schedule",
    components: {
        ScheduleList,
        ScheduleLoading,
        MessageList
    }
};
</script>
