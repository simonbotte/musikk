<script lang="ts" setup>
import type { Song } from "~/types/song";

const props = defineProps<{
    onAddClick: (song: Song) => void;
}>();

const route = useRoute();
const query = ref<string>("");
const songs = ref<Song[]>([]);
const temporizer = ref<ReturnType<typeof setTimeout> | null>(null);

const search = async () => {
    if (temporizer.value) {
        clearTimeout(temporizer.value);
    }

    temporizer.value = setTimeout(async () => {
        if (query.value.length < 2) {
            songs.value = [];
            return;
        }

        const querySerialized = query.value.replace(/\s/g, "+");

        const { data: searchResponse } = await useFetch<Song[] | null>(
            `http://musikk.localhost/api/apple-music/search/${querySerialized}`
        );

        songs.value = searchResponse.value ?? [];
    }, 200);
};
</script>

<template>
    <div class="flex">
        <div class="flex flex-col w-full">
            <div class="flex flex-col w-full">
                <p class="text-2xl font-bold">Search</p>
                <input
                    type="text"
                    placeholder="Search for a song"
                    class="border border-gray-300 rounded-lg p-2 mt-2"
                    v-model="query"
                    @input="search"
                />
            </div>
            <div class="flex flex-col w-full mt-4">
                <p class="text-xl font-bold">Results</p>
                <div class="flex flex-col w-full mt-2">
                    <div v-for="song in songs" :key="song.id" class="flex justify-between border-b border-gray-300 p-2">
                        <div class="flex gap-2">
                            <p>{{ song.title }}</p>
                            <p>{{ song.artist }}</p>
                        </div>
                        <button class="bg-slate-400 px-4 py-1" v-on:click="() => props.onAddClick(song)">Ajouter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
