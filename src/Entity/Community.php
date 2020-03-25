<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommunityRepository")
 */
class Community
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
    private $alias;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Pieces", mappedBy="id_community")
     */
    private $pieces;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\InCommunity", mappedBy="id_community")
     */
    private $inCommunities;

    public function __construct()
    {
        $this->pieces = new ArrayCollection();
        $this->inCommunities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
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

    /**
     * @return Collection|Pieces[]
     */
    public function getPieces(): Collection
    {
        return $this->pieces;
    }

    public function addPiece(Pieces $piece): self
    {
        if (!$this->pieces->contains($piece)) {
            $this->pieces[] = $piece;
            $piece->setIdCommunity($this);
        }

        return $this;
    }

    public function removePiece(Pieces $piece): self
    {
        if ($this->pieces->contains($piece)) {
            $this->pieces->removeElement($piece);
            // set the owning side to null (unless already changed)
            if ($piece->getIdCommunity() === $this) {
                $piece->setIdCommunity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|InCommunity[]
     */
    public function getInCommunities(): Collection
    {
        return $this->inCommunities;
    }

    public function addInCommunity(InCommunity $inCommunity): self
    {
        if (!$this->inCommunities->contains($inCommunity)) {
            $this->inCommunities[] = $inCommunity;
            $inCommunity->setIdCommunity($this);
        }

        return $this;
    }

    public function removeInCommunity(InCommunity $inCommunity): self
    {
        if ($this->inCommunities->contains($inCommunity)) {
            $this->inCommunities->removeElement($inCommunity);
            // set the owning side to null (unless already changed)
            if ($inCommunity->getIdCommunity() === $this) {
                $inCommunity->setIdCommunity(null);
            }
        }

        return $this;
    }
}
