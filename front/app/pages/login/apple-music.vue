<script setup lang="ts">
definePageMeta({
    middleware: "auth",
});

const user = useCookie<User | null>("user");
const musicKit = ref<MusicKit | null>(null);
onMounted(() => {
    if (typeof window !== "undefined") {
        if ("MusicKit" in window) {
            loadMusicKitInstance(); // SDK déjà chargé
        } else {
            document.addEventListener("musickitloaded", loadMusicKitInstance);
        }
    }
});

const loadMusicKitInstance = async () => {
    const tokenResponse = await $fetch<Record<string, string>>(`https://api.musikk.localhost/apple-music/token/360`, {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${user?.value?.token}`,
        },
    });
    const token = tokenResponse?.jwt;
    console.log(token);

    try {
        await window.MusicKit.configure({
            developerToken: token,
            app: {
                name: "Musikk",
                build: "0.1.1",
            },
        });
        musicKit.value = window.MusicKit;
    } catch (err) {
        // Handle configuration error
    }
};

const openLogin = async () => {
    if (musicKit.value) {
        const musicKitInstance = musicKit.value.getInstance();
        musicKitInstance
            .authorize()
            .then(async (token) => {
                const tokenResponse = await $fetch<Record<string, string>>(
                    `https://api.musikk.localhost/apple-music/user-token/save`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${user?.value?.token}`,
                        },
                        body: {
                            token: token,
                        },
                    }
                );
            })
            .catch((error) => {
                console.error("Authorization failed:", error);
            });
    } else {
        console.error("MusicKit instance is not available.");
    }
};
</script>
<template>
    <div v-on:click="openLogin">Login to do</div>
</template>
