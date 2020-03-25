<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StatusRepository")
 */
class Status
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $Code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CollectControl", mappedBy="id_status")
     */
    private $collectControls;

    public function __construct()
    {
        $this->collectControls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?int
    {
        return $this->Code;
    }

    public function setCode(int $Code): self
    {
        $this->Code = $Code;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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
            $collectControl->setIdStatus($this);
        }

        return $this;
    }

    public function removeCollectControl(CollectControl $collectControl): self
    {
        if ($this->collectControls->contains($collectControl)) {
            $this->collectControls->removeElement($collectControl);
            // set the owning side to null (unless already changed)
            if ($collectControl->getIdStatus() === $this) {
                $collectControl->setIdStatus(null);
            }
        }

        return $this;
    }
}
