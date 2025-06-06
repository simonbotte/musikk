<script lang="ts" setup>
import type { Playlist } from "~/types/playlist";
import type { Song } from "~/types/song";

const user = useCookie<User | null>("user");
const route = useRoute();
const userUuid = route.params.userUuid as string;
const playlistUuid = route.params.playlistUuid as string;
const playlist = ref<Playlist | null>(null);

const getPlaylist = async () => {
    try {
        const playlistResponse = await $fetch<Playlists | null>(
            `https://musikk.localhost/api/playlist/${userUuid}/${playlistUuid}`,
            {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${user?.value?.token}`,
                },
            }
        );
        playlist.value = playlistResponse ?? [];
        console.log(playlist.value);
        
    } catch (error) {
        console.error(error);
    }
};
await getPlaylist();
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userUuid">{{ userUuid }}</p>
        <p class="max-w-full break-all" v-if="playlistUuid">{{ playlistUuid }}</p>
        <div>
            <Search :service="playlist.service" :on-add-cick="()=>{console.log('Add song clicked');}"/>
        </div>
        <br />
        <div v-if="playlist">
            <p class="text-2xl font-bold">
                {{ playlist.name }}
            </p>
            <br />
            <div class="flex flex-col gap-2">
                <div v-for="song in playlist.songs" class="flex flex-col">
                    <p>{{ song.title }}</p>
                    <div class="flex gap-1 opacity-50">
                        <span>{{ song.album }}</span>
                        <span>{{ song.artist }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
