<script lang="ts" setup>

interface Playlists {
    appleMusic?: Playlist[];
    spotify?: Playlist[];
}

definePageMeta({
    middleware: 'auth'
})

const route = useRoute();
const userUuid = route.params.userUuid as string;
const { getUser } = useAuth();

const user = getUser();

const playlists = useState<Playlists>("playlists", () => ({
    appleMusic: [],
    spotify: []
}));
const getPlaylists = async () => {
    try {
        const playlistsResponse = await $fetch<Playlists | null>(
        `https://api.musikk.localhost/playlist/${userUuid}`,
        {
            method: "GET",
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${user?.token}`,
            },
        }
    );
    
    playlists.value = playlistsResponse ?? {
        appleMusic: [],
        spotify: []
    };
    } catch (error) {
        console.error(error);
    }
};
await getPlaylists();
console.log("Playlists:", playlists.value);

</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userUuid">{{ userUuid }}</p>
        <button @click="getPlaylists">Get Playlists</button>
        <div v-if="playlists?.appleMusic && playlists?.appleMusic?.length > 0">
            <h2 class="text-2xl font-bold mb-4">Apple Music Playlists</h2>
            <ul class="grid grid-cols-2 gap-4">
                <li v-for="playlist in playlists.appleMusic" :key="playlist.id">
                    <NuxtLink :to="{ name: 'playlist-userUuid-playlistUuid', params: { userUuid, playlistUuid: playlist.uuid }}" class="overflow-hidden">
                        <div v-if="playlist?.artwork !== undefined" class="">
                            <AppleMusicArtwork :url="playlist.artwork" :size="100" :alt="`${playlist.name} artwork`" />
                        </div>
                        <div v-else class="bg-linear-65 from-orange-200 to-orange-500 aspect-square px-2 py-1 rounded-lg">
                            <span class="text-lg font-bold">{{ playlist.name }}</span>
                        </div>
                        <span>{{ playlist.name.substring(0,36)}}</span>
                    </NuxtLink>
                </li>
            </ul>
        </div>
        <div v-if="playlists?.spotify && playlists?.spotify?.length > 0">
            <h2 class="text-2xl font-bold mb-4">Spotify Playlists</h2>
            <ul class="grid grid-cols-2 gap-4">
                <li v-for="playlist in playlists.spotify" :key="playlist.id">
                    <NuxtLink :to="{ name: 'playlist-userUuid-playlistUuid', params: { userUuid, playlistUuid: playlist.uuid } }" class="overflow-hidden">
                        <div v-if="playlist?.artwork !== undefined" class="">
                            <AppleMusicArtwork :url="playlist.artwork" :size="100" :alt="`${playlist.name} artwork`" />
                        </div>
                        <div v-else class="bg-linear-65 from-orange-200 to-orange-500 aspect-square px-2 py-1 rounded-lg">
                            <span class="text-lg font-bold">{{ playlist.name }}</span>
                        </div>
                        <span>{{ playlist.name.substring(0,36)}}</span>
                    </NuxtLink>
                </li>
            </ul>
        </div>
    </div>
</template>
