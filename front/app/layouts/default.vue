<template>
    <UApp>
        <div data-vaul-drawer-wrapper>
            <section><slot /></section>
        </div>
    </UApp>
</template>

<style>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.4s;
}
.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    
}
</style>
