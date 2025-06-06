import type { Song } from './song';

export interface Playlist {
    id: string;
    name: string;
    uuid: string;
    songs?: Song[];
    service: "apple-music" | "spotify"
}