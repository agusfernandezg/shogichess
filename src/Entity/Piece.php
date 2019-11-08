<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PieceRepository")
 */
class Piece
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="boolean")
     */
    private $promoted;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Move", mappedBy="piece")
     */
    private $moves;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="integer")
     */
    private $row;

    /**
     * @ORM\Column(type="integer")
     */
    private $col;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Bitboard", inversedBy="piece")
     */
    private $bitboard;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $generator;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $promotedgenerator;


    public function __construct()
    {
        $this->moves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getPromoted(): ?bool
    {
        return $this->promoted;
    }

    public function setPromoted(bool $promoted): self
    {
        $this->promoted = $promoted;

        return $this;
    }

    /**
     * @return Collection|Move[]
     */
    public function getMoves(): Collection
    {
        return $this->moves;
    }

    public function addMove(Move $move): self
    {
        if (!$this->moves->contains($move)) {
            $this->moves[] = $move;
            $move->setPiece($this);
        }

        return $this;
    }

    public function removeMove(Move $move): self
    {
        if ($this->moves->contains($move)) {
            $this->moves->removeElement($move);
            // set the owning side to null (unless already changed)
            if ($move->getPiece() === $this) {
                $move->setPiece(null);
            }
        }

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getRow(): ?int
    {
        return $this->row;
    }

    public function setRow(int $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function getCol(): ?int
    {
        return $this->col;
    }

    public function setCol(int $col): self
    {
        $this->col = $col;

        return $this;
    }

    public function getBitboard(): ?Bitboard
    {
        return $this->bitboard;
    }

    public function setBitboard(?Bitboard $bitboard): self
    {
        $this->bitboard = $bitboard;

        return $this;
    }

    public function getGenerator(): ?string
    {
        return $this->generator;
    }

    public function setGenerator(?string $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    public function getPromotedgenerator(): ?string
    {
        return $this->promotedgenerator;
    }

    public function setPromotedgenerator(string $promotedgenerator): self
    {
        $this->promotedgenerator = $promotedgenerator;

        return $this;
    }
}
