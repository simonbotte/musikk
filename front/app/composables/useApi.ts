export const useApi = () => {
    const config = useRuntimeConfig();
    const { user, refreshToken } = useAuth();

    const baseURL = import.meta.server ? config.public.API_URL_IN : config.public.API_URL;

    const ssrForward = import.meta.server ? useRequestHeaders(["cookie", "accept-language", "user-agent"]) : undefined;

    const buildHeaders = (options: RequestInit = {}, needAuth: boolean = true) => {
        const isFormData = typeof FormData !== "undefined" && options.body instanceof FormData;

        const headers: Record<string, string> = {
            Accept: "application/json",
            ...(options.headers as Record<string, string>),
        };

        const method = (options.method || "GET").toUpperCase();
        if (method !== "GET" && options.body && !isFormData) {
            headers["Content-Type"] = headers["Content-Type"] || "application/json";
        }
        
        if (needAuth && user.value) {
            const token = user.value.token;
            if (token) headers.Authorization = `Bearer ${token}`;
        }
        
        if (ssrForward) {
            if (ssrForward.cookie) headers.cookie = ssrForward.cookie;
            if (ssrForward["accept-language"]) headers["accept-language"] = ssrForward["accept-language"];
            if (ssrForward["user-agent"]) headers["user-agent"] = ssrForward["user-agent"];
        }

        return headers;
    };

    const apiCall = async <T>(path: string, options: RequestInit = {}, needAuth: boolean = true): Promise<T> => {
        const url = path.startsWith("http") ? path : baseURL + path;
        const opts: RequestInit & { _retried?: boolean } = {
            ...options,
            credentials: import.meta.client ? "include" : "omit",
            headers: buildHeaders(options),
        };

        try {
            // $fetch sérialise tout seul si body = object (et Content-Type JSON)
            return await $fetch<T>(url, opts as any);
        } catch (err) {
            let status = 401;
            // Un seul retry après refresh
            if (status === 401 && !opts._retried) {
                try {
                    await refreshToken();
                    opts._retried = true;
                    opts.headers = buildHeaders(options);
                    return await $fetch<T>(url, opts as any);
                } catch (err2) {
                    console.log(err2);
                }
            }
            throw err instanceof Error ? err : new Error("Erreur lors de l’appel API.");
        }
    };

    const getPlaylist = async (userUuid: string, playlistUuid: string) => {
        // on retourne la donnée, libre à toi de set ta ref côté appelant
        return apiCall<Playlist>(`/playlist/${userUuid}/${playlistUuid}`, {
            method: "GET",
        });
    };

    const getPlaylistFromCollaboration = async (collaborationUuid: string, playlistUuid: string) => {
        return apiCall<Playlist>(`/collaboration/${collaborationUuid}/${playlistUuid}`, {
            method: "GET",
        }, false);
    };

    const getCollaboration = async (collaborationUuid: string) => {
        return apiCall<Collaboration>(`/collaboration/${collaborationUuid}`, {
            method: "GET",
        }, false);
    }

    const getCollaborations = async (playlistUuid: string) => {
        return apiCall<Collaboration[]>(`/playlist/${playlistUuid}/collaborations`, {
            method: "GET",
        });
    }

    const searchSongs = async (query: string, service: string, limit = 10) => {
        const querySerialized = query.replace(/\s/g, "+");
        return apiCall<Song[] | null>(`/song/search/${service}/${querySerialized}`, {
            method: "GET",
        });
    };

    const addMusicToPlaylist = async (song: Song, userUuid: string, playlistUuid: string) => {
        return apiCall<Song | null>(`/playlist/${userUuid}/${playlistUuid}/add-song/${song.id}`, {
            method: "GET",
        });
    }

    const addMusicToPlaylistFromCollaboration = async (song: Song, collaborationUuid: string, playlistUuid: string) => {
        return apiCall<Song | null>(`/collaboration/${collaborationUuid}/${playlistUuid}/add-song/${song.id}`, {
            method: "GET",
        }, false);
    }

    const addCollaboration = async (collaboration: Collaboration, userUuid: string, playlistUuid: string) => {
        return apiCall<Collaboration | null>(`/playlist/${userUuid}/${playlistUuid}/add-collaboration-invitation`, {
            method: "POST",
            body: collaboration
        });
    }

    const updateCollaboration = async (collaboration: Collaboration) => {
        return apiCall<Collaboration | null>(`/collaboration/${collaboration.uuid}/edit`, {
            method: "POST",
            body: collaboration
        });
    }

    const removeCollaboration = async (collaborationUuid: string) => {
        return apiCall<boolean>(`/collaboration/${collaborationUuid}/remove`, {
            method: "DELETE",
        });
    }

    return {
        getPlaylist,
        searchSongs,
        addMusicToPlaylist,
        addCollaboration,
        getPlaylistFromCollaboration,
        addMusicToPlaylistFromCollaboration,
        getCollaboration,
        getCollaborations,
        updateCollaboration,
        removeCollaboration
    };
};
