<script lang="ts" setup>

const props = withDefaults(
    defineProps<{
        song: Song;
        size?: number;
        service: "apple_music" | "spotify";
    }>(),
    {}
);

const url = computed(() => {
    const size = props.size ?? 300;
    let url = "";
    if (props.service === "apple_music") {
        url = props.song.artworks.apple_music_artwork ?? "";
        url = url !== "" ? url.replace("{w}", (size * 2).toString()).replace("{h}", (size * 2).toString()) : "";
    }
    return url;
});
</script>

<template>
    <img v-if="url !== ''" class="rounded-lg w-full" :src="url" :alt="`${song.title} artwork`" />
</template>
