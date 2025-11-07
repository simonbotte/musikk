<script setup lang="ts">
const email = ref("simon.botte@icloud.com");
const password = ref("password");
const freshToken = ref<string | null>("");
const { login, getUser } = useAuth();
const router = useRouter();

const loginFormSubmit = async () => {
    try {
        await login(email.value, password.value);
        const user = getUser();
        await navigateTo(`/playlist/${user?.uuid}`, { replace: true });
    } catch (error) {
        console.error("Login error:", error);
    }
    
};
</script>


<template>
    <form @submit.prevent="loginFormSubmit">
        <input v-model="email" placeholder="Email" />
        <input v-model="password" type="password" placeholder="Mot de passe" />
        <button type="submit">Se connecter</button>
    </form>
</template>