<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'The title must have 2 caracters',
        maxMessage: 'The title must have 255 caracters',
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?int $price = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?int $stock = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'The picture title cannot have less than 2 caracters',
        maxMessage: 'The picture title cannot have more than 255 caracters',
    )]
    private ?string $image = null;
    // Ici on supprime les orphelins car lorsque l'on supprime un article, et que ce même article se trouve dans un panier non finalisé on produit une erreur, mais l'idéal serait d'aller supprimer
    // le produit de chaque panier non finalisé, et de le garder en base afin de garder une trace sur les commandes finalisées des utilisateurs
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ShoppingBasket::class, orphanRemoval: true)]
    private Collection $shoppingBaskets;

    public function __construct()
    {
        $this->shoppingBaskets = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

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
            $shoppingBasket->setProduct($this);
        }

        return $this;
    }

    public function removeShoppingBasket(ShoppingBasket $shoppingBasket): self
    {
        if ($this->shoppingBaskets->removeElement($shoppingBasket)) {
            // set the owning side to null (unless already changed)
            if ($shoppingBasket->getProduct() === $this) {
                $shoppingBasket->setProduct(null);
            }
        }

        return $this;
    }

    #[ORM\PostRemove]
    public function deleteImage(): void
    {
        if($this->image != null){
            unlink(__DIR__.'/../../public/uploads/'.$this->image);
        }
    }
}
