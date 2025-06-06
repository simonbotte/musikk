<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\User;
use App\Entity\SongData;
use App\Repository\SongDataRepository;
use Doctrine\ORM\EntityManagerInterface;

class SongDataService
{
    private SongDataRepository $songDataRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SongDataRepository $songDataRepository) {
        $this->songDataRepository = $songDataRepository;
        $this->entityManager = $entityManager;
    }

    public function getData(Song $song, string $dataName): ?string
    {
        $songData = $this->songDataRepository->findOneBy(['song' => $song, 'name' => $dataName]);
        if ($songData instanceof SongData) {
            return $songData->getValue();
        }
        return null;
    }

    public function saveData(Song $song, string $dataName, string $value): SongData
    {
        $songData = $this->songDataRepository->findOneBy(['song' => $song, 'name' => $dataName]);
        if ($songData instanceof SongData) {
            $songData->setValue($value);
            $this->entityManager->persist($songData);
        } else {
            $songData = new SongData();
            $songData->setSong($song);
            $songData->setName($dataName);
            $songData->setValue($value);
            $this->entityManager->persist($songData);
        }
        $this->entityManager->flush();
        return $songData;
    }
}