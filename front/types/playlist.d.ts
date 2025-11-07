declare global {
    interface Playlist {
        id: string;
        name: string;
        uuid: string;
        songs?: Song[];
        service: "apple_music" | "spotify";
        artwork?: string;
    }
}
export {};
