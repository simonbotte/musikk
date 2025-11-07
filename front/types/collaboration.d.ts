declare global {
    interface Collaboration {
        id: string;
        name: string;
        uuid: string;
        playlistUuid: string;
        addLimit?: number;
        email: string;
        addedSongsCount: number;
    }
}
export {};
