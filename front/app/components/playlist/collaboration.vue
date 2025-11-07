<script setup lang="ts">
const nuxtApp = useNuxtApp();
const { user } = useAuth();
const route = useRoute();

const displayCollaborationModal = ref(false);
const { removeCollaboration, addCollaboration, getCollaborations, updateCollaboration } = useApi();
const toast = useToast();
const collaboration = reactive(<Collaboration>{
    uuid: "",
    name: "",
    email: "",
    withLimit: false,
    addLimit: 0,
});
const collaborationDrawerOpen = ref(false);
const props = defineProps<{
    userUuid: string;
    playlistUuid: string;
    collaborations?: Collaboration[];
}>();

const resetCollaboration = () => {
    collaboration.uuid = "";
    collaboration.name = "";
    collaboration.email = "";
    collaboration.withLimit = false;
    collaboration.addLimit = 1;
};

const setCollaboration = (collaborationToEdit: Collaboration) => {
    collaboration.uuid = collaborationToEdit.uuid;
    collaboration.name = collaborationToEdit.name;
    collaboration.email = collaborationToEdit.email;
    collaboration.withLimit = collaborationToEdit.addLimit !== null;
    collaboration.addLimit = collaborationToEdit.addLimit;
};

const getDropdownMenuItems = (collaborationToEdit: Collaboration): DropdownMenuItem[][] => [
    [
        {
            label: "Edit",
            icon: "material-symbols:edit-outline-rounded",
            type: "button",
            onSelect() {
                toggleDrawer(collaborationToEdit);
            },
        },
        {
            label: "Remove",
            icon: "material-symbols:remove-rounded",
            type: "button",
            onSelect(e: Event) {
                removeCollaboration(collaborationToEdit.uuid)
                    .then(() => {
                        getCollaborations(props.playlistUuid).then(() => {
                            nuxtApp.callHook("update:collaborations", props.collaborations.filter((c) => c.uuid !== collaborationToEdit.uuid));
                        });
                    })
                    .catch((error) => {
                        console.error("Error removing collaboration :", error);
                    });
            },
        },
    ],
];

const toggleDrawer = (collaboration: Collaboration | null) => {
    collaborationDrawerOpen.value = !collaborationDrawerOpen.value;
    if (collaboration) {
        setCollaboration(collaboration);
    } else {
        resetCollaboration();
    }
};

const OnSaveCollaboration = async () => {
    if (props.playlistUuid) {
        if (collaboration.uuid !== "") {
            updateCollaboration(collaboration)
                .then(() => {
                    getCollaborations(props.playlistUuid).then((updatedCollaborations) => {
                        nuxtApp.callHook("update:collaborations", updatedCollaborations);
                        collaborationDrawerOpen.value = false;
                    });
                })
                .catch((error) => {
                    console.error("Error sending invitation :", error);
                });
        } else {
            addCollaboration(collaboration, props.userUuid, props.playlistUuid)
                .then(() => {
                    getCollaborations(props.playlistUuid).then((updatedCollaborations) => {
                        nuxtApp.callHook("update:collaborations", updatedCollaborations);
                        collaborationDrawerOpen.value = false;
                    });
                })
                .catch((error) => {
                    console.error("Error sending invitation :", error);
                });
        }
    }
};

async function shareLink(collaborationUuid: string) {
    const link = `${window.location.origin}/collaboration/${collaborationUuid}/${props.playlistUuid}`;
    try {
        await navigator.clipboard.writeText(link);
        toast.add({
            title: "Link copied to clipboard",
            description: "You can now share it with others",
            icon: "material-symbols:content-copy-rounded",
            progress: false,
            duration: 2000,
            color: "neutral",
        });
    } catch (err) {
        toast.add({
            title: "Failed to copy link",
            icon: "material-symbols:error-outline-rounded",
            color: "danger",
            duration: 5000,
        });
    }
}

watch(displayCollaborationModal, (value: boolean) => {
    document.body.style.overflow = value ? "hidden" : "auto";
});
</script>

<template>
    <div class="py-4">
        <div class="flex flex-col gap-4 px-4">
            <div class="flex justify-between items-center w-full gap-4">
                <h2 class="text-2xl leading-9 font-bold">Your collaborations</h2>
                <div>
                    <UButton
                        size="md"
                        color="neutral"
                        variant="translucent"
                        trailing-icon="material-symbols:add-2-rounded"
                        @click="toggleDrawer(null)"
                        >Add</UButton
                    >
                </div>
            </div>
            <div class="flex flex-col gap-2 w-full">
                <div
                    class="flex items-center justify-between w-full gap-2 border border-neutral-300/50 rounded-lg p-4"
                    v-for="collaboration of collaborations"
                    v-bind:key="`collaboration-${collaboration.uuid}`"
                >
                    <div class="flex items-center justify-between w-full min-w-0 gap-2">
                        <div class="flex flex-col min-w-0">
                            <span class="truncate">
                                {{ collaboration.name }}
                            </span>
                            <span class="opacity-50 text-sm truncate min-w-0">
                                {{ collaboration.addedSongsCount !== null ? collaboration.addedSongsCount : 0 }} songs
                                added
                                {{ collaboration.addLimit ? `on ${collaboration.addLimit}` : "" }}
                            </span>
                        </div>

                        <UFieldGroup orientation="horizontal">
                            <UButton
                                size="md"
                                variant="translucent"
                                icon="material-symbols:ios-share-rounded"
                                @click="shareLink(collaboration.uuid)"
                                >Share</UButton
                            >
                            <UDropdownMenu
                                :items="getDropdownMenuItems(collaboration)"
                                :content="{ align: 'start' }"
                                :ui="{ content: 'w-48' }"
                                data-
                            >
                                <UButton size="md" variant="translucent" icon="material-symbols:more-horiz" />
                            </UDropdownMenu>
                        </UFieldGroup>
                    </div>
                </div>
                <UDrawer v-model:open="collaborationDrawerOpen" inset>
                    <template #body>
                        <PlaylistCollaborationForm
                            :collaboration="collaboration"
                            :on-save-collaboration="OnSaveCollaboration"
                        />
                    </template>
                </UDrawer>
            </div>
        </div>
    </div>
</template>
