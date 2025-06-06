<script lang="ts" setup>
import type { Playlist } from "~/types/playlist";
import type { Song } from "~/types/song";
import type { User } from "~/types/user";

interface Playlists {
    appleMusic?: Playlist[];
    spotify?: Playlist[];
}

const route = useRoute();
const userUuid = route.params.userUuid as string;
const user = useCookie<User | null>("user");
const playlists = ref<Playlists>([]);
const getPlaylists = async () => {
    try {
        const playlistsResponse = await $fetch<Playlists | null>(
        `https://musikk.localhost/api/playlist/${userUuid}`,
        {
            method: "GET",
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${user?.value?.token}`,
            },
        }
    );
    playlists.value = playlistsResponse ?? [];
    } catch (error) {
        console.error(error);
    }
};
await getPlaylists();
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userUuid">{{ userUuid }}</p>
        <button @click="getPlaylists">Get Playlists</button>
        <div v-if="playlists?.appleMusic?.length > 0">
            <h2 class="text-2xl font-bold mb-4">Apple Music Playlists</h2>
            <ul>
                <li v-for="playlist in playlists.appleMusic" :key="playlist.id">
                    <NuxtLink :to="{ name: 'playlist-userUuid-playlistUuid', params: { userUuid, playlistUuid: playlist.uuid } }" class="text-xl">
                        {{ playlist.name }}
                    </NuxtLink>
                </li>
            </ul>
        </div>
        <div v-if="playlists?.spotify?.length > 0">
            <h2 class="text-2xl font-bold mb-4">Spotify Playlists</h2>
            <ul>
                <li v-for="playlist in playlists.spotify" :key="playlist.id">
                    <NuxtLink :to="{ name: 'playlist-userUuid-playlistUuid', params: { userUuid, playlistUuid: playlist.uuid } }" class="text-xl">
                        {{ playlist.name }}
                    </NuxtLink>
                </li>
            </ul>
        </div>
    </div>
</template>
