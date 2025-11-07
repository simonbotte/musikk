export default defineNuxtRouteMiddleware(async (to, from) => {
    if (from.path !== "/login") {
        const { refreshToken, logout } = useAuth();
        const success = await refreshToken();
        
        if (!success) {
            console.warn("⛔️ Non authentifié, redirection vers /login");
            logout();
            return await navigateTo("/login");
        }
    }
});
