<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PiecesRepository")
 */
class Pieces
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Community", inversedBy="pieces")
     */
    private $id_community;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $download_url;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\StackControl", mappedBy="id_piece")
     */
    private $stackControls;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CollectControl", mappedBy="id_piece")
     */
    private $collectControls;

    public function __construct()
    {
        $this->stackControls = new ArrayCollection();
        $this->collectControls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCommunity(): ?Community
    {
        return $this->id_community;
    }

    public function setIdCommunity(?Community $id_community): self
    {
        $this->id_community = $id_community;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->download_url;
    }

    public function setDownloadUrl(string $download_url): self
    {
        $this->download_url = $download_url;

        return $this;
    }

    /**
     * @return Collection|StackControl[]
     */
    public function getStackControls(): Collection
    {
        return $this->stackControls;
    }

    public function addStackControl(StackControl $stackControl): self
    {
        if (!$this->stackControls->contains($stackControl)) {
            $this->stackControls[] = $stackControl;
            $stackControl->setIdPiece($this);
        }

        return $this;
    }

    public function removeStackControl(StackControl $stackControl): self
    {
        if ($this->stackControls->contains($stackControl)) {
            $this->stackControls->removeElement($stackControl);
            // set the owning side to null (unless already changed)
            if ($stackControl->getIdPiece() === $this) {
                $stackControl->setIdPiece(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CollectControl[]
     */
    public function getCollectControls(): Collection
    {
        return $this->collectControls;
    }

    public function addCollectControl(CollectControl $collectControl): self
    {
        if (!$this->collectControls->contains($collectControl)) {
            $this->collectControls[] = $collectControl;
            $collectControl->setIdPiece($this);
        }

        return $this;
    }

    public function removeCollectControl(CollectControl $collectControl): self
    {
        if ($this->collectControls->contains($collectControl)) {
            $this->collectControls->removeElement($collectControl);
            // set the owning side to null (unless already changed)
            if ($collectControl->getIdPiece() === $this) {
                $collectControl->setIdPiece(null);
            }
        }

        return $this;
    }
}
