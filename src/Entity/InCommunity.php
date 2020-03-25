<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InCommunityRepository")
 */
class InCommunity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="inCommunities")
     */
    private $id_user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Community", inversedBy="inCommunities")
     */
    private $id_community;

    /**
     * @ORM\Column(type="array")
     */
    private $role = [];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\StackControl", mappedBy="id_maker")
     */
    private $stackControls;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CollectControl", mappedBy="id_maker")
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

    public function getIdUser(): ?User
    {
        return $this->id_user;
    }

    public function setIdUser(?User $id_user): self
    {
        $this->id_user = $id_user;

        return $this;
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

    public function getRole(): ?array
    {
        return $this->role;
    }

    public function setRole(array $role): self
    {
        $this->role = $role;

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
            $stackControl->setIdMaker($this);
        }

        return $this;
    }

    public function removeStackControl(StackControl $stackControl): self
    {
        if ($this->stackControls->contains($stackControl)) {
            $this->stackControls->removeElement($stackControl);
            // set the owning side to null (unless already changed)
            if ($stackControl->getIdMaker() === $this) {
                $stackControl->setIdMaker(null);
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
            $collectControl->setIdMaker($this);
        }

        return $this;
    }

    public function removeCollectControl(CollectControl $collectControl): self
    {
        if ($this->collectControls->contains($collectControl)) {
            $this->collectControls->removeElement($collectControl);
            // set the owning side to null (unless already changed)
            if ($collectControl->getIdMaker() === $this) {
                $collectControl->setIdMaker(null);
            }
        }

        return $this;
    }
}
