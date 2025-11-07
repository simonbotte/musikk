<script lang="ts" setup>

const user = useCookie<User | null>("user");
const userToken = ref<string|null>(null);
const loginUrl = ref<string|null>(null);
const code = ref<string|null>(null);
const router = useRouter();

const { data: loginUrlResponse, error: loginUrlError } = await useFetch<Record<string,string> | null>('https://api.musikk.localhost/spotify/login-url', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${user?.value?.token}`,
    },
});
if (loginUrlResponse.value) {
    loginUrl.value = loginUrlResponse.value.url;
}

if (router.currentRoute.value.query?.code !== undefined) {
    const queryCode = router.currentRoute.value.query.code;
    code.value = Array.isArray(queryCode) ? queryCode[0] : queryCode ?? null;
}

console.log(code.value);


const fistLogin = async () => {
    console.log(code.value);
    const { data: tokenResponse, error: tokenError } = await useFetch<Record<string,string>>(`https://api.musikk.localhost/spotify/first-login-workflow/${code.value}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${user?.value?.token}`,
        },
    });
    console.log(tokenError.value);
    userToken.value = tokenResponse.value?.access_token ?? null;
    console.log(userToken.value);
}

if (code.value) {
    await fistLogin();
}
</script>

<template>
    <div class="overflow-hidden max-w-full">
        <h1 class="text-2xl font-bold">
            Connexion à Spotify
        </h1>
        <p class="max-w-full break-all" v-if="userToken">{{ userToken }}</p>
        <br/>
        <p class="max-w-full break-all" v-if="loginUrl">{{ loginUrl }}</p>
        <br/>
        <p class="max-w-full break-all" v-if="code">{{ code }}</p>
        <br/>
        <a v-if="loginUrl" :href="loginUrl">Connexion avec Spotify</a>
        <br/>
        <button v-if="code" class="bg-slate-400 px-4 py-1" @click="() => fistLogin()">Ajouter</button>
    </div>
</template>
