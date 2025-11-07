<script lang="js" setup>
const userToken = ref("Aon5tnprK1+k4mkvPZnxkWW3R2LxnisuF6DEZegS12vK6v+WoO0T1L/SUHfZi+LpE4Mn0mmk8eMMPfmr5d0omBuCVyweApCnmsNbz3m8bH6MNUFX2YlwJ+Py6GiCrSCeG/ODQc18YPC/CYNe1DkmsPWeDu8nubA1hdIQ1NMOgwgrco5aUin5gw5PmheJmPCEeC52MtwI3405AzfMQ2CttLMiWgSZ9M1WxytFH12F3gw6m94bZQ==");
const apiToken = ref(null);
const playlists = ref(null);
const addSongToPlaylist = ref(null);


const {data:tokenResponse, error:tokenError} = await useFetch('https://musikk.localhost/api/apple-music/token/60', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
    },
});
apiToken.value = tokenResponse.value.jwt;

const {data:playlistsResponse, status:playlistsStatus, error:playlistsError} = await useFetch(
        'https://api:8000/apple-music/playlists',
        {
            method: 'GET',
            headers: {
                'Music-User-Token': userToken.value,
            },
        }
    );
    playlists.value = playlistsResponse.value.data;


    // // add song id:697195462 to playlist id:p.e58vtQr7xz6
    // const { data:addSongToPlaylistResponse, status:addSongToPlaylistStatus, error:addSongToPlaylistError  } = await useFetch(
    // `https://api.music.apple.com/v1/me/library/playlists/p.e58vtQr7xz6/tracks`,
    // {
    //     method: 'POST',
    //     headers: {
    //         Authorization: `Bearer ${apiToken.value}`,
    //         'Music-User-Token': userToken.value,
    //     },
    //     body: {
    //         data: [
    //             {
    //                 id: '1579349842',
    //                 type: 'songs',
    //             },
    //         ],
    //     },
    // });
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <p class="max-w-full break-all" v-if="userToken">{{ userToken }}</p>
    </div>
</template>
