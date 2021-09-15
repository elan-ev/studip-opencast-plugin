import CoursewareOpencastBlock from "./courseware-plugin-opencast-video.vue";

window.STUDIP.eventBus.on("courseware:init-plugin-manager", (pluginManager) => {
    pluginManager.addBlock("courseware-opencast-block", CoursewareOpencastBlock);
});

export default CoursewareOpencastBlock;
