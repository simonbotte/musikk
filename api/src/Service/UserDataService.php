<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserData;
use App\Repository\UserDataRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserDataService
{
    private UserDataRepository $userDataRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, UserDataRepository $userDataRepository) {
        $this->userDataRepository = $userDataRepository;
        $this->entityManager = $entityManager;
    }

    public function getData(User $user, string $dataName): ?string
    {
        $userData = $this->userDataRepository->findOneBy(['user' => $user, 'name' => $dataName]);
        if ($userData instanceof UserData) {
            return $userData->getValue();
        }
        return null;
    }

    public function saveData(User $user, string $dataName, string $value): UserData
    {
        $userData = $this->userDataRepository->findOneBy(['user' => $user, 'name' => $dataName]);
        if ($userData instanceof UserData) {
            $userData->setValue($value);
            $this->entityManager->persist($userData);
        } else {
            $userData = new UserData();
            $userData->setUser($user);
            $userData->setName($dataName);
            $userData->setValue($value);
            $this->entityManager->persist($userData);
        }
        $this->entityManager->flush();
        return $userData;
    }
}