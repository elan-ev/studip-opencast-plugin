<template>
    <div class="video-card">
        <div>checkbox</div>
        <div>Image</div>
        <div>
            <div>{{ video.title }}</div>
            <div>Tags</div>
        </div>
        <div>QuickActions</div>
        <div>Sichtbarkeit</div>
        <div>Aufrufe</div>
        <div>Erstellt am</div>
    </div>
</template>

<script>

export default({
    name: "VideoCard",
    props: {
        video: Object
    }
})
</script>