<script lang="ts" setup>
import type { Playlist } from "~/types/playlist";
import type { Song } from "~/types/song";

const route = useRoute();
const userUuid = route.params.userUuid as string;
const playlistUuid = route.params.playlistUuid as string;

const playlist = ref<Playlist | null>(null);

const { data: playlistResponse, error: playlistError } = await useFetch<Playlist | null>(
    `https://musikk.localhost/api/playlist/${userUuid}/${playlistUuid}`,
    {
        method: "GET",
    }
);
playlist.value = playlistResponse.value ?? null;

const addSong = async (song: Song) => {
    const { data: addSongResponse } = await useFetch<Playlist | null>(
        `https://musikk.localhost/api/playlist/${userUuid}/${playlistUuid}/add-song/${song.id}`,
        { method: "POST" }
    );

    // (optionnel) rafraîchir la playlist après ajout
    if (addSongResponse.value) {
        playlist.value = addSongResponse.value;
    }
};
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userUuid">{{ userUuid }}</p>
        <p class="max-w-full break-all" v-if="playlistUuid">{{ playlistUuid }}</p>
        <br />
        <div v-if="playlist">
            <p class="text-2xl font-bold">
                {{ playlist.title }}
            </p>
            <br />
            <div>
                <p v-for="song in playlist.songs">
                    {{ song.title }}
                </p>
            </div>
        </div>
        <br />
        <Search :on-add-click="addSong" />
    </div>
</template>
