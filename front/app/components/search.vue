<script lang="ts" setup>
const props = withDefaults(
    defineProps<{
        service: "apple_music" | "spotify";
        onAddButtonClick: (song: Song) => void;
        closeOnAdd?: boolean;
    }>(),
    {
        closeOnAdd: true,
    }
);

const route = useRoute();
const user = useCookie<User | null>("user");
const query = ref<string>("");
const songs = ref<Song[]>([]);
const temporizer = ref<ReturnType<typeof setTimeout> | null>(null);
const { searchSongs } = useApi();
const loading = ref(false);
const search = async () => {
    if (temporizer.value) {
        clearTimeout(temporizer.value);
    }
    
    temporizer.value = setTimeout(async () => {
        if (query.value.length < 2) {
            songs.value = [];
            return;
        }
        loading.value = true;
        songs.value = (await searchSongs(query.value, props.service)) ?? [];
        loading.value = false;
    }, 500);
};

const addButtonClick = (song: Song) => {
    props.onAddButtonClick(song);
    if (props.closeOnAdd) {
        query.value = "";
        songs.value = [];
    }
};
</script>

<template>
    <div class="flex flex-col w-full h-full">
        <div class="shrink-0">
            <UInput :loading="loading" icon="material-symbols:search-rounded" size="md" variant="translucent" name="search-song" placeholder="Search for a song" class="w-full" v-model="query" @input="search"/>
        </div>

        <div class="flex-1 overflow-y-auto mt-2 scrollbar-hidden">
            <div class="flex flex-col w-full pb-10">
                <div
                    v-for="song in songs"
                    :key="song.id"
                    class="flex gap-2 justify-between py-2 max-w-full"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-[48px] shrink-0">
                            <SongArtwork :song="song" :size="48" :service="service" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p class="truncate">{{ song.title }}</p>
                            <div class="flex gap-1 opacity-50 min-w-0">
                                <span class="text-sm truncate">{{ song.artist }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end shrink-0">
                        <button @click="addButtonClick(song)">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
