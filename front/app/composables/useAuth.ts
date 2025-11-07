export const useAuth = () => {
    const apiUrl = import.meta.server ? useRuntimeConfig().public.API_URL_IN : useRuntimeConfig().public.API_URL
    const user = useCookie<User | null>("user", {
        default: () => null,
        sameSite: true,
        secure: process.env.NODE_ENV === 'production',
        maxAge: 60 * 60 * 24 * 7,
        httpOnly: false,
    });

    if (import.meta.server) {
        console.log('ssr');
        
    }

    const login = async (username: string, password: string) => {
        let loginResponse: Record<string, string> | null = null;

        try {
            loginResponse = await $fetch(apiUrl+"/login", {
                method: "POST",
                body: { username, password },
                headers: {
                    "Content-Type": "application/json",
                },
                credentials: "include",
            });
        } catch (error) {
            throw new Error(error instanceof Error ? error.message : "Erreur de connexion.");
        }

        if (!loginResponse?.token) {
            throw new Error("Token missing.");
        }

        if (!loginResponse?.refresh_token) {
            throw new Error("Refresh Token missing.");
        }

        try {
            const userResponse = await $fetch<User>(apiUrl+"/user/informations", {
                headers: {
                    Authorization: `Bearer ${loginResponse.token}`,
                },
            });

            user.value = { ...userResponse, token: loginResponse.token, refresh_token: loginResponse.refresh_token };
            // refreshToken();
        } catch (error) {
            throw new Error("Impossible de récupérer les informations utilisateur.");
        }
    };

    const refreshToken = async (): Promise<boolean> => {    
        const form = new URLSearchParams();
        form.append("refresh_token", user.value?.refresh_token);
        
        try {
            const res = await $fetch<Record<string, string>>(apiUrl + "/token/refresh", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: form,
            })
            .catch((error) => {
                console.error("Erreur lors du rafraîchissement du token :", error);
                throw new Error(`Erreur lors du rafraîchissement du token. ${error instanceof Error ? error.message : "Erreur inconnue."}`);a
            });
            
            if (!res?.token) throw new Error("Token non reçu.");
            if (!res?.refresh_token) throw new Error("Refresh Token non reçu.");
    
            const prevUser = user.value;
            if (!prevUser) {
                console.warn("Aucun utilisateur connecté, impossible de rafraîchir le token.");
                return false;
            }
            if (prevUser.token === res.token) {
                console.log("token non modifié, pas de mise à jour nécessaire");
                return true;
            }
            
            user.value = { ...prevUser, token: res.token, refresh_token: res.refresh_token };
            
            return true;
        } catch (e) {
            return false;
        }
    };

    const initAuth = async (retry = false) => {
        if (!user.value?.token) return;

        try {
            const userResponse = await $fetch<User>(apiUrl+"/user/informations", {
                headers: {
                    Authorization: `Bearer ${user.value.token}`,
                },
            });

            user.value = { ...userResponse, token: user.value.token };
        } catch (error) {
            console.error("Erreur récupération utilisateur :", error);
            if (!retry) {
                const refreshed = await refreshToken();
                if (refreshed) return initAuth(true);
            }
            // user.value = null;
        }
    };

    const getToken = () => user.value?.token || null;
    const getUser = () => user.value;
    const isAuthenticated = () => !!user.value?.token;

    const logout = () => {
        user.value = null;
    };

    const checkAuth = async () => {
        return await refreshToken();
    };

    return {
        user,
        login,
        refreshToken,
        initAuth,
        getToken,
        getUser,
        checkAuth,
        logout,
        isAuthenticated,
    };
};
