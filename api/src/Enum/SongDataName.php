<?php

namespace App\Enum;

enum SongDataName: string
{
    case APPLE_MUSIC_SONG_ID = 'apple_music_song_id';
    case SPOTIFY_SONG_ID     = 'spotify_song_id';
    case ARTWORK             = '_artwork';
    case APPLE_MUSIC_ARTWORK = 'apple_music_artwork';
    case SPOTIFY_ARTWORK     = 'spotify_artwork';
}
