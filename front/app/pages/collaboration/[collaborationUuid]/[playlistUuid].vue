<script setup lang="ts">
const route = useRoute();
const { getPlaylistFromCollaboration, addMusicToPlaylistFromCollaboration, getCollaboration } = useApi();
const { getColor } = useFastAverageColor();
const colorMode = useColorMode();
colorMode.preference = "light";

const collaborationUuid = route.params.collaborationUuid as string;
const playlistUuid = route.params.playlistUuid as string;

const playlist = ref<Playlist | null>(null);
const collaboration = ref<Collaboration | null>(null);
const averageColor = ref();
const bgStyle = ref("");
const displayAddSong = ref(false);

playlist.value = await getPlaylistFromCollaboration(collaborationUuid, playlistUuid);
collaboration.value = await getCollaboration(collaborationUuid);

const canAddSoungCount = computed(() => {
    if (collaboration.value?.addLimit !== null) {
        return collaboration.value.addLimit - (collaboration.value.addedSongsCount || 0);
    }
    return null;
});

if (playlist.value?.artwork !== undefined) {
    averageColor.value = await getColor(playlist.value.artwork);
} else {
    averageColor.value = { hex: "#edac7e" };
}

bgStyle.value = `
background: ${averageColor.value.hex};`;

useHead({
    meta: [{ name: "theme-color", content: averageColor.value.hex }],
    bodyAttrs: { style: `background-color: ${averageColor.value.hex};` },
});

const toggleAddSong = () => {
    displayAddSong.value = !displayAddSong.value;
    console.log(displayAddSong.value);
};

const onAddButtonClick = async (song: Song) => {
    if (!playlist.value) return;

    toggleAddSong();

    try {
        const addedSong = await addMusicToPlaylistFromCollaboration(song, collaborationUuid, playlistUuid);
        if (addedSong && playlist.value) {
            playlist.value = {
                ...playlist.value,
                songs: [...(playlist.value.songs || []), addedSong.song],
            };
            console.log("Song added to playlist:", addedSong.title);
        }

        collaboration.value = await getCollaboration(collaborationUuid);
    } catch (error) {
        console.error("Error adding song to playlist:", error);
    }
};

watch(displayAddSong, (value: boolean) => {
    document.body.style.overflow = value ? "hidden" : "auto";
});
</script>

<template>
    <div :style="`${bgStyle}`" class="relative w-full flex items-center justify-center p-4">
        <div class="flex flex-col items-center gap-4 w-full">
            <div class="flex justify-between items-start w-full">
                <UButton
                    icon="material-symbols:arrow-back-ios-new-rounded"
                    color="neutral"
                    size="xl"
                    variant="translucent"
                ></UButton>
                <div v-if="playlist?.artwork !== undefined" class="w-60 h-60">
                    <AppleMusicArtwork :url="playlist.artwork" :size="100" :alt="`${playlist.name} artwork`" />
                </div>
                <div
                    v-else
                    class="w-60 h-60 bg-linear-65 from-orange-200 to-orange-500 aspect-square px-2 py-1 rounded-lg"
                >
                    <span class="text-lg font-bold">{{ playlist.name }}</span>
                </div>
                <UButton icon="material-symbols:more-horiz" color="neutral" size="xl" variant="translucent"></UButton>
            </div>
            <div class="flex flex-col items-center gap-2">
                <h1 class="text-white text-4xl leading-9 font-bold text-center">{{ playlist.name }}</h1>
                <p class="text-white opacity-80 leading-3 text-xs font-medium">Updated 3min ago</p>
            </div>
            <div v-if="canAddSoungCount !== null">
                <p class="text-white opacity-80 leading-3 text-xs font-medium">
                    You can add {{ canAddSoungCount }} {{ canAddSoungCount > 1 ? "songs" : "song" }}
                </p>
            </div>
        </div>
    </div>
    <div class="bg-white min-h-screen py-4">
        <div class="flex flex-col gap-4 px-4 transition origin-top max-w-full" :class="{ 'scale-90': displayAddSong }">
            <div
                v-if="canAddSoungCount !== null && canAddSoungCount > 0"
                class="flex gap-2 justify-between items-center"
                @click="toggleAddSong"
            >
                <div class="flex gap-2">
                    <div class="w-12">
                        <div
                            class="bg-neutral-200/50 border border-neutral-300/50 h-12 w-12 rounded-lg flex justify-center items-center"
                        >
                            <Icon name="ic:round-plus" size="24" />
                        </div>
                    </div>
                    <div class="flex flex-col justify-center gap-2">
                        <p class="text-neutral">Add song</p>
                    </div>
                </div>
            </div>
            <div
                v-for="song in playlist.songs"
                class="flex gap-2 justify-between items-center max-w-full"
                :key="`${song.id}-${song.service}`"
            >
                <div class="flex gap-2 min-w-0 max-w-full">
                    <div class="w-12 shrink-0">
                        <SongArtwork :song="song" :size="48" :service="playlist.service" />
                    </div>
                    <div class="flex flex-col justify-center min-w-0">
                        <p class="text-neutral">{{ song.title }}</p>
                        <span class="text-neutral opacity-50 text-sm truncate min-w-0">{{ song.artist }}</span>
                    </div>
                </div>
                <span class="opacity-50 shrink-0">2:49</span>
            </div>
        </div>
        <div
            v-if="canAddSoungCount !== null && canAddSoungCount > 0"
            :class="`fixed px-2 z-50 left-0 h-7/8 w-full transition overflow-hidden ${
                displayAddSong ? 'bottom-2' : 'translate-y-full bottom-0'
            }`"
            ref="addSongContainer"
        >
            <div
                class="bg-neutral-200/50 border border-neutral-300/50 backdrop-blur-md p-4 w-full h-full rounded-2xl flex flex-col"
            >
                <div class="flex gap-2 items-center mb-4">
                    <UButton
                        @click="toggleAddSong"
                        :icon="displayAddSong ? 'ic:close' : 'ic:baseline-add'"
                        color="neutral"
                        size="xl"
                        variant="translucent"
                    ></UButton>
                    <h3 class="text-2xl font-bold">Search a song</h3>
                </div>

                <Search :service="playlist.service" v-on:add-button-click="onAddButtonClick" />
            </div>
        </div>
    </div>
</template>
