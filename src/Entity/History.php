<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HistoryRepository")
 */
class History
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Piece", inversedBy="history")
     */
    private $piece;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $cellFrom;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $cellTo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;



    public function getId(): ?int
    {
        return $this->id;
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

    public function getCellFrom(): ?string
    {
        return $this->cellFrom;
    }

    public function setCellFrom(?string $cellFrom): self
    {
        $this->cellFrom = $cellFrom;

        return $this;
    }

    public function getCellTo(): ?string
    {
        return $this->cellTo;
    }

    public function setCellTo(?string $cellTo): self
    {
        $this->cellTo = $cellTo;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

}
