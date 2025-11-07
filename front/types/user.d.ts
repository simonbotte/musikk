declare global {
    interface User {
        uuid: string;
        email: string;
        token: string;
        refresh_token: string;
    }
}
export {};