<script lang="ts" setup>
import type { Playlist } from "~/types/playlist";
import type { Song } from "~/types/song";
import type { User } from "~/types/user";

const route = useRoute();
const userUuid = route.params.userUuid as string;
const user = useCookie<User | null>("user");
const playlists = ref<Playlist[]>([]);
const getPlaylists = async () => {
    const { data: playlistsResponse, error: playlistsError } = await useFetch<Playlist[] | null>(
        `https://musikk.localhost/api/playlist/${userUuid}`,
        {
            method: "GET",
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${user?.value?.token}`,
            },
        }
    );
    playlists.value = playlistsResponse.value ?? [];
    console.log(playlists.value, playlistsError.value);
};
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userUuid">{{ userUuid }}</p>
        <button @click="getPlaylists">Get Playlists</button>
    </div>
</template>
