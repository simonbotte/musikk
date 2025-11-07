<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\User;
use App\Enum\SongDataName;
use App\Repository\SongDataRepository;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;

class SongService
{
    public function __construct(
        private SongRepository $songRepository,
        private SongDataRepository $songDataRepository,
        private EntityManagerInterface $em,
        private SongDataService $songDataService,
    ) {}

    public function getSong(string $songId, SongDataName $songIdDataName): Song|null
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

    public function getSongByData(string $songTitle, string $songArtist, string $songAlbum): Song|null
    {
        return $this->songRepository->findOneBy([
            'title' => $songTitle,
            'artist' => $songArtist,
            'album' => $songAlbum,
        ]);
    }

    public function formatSongs(array $songs): array
    {
        $formattedSongs = [];
        foreach ($songs as $song) {
            $formattedSongs[] = $song->toArray();
        }
        return $formattedSongs;
    }

    public function addSong(
        string $title,
        string $artist,
        string $album,
        SongDataName $idName,
        string $idValue,
        SongDataName $artworkName,
        ?string $artworkUrl = null,
    ): Song {
        $song = (new Song())
            ->setTitle($title)
            ->setArtist($artist)
            ->setAlbum($album);

        $this->em->persist($song);

        $this->songDataService->saveData($song, $idName, $idValue);

        if ($artworkUrl !== null) {
            $this->songDataService->saveData($song, $artworkName, $artworkUrl);
        }

        return $song;
    }
}