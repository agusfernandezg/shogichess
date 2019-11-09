<?php

namespace App\Entity;


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
     * @ORM\Column(type="string", length=81)
     */
    private $bitboard;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Piece", inversedBy="bitboards")
     */
    private $piece;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $row;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $col;

    /**
     * @ORM\Column(type="string", length=27, nullable=true)
     */
    private $board1;

    /**
     * @ORM\Column(type="string", length=27, nullable=true)
     */
    private $board2;

    /**
     * @ORM\Column(type="string", length=27, nullable=true)
     */
    private $board3;





    public function __construct()
    {

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


    public function getBitboard(): ?string
    {
        return $this->bitboard;
    }

    public function setBitboard(string $bitboard): self
    {
        $this->bitboard = $bitboard;

        return $this;
    }

    public function getPiece(): ?Piece
    {
        return $this->piece;
    }

    public function setPiece(?Piece $piece): self
    {
        $this->piece = $piece;

        return $this;
    }

    public function getRow(): ?int
    {
        return $this->row;
    }

    public function setRow(?int $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function getCol(): ?int
    {
        return $this->col;
    }

    public function setCol(?int $col): self
    {
        $this->col = $col;

        return $this;
    }

    public function getBoard1(): ?string
    {
        return $this->board1;
    }

    public function setBoard1(?string $board1): self
    {
        $this->board1 = $board1;

        return $this;
    }

    public function getBoard2(): ?string
    {
        return $this->board2;
    }

    public function setBoard2(?string $board2): self
    {
        $this->board2 = $board2;

        return $this;
    }

    public function getBoard3(): ?string
    {
        return $this->board3;
    }

    public function setBoard3(?string $board3): self
    {
        $this->board3 = $board3;

        return $this;
    }



}
