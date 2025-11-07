export default defineAppConfig({
    ui: {
        colors: {
            primary: "orange",
            neutral: "neutral",
        },
        button: {
            slots: {
                base: ["rounded-full leading-none [&+button]:border-l-0 [&:has(+button)]:border-r-0"],
                label: "leading-none",
            },
            variants: {
                variant: {
                    translucent: "bg-neutral-100/50 text-neutral-900 border border-neutral-200/50",
                },
                size: {
                    md: {
                        base: "leading-none [&>*]:leading-none",
                    },
                },
            },
        },
        fieldGroup: {
            slots: {
                base: "inline-flex",
            },
        },
        input: {
            slots: {
                base: ["rounded-full"],
            },
            variants: {
                variant: {
                    translucent: "bg-neutral-100/50 text-neutral-900 border border-neutral-200/50",
                },
            },
        },
        inputNumber: {
            slots: {
                base: ["rounded-full"],
            },
            variants: {
                variant: {
                    translucent: "bg-neutral-100/50 text-neutral-900 border border-neutral-200/50",
                },
            },
        },
        toast: {
            slots: {
                icon: "mt-0.5",
                close: "mt-0.5",
            },
        },
        dropdownMenu: {
            slots: {
                content: "bg-neutral-100/50 text-neutral-900 border border-neutral-200/50 backdrop-blur-sm rounded-lg",
                group: "p-0",
                item: "hover:bg-neutral-200/50 cursor-pointer data-disabled:opacity-50",
            },
        },
        drawer: {
            slots: {
                content: "bg-white/80 border border-neutral-300/40 backdrop-blur-md",
                handle: "shrink-0 !bg-neutral-400/80 transition-opacity",
                container: "rounded-lg",
            },
            variants: {
                inset: {
                    true: {
                        content: "[--initial-transform:calc(100%+0.5rem)]",
                    },
                },
            },
            compoundVariants: [
                {
                    direction: "bottom",
                    inset: true,
                    class: {
                        content: "inset-x-2 bottom-2",
                    },
                },
            ],
        },
    },
});
