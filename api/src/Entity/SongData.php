<?php

namespace App\Entity;

use App\Enum\SongDataName;
use App\Repository\SongDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongDataRepository::class)]
class SongData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'songData')]
    private ?Song $song = null;

    #[ORM\Column(enumType: SongDataName::class)]
    private ?SongDataName $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song): static
    {
        $this->song = $song;

        return $this;
    }

    public function getName(): SongDataName
    {
        return $this->name;
    }

    public function setName(SongDataName $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
