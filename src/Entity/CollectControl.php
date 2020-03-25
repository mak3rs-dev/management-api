<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CollectControlRepository")
 */
class CollectControl
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\InCommunity", inversedBy="collectControls")
     */
    private $id_maker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Pieces", inversedBy="collectControls")
     */
    private $id_piece;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Status", inversedBy="collectControls")
     */
    private $id_status;

    /**
     * @ORM\Column(type="integer")
     */
    private $units;

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

    public function getIdStatus(): ?Status
    {
        return $this->id_status;
    }

    public function setIdStatus(?Status $id_status): self
    {
        $this->id_status = $id_status;

        return $this;
    }

    public function getUnits(): ?int
    {
        return $this->units;
    }

    public function setUnits(int $units): self
    {
        $this->units = $units;

        return $this;
    }
}
