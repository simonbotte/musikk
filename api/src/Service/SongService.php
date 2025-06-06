<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\User;
use App\Repository\SongDataRepository;
use App\Repository\SongRepository;

class SongService
{
    public function __construct(
        private SongRepository $songRepository,
        private SongDataRepository $songDataRepository,
    ) {}

    public function getSong(string $songId, string $songIdDataName): Song|null
    {
        $songDataId = $this->songDataRepository->findOneBy([
            'name' => $songIdDataName,
            'value' => $songId,
        ]);

        if ($songDataId instanceof SongData) {
            $playlist = $this->songRepository->findOneBy([
                'id' => $songDataId->getSong()->getId(),
            ]);
            return $playlist;
        }
        return null;
    }

    public function formatSongs(array $songs): array
    {
        $formattedSongs = [];
        foreach ($songs as $song) {
            $formattedSongs[] = $song->toArray();
        }
        return $formattedSongs;
    }
}