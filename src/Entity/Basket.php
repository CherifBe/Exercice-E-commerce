<?php

namespace App\Entity;

use App\Repository\BasketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BasketRepository::class)]
class Basket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'baskets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private ?bool $state = null;

    #[ORM\OneToMany(mappedBy: 'basket', targetEntity: ShoppingBasket::class, orphanRemoval: true)]
    private Collection $shoppingBaskets;

    public function __construct()
    {
        $this->shoppingBaskets = new ArrayCollection();
        $this->setState(false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isState(): ?bool
    {
        return $this->state;
    }

    public function setState(bool $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, ShoppingBasket>
     */
    public function getShoppingBaskets(): Collection
    {
        return $this->shoppingBaskets;
    }

    public function addShoppingBasket(ShoppingBasket $shoppingBasket): self
    {
        if (!$this->shoppingBaskets->contains($shoppingBasket)) {
            $this->shoppingBaskets->add($shoppingBasket);
            $shoppingBasket->setBasket($this);
        }

        return $this;
    }

    public function removeShoppingBasket(ShoppingBasket $shoppingBasket): self
    {
        if ($this->shoppingBaskets->removeElement($shoppingBasket)) {
            // set the owning side to null (unless already changed)
            if ($shoppingBasket->getBasket() === $this) {
                $shoppingBasket->setBasket(null);
            }
        }

        return $this;
    }
}
