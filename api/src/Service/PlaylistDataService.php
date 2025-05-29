<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\User;
use App\Entity\PlaylistData;
use App\Repository\PlaylistDataRepository;
use Doctrine\ORM\EntityManagerInterface;

class PlaylistDataService
{
    private PlaylistDataRepository $playlistDataRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, PlaylistDataRepository $playlistDataRepository) {
        $this->playlistDataRepository = $playlistDataRepository;
        $this->entityManager = $entityManager;
    }

    public function getData(Playlist $playlist, string $dataName): ?string
    {
        $playlistData = $this->playlistDataRepository->findOneBy(['playlist' => $playlist, 'name' => $dataName]);
        if ($playlistData instanceof PlaylistData) {
            return $playlistData->getValue();
        }
        return null;
    }

    public function saveData(Playlist $playlist, string $dataName, string $value): PlaylistData
    {
        $playlistData = $this->playlistDataRepository->findOneBy(['playlist' => $playlist, 'name' => $dataName]);
        if ($playlistData instanceof PlaylistData) {
            $playlistData->setValue($value);
            $this->entityManager->persist($playlistData);
        } else {
            $playlistData = new PlaylistData();
            $playlistData->setPlaylist($playlist);
            $playlistData->setName($dataName);
            $playlistData->setValue($value);
            $this->entityManager->persist($playlistData);
        }
        $this->entityManager->flush();
        return $playlistData;
    }
}