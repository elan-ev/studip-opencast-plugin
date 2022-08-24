<template>
    <div>
        <li class="oc--playlist-item">
            <form class="default">
            <input type="text" :placeholder="$gettext('Titel der Wiedergabeliste')">
            <select class="size-s">
                <option value="internal">
                    {{ $gettext('Intern') }}
                </option>
                <option value="internal">
                    {{ $gettext('Nicht gelistet') }}
                </option>
                <option value="internal">
                    {{ $gettext('Öffentlich') }}
                </option>
            </select>
            </form>
        </li>
    </div>
</template>

<script>

export default {
    name: "PlaylistAddCard",

    data() {

    },
}
</script>