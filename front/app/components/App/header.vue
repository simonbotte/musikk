<script setup lang="ts">
const { getUser } = useAuth();
const user = computed(() => getUser())
const route = useRoute();
</script>

<template>
    <div class="flex justify-center w-full p-4">
        <div class="w-full max-w-[1440px] rounded-full bg-crusta-100 py-4 px-8 grid grid-cols-3 gap-2">
            <NuxtLink :to="'/'" class="text-lg font-bold text-crusta-900">Musikk</NuxtLink>
            <nav class="w-full flex justify-center">
                <ul class="flex gap-4">
                    <li>
                        <NuxtLink v-if="user?.uuid != undefined" :to="`/playlist/${user?.uuid}`" class="text-crusta-900 hover:text-crusta-700 font-medium">Playlists</NuxtLink>
                    </li>
                </ul>
            </nav>
            <nav class="w-full flex justify-end">
                <ul class="flex gap-4">
                    <li>
                        <NuxtLink to="/login" class="text-crusta-900 hover:text-crusta-700 font-medium">Login</NuxtLink>
                    </li>
                    <li>
                        <NuxtLink to="/login" class="text-crusta-900 hover:text-crusta-700 font-medium">Sign up</NuxtLink>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>