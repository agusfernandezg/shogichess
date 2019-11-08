<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BitboardRepository")
 */
class Bitboard
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Piece", mappedBy="bitboard")
     */
    private $piece;

    /**
     * @ORM\Column(type="string", length=81)
     */
    private $bitboard;

    public function __construct()
    {
        $this->piece = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Piece[]
     */
    public function getPiece(): Collection
    {
        return $this->piece;
    }

    public function addPiece(Piece $piece): self
    {
        if (!$this->piece->contains($piece)) {
            $this->piece[] = $piece;
            $piece->setBitboard($this);
        }

        return $this;
    }

    public function removePiece(Piece $piece): self
    {
        if ($this->piece->contains($piece)) {
            $this->piece->removeElement($piece);
            // set the owning side to null (unless already changed)
            if ($piece->getBitboard() === $this) {
                $piece->setBitboard(null);
            }
        }

        return $this;
    }

    public function getBitboard(): ?string
    {
        return $this->bitboard;
    }

    public function setBitboard(string $bitboard): self
    {
        $this->bitboard = $bitboard;

        return $this;
    }
}
