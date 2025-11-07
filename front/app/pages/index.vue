<script setup lang="ts">

const { getUser } = useAuth();
const user = getUser();
</script>

<template>
    {{ user?.uuid }}
</template>