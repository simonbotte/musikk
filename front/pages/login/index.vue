<template>
    <form @submit.prevent="login">
        <input v-model="email" placeholder="Email" />
        <input v-model="password" type="password" placeholder="Mot de passe" />
        <button type="submit">Se connecter</button>
    </form>
</template>
<script setup lang="ts">
import type { User } from "~/types/user";
const email = ref("");
const password = ref("");
const freshToken = ref<string | null>("");
const user = useCookie<User | null>("user");
const router = useRouter();

const login = async () => {
    const { data: loginResponse, error: loginError } = await useFetch<Record<string, string>>(
        "/api/login",
        {
            method: "POST",
            body: {
                username: email.value,
                password: password.value,
            },
        }
    );

    if (loginError.value) {
        console.error("Erreur de connexion :", loginError.value);
        return;
    }

    freshToken.value = loginResponse.value?.token ?? null;

    if (!freshToken.value) {
        console.error("Token non reçu");
        return;
    }

    const { data: userResponse, error: userError } = await useFetch<User>("/api/user/informations", {
        method: "GET",
        headers: {
            Authorization: `Bearer ${freshToken.value}`,
        },
    });

    if (userError.value) {
        console.error("Erreur récupération user :", userError.value);
        return;
    }

    if (userResponse.value) {
        user.value = { ...userResponse.value, token: freshToken.value };
        return navigateTo({
            path: `/playlist/${user.value.uuid}`,
        });
    }
};
</script>
