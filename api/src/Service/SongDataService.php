<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\User;
use App\Entity\SongData;
use App\Enum\SongDataName;
use App\Repository\SongDataRepository;
use Doctrine\ORM\EntityManagerInterface;

class SongDataService
{
    private SongDataRepository $songDataRepository;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager, SongDataRepository $songDataRepository) {
        $this->songDataRepository = $songDataRepository;
        $this->em = $entityManager;
    }

    public function getData(Song $song, SongDataName $dataName): ?string
    {
        $songData = $this->songDataRepository->findOneBy(['song' => $song, 'name' => $dataName]);
        if ($songData instanceof SongData) {
            return $songData->getValue();
        }
        return null;
    }

    public function saveData(Song $song, SongDataName $dataName, string $value): SongData
    {
        $songData = $this->songDataRepository->findOneBy(['song' => $song, 'name' => $dataName]);
        if ($songData instanceof SongData) {
            $songData->setValue($value);
            $this->em->persist($songData);
        } else {
            $songData = new SongData();
            $songData->setName($dataName);
            $songData->setValue($value);
            $song->addSongData($songData);
        }
        return $songData;
    }
}