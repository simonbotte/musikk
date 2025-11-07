<script setup lang="ts">
definePageMeta({
    middleware: "auth",
});

const nuxtApp = useNuxtApp();
const { user } = useAuth();
const route = useRoute();
const { getPlaylist, addMusicToPlaylist, getCollaborations } = useApi();
const { getColor } = useFastAverageColor();
const colorMode = useColorMode();
const sectionToDisplay = ref<string>("songs");
colorMode.preference = "light";

const userUuid = route.params.userUuid as string;
const playlistUuid = route.params.playlistUuid as string;

const playlist = ref<Playlist | null>(null);
const collaborations = ref<Collaboration[]>([]);
const averageColor = ref();
const bgStyle = ref("");
const isAccentColorDark = ref(false);
const displayAddSong = ref(false);

playlist.value = await getPlaylist(userUuid, playlistUuid);
collaborations.value = await getCollaborations(playlistUuid);
if (playlist.value?.artwork !== undefined) {
    averageColor.value = await getColor(playlist.value.artwork);
} else {
    averageColor.value = { hex: "#edac7e" };
}

isAccentColorDark.value = averageColor.value.isDark;

useHead({
    meta: [{ name: "theme-color", content: averageColor.value.hex }],
    bodyAttrs: { style: `background-color: ${averageColor.value.hex};` },
});

const toggleAddSong = () => {
    displayAddSong.value = !displayAddSong.value;
};

const onAddButtonClick = async (song: Song) => {
    if (!playlist.value) return;

    toggleAddSong();

    try {
        const addedSong = await addMusicToPlaylist(song, userUuid, playlistUuid);
        if (addedSong && playlist.value) {
            playlist.value = {
                ...playlist.value,
                songs: [...(playlist.value.songs || []), addedSong.song],
            };
        }
    } catch (error) {
        console.error("Error adding song to playlist:", error);
    }
};

watch(displayAddSong, (value: boolean) => {
    document.body.style.overflow = value ? "hidden" : "auto";
});

onMounted(() => {
    bgStyle.value = `background: ${averageColor.value.hex};`;
});

nuxtApp.hook("update:collaborations", (updatedCollaborations) => {
    collaborations.value = updatedCollaborations;
});
</script>

<template>
    <div :style="`${bgStyle}`" class="relative w-full flex items-center justify-center p-4">
        <div class="flex flex-col items-center gap-4 w-full">
            <div class="flex justify-between items-start w-full gap-2">
                <UButton
                    :to="{ name: 'playlist-userUuid', params: { userUuid } }"
                    icon="material-symbols:arrow-back-ios-new-rounded"
                    color="neutral"
                    size="xl"
                    variant="translucent"
                ></UButton>
                <UFieldGroup orientation="horizontal">
                    <UDrawer inset>
                        <UButton icon="material-symbols:group" color="neutral" size="xl" variant="translucent" />
                        <template #content>
                            <PlaylistCollaboration
                                :collaborations="collaborations"
                                :playlist-uuid="playlistUuid"
                                :user-uuid="userUuid"
                            />
                        </template>
                    </UDrawer>

                    <UButton
                        icon="material-symbols:more-horiz"
                        color="neutral"
                        size="xl"
                        variant="translucent"
                    ></UButton>
                </UFieldGroup>
            </div>
            <div v-if="playlist?.artwork !== undefined" class="w-60">
                <AppleMusicArtwork :url="playlist.artwork" :size="100" :alt="`${playlist.name} artwork`" />
            </div>
            <div v-else class="w-60 h-60 bg-linear-65 from-orange-200 to-orange-500 aspect-square px-2 py-1 rounded-lg">
                <span class="text-lg font-bold">{{ playlist.name }}</span>
            </div>
            <div class="flex flex-col items-center gap-2">
                <h1
                    class="opacity-60 text-3xl leading-9 font-bold text-center"
                    :class="{ 'text-white': isAccentColorDark, 'text-black': !isAccentColorDark }"
                >
                    {{ playlist.name }}
                </h1>
                <p
                    class="opacity-50 leading-3 text-xs font-medium"
                    :class="{ 'text-white': isAccentColorDark, 'text-black': !isAccentColorDark }"
                >
                    Updated 3min ago
                </p>
            </div>
        </div>
    </div>
    <div v-if="sectionToDisplay === 'songs'" class="bg-white min-h-screen py-4">
        <div class="flex flex-col gap-4 px-4 transition origin-top max-w-full" :class="{ 'scale-90': displayAddSong }">
            <div class="flex gap-2 justify-between items-center" @click="toggleAddSong">
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
