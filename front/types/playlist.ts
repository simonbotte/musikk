import type { Song } from './song';

export interface Playlist {
    id: string;
    title: string;
    songs: Song[];
}