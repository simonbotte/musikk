import tailwindcss from "@tailwindcss/vite";

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: "2024-11-01",
    devtools: {
        enabled: true,

        timeline: {
            enabled: true,
        },
    },
    modules: ["@nuxt/image", "@nuxt/fonts", "@nuxt/icon", "@nuxt/ui"],

    css: ["~/assets/css/fonts.css", "~/assets/css/main.css"],
    vite: {
        plugins: [tailwindcss()],
    },
    fonts: {
        families: [
            {
                name: "Satoshi",
                src: ["Satoshi-Light.woff2", "Satoshi-Light.woff", "Satoshi-Light.ttf"],
                weight: "300",
                style: "normal",
            },
            {
                name: "Satoshi",
                src: ["Satoshi-Regular.woff2", "Satoshi-Regular.woff", "Satoshi-Regular.ttf"],
                weight: "400",
                style: "normal",
            },
            {
                name: "Satoshi",
                src: ["Satoshi-Medium.woff2", "Satoshi-Medium.woff", "Satoshi-Medium.ttf"],
                weight: "500",
                style: "normal",
            },
            {
                name: "Satoshi",
                src: ["Satoshi-Bold.woff2", "Satoshi-Bold.woff", "Satoshi-Bold.ttf"],
                weight: "700",
                style: "normal",
            },
        ],
    },
    app: {
        head: {
            script: [
                {
                    src: "https://js-cdn.music.apple.com/musickit/v3/musickit.js",
                    "data-web-components": "",
                },
            ],
        },
    },
    runtimeConfig: {
        public: {
            API_URL: process.env.API_URL,
            API_URL_IN: process.env.API_URL_IN,
        },
    },
    ui: {
        colorMode: false,
        theme: {
            colors: ["primary", "neutral", "error"],
        },
    },
});
