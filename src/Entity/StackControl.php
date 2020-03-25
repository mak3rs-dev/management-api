<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StackControlRepository")
 */
class StackControl
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\InCommunity", inversedBy="stackControls")
     */
    private $id_maker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Pieces", inversedBy="stackControls")
     */
    private $id_piece;

    /**
     * @ORM\Column(type="integer")
     */
    private $unit_manufactured;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMaker(): ?InCommunity
    {
        return $this->id_maker;
    }

    public function setIdMaker(?InCommunity $id_maker): self
    {
        $this->id_maker = $id_maker;

        return $this;
    }

    public function getIdPiece(): ?Pieces
    {
        return $this->id_piece;
    }

    public function setIdPiece(?Pieces $id_piece): self
    {
        $this->id_piece = $id_piece;

        return $this;
    }

    public function getUnitManufactured(): ?int
    {
        return $this->unit_manufactured;
    }

    public function setUnitManufactured(int $unit_manufactured): self
    {
        $this->unit_manufactured = $unit_manufactured;

        return $this;
    }
}
