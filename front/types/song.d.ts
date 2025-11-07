declare global { 
    interface Song {
        id: string;
        title: string;
        artist: string;
        album?: string;
        artwork?: Record<string, string>;
    }
}
export {};