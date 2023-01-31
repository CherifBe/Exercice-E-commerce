<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Entity\ShoppingBasket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasketController extends AbstractController
{
    #[Route('/basket', name: 'app_basket')]
    public function index(EntityManagerInterface $em): Response
    {
        // Cette  fonction vient chercher le contenu du panier si il y en a un
        $basket_content = []; // On initialise un tableau vide
        $basket = $em->getRepository(Basket::class)->findOneBy(['user'=>$this->getUser(), 'state'=>false], null, 1);
        if($basket != null){ // Si la requête renvoie un objet on remplit le tableau avec les produits liés au panier
            $basket_content = $basket->getShoppingBaskets();
        }
        return $this->render('basket/index.html.twig', [
            'products' => $basket_content, // Ici on renvoit soit un tableau vide soit une liste d'objet
        ]);
    }

    #[Route('/basket/buy', name: 'app_basket_buy')]
    public function buy(EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        // TODO: FAIRE README
        // TODO: VERIFIER FONCTIONNEMENT DU SITE SANS BDD
        // TODO TRADUIRE MESSAGE DU FORM REGISTRATIONFORMTYPE

        // Cette fonction vient finaliser un achat en passant l'état d'une commande à VRAI et en retirant le stock des produits en fonction de la quantité commandée
        $basket = $em->getRepository(Basket::class)->findOneBy(['user'=>$this->getUser(), 'state'=>false]);
        if($basket === null){ // Si $basket est null on redirige au panier
            return $this->redirectToRoute('app_basket');
        }
        if(!$basket->isState()){ // Ici on passe l'état du panier à VRAI étant donné qu'à présent il est finalisé
            $basket->setState(true);
            $basket->setCreatedAt(new \DateTime()); // Date d'achat
            $em->persist($basket);
        }

        foreach($basket->getShoppingBaskets() as $productInBasket){ // Puisque nos produits sont achetés on vient modifier le stock de chaque produit
            $product = $productInBasket->getProduct();
            $stock = $product->getStock(); // On récupère le stock du produit
            $stock -= $productInBasket->getQuantity(); // On y retire la quantité du produit commandé
            $product->setStock($stock); // On set la nouvelle valeur du stock
            $em->persist($product);
            $em->flush();
        }

        $this->addFlash('success', $t->trans('BasketController.buy-success'));
        return $this->render('basket/thanks.html.twig');
    }

    #[Route('/basket/{id}/{change}', name: 'app_basket_change')]
    public function change(ShoppingBasket $shoppingBasket, EntityManagerInterface $em, string $change, TranslatorInterface $t): Response
    {
        // Cette fonction vient "attraper" tous les types de changement possible de la page basket
        // Lorsque l'utilisateur modifie la quantité ou supprime un article, il envoie une valeur $change, soit pour augmenter la quantité,
        // pour diminuer la quantité ou encore supprimer l'article
        switch ($change){
            case 'remove':
                $em->remove($shoppingBasket);
                $em->flush();
                $this->addFlash('success', $t->trans('BasketController.remove-product'));
                break;
            case 'add':
                $quantity = $shoppingBasket->getQuantity();
                $quantity++;
                $shoppingBasket->setQuantity($quantity);
                $em->persist($shoppingBasket);
                $em->flush();
                $this->addFlash('success', $t->trans('BasketController.change-quantity'));
                break;
            case 'minus':
                $quantity = $shoppingBasket->getQuantity();
                $quantity--;
                if($quantity < 1){
                    $em->remove($shoppingBasket); // Si jamais la quantité est inférieur à 1 on vient supprimer l'article du panier
                    $this->addFlash('success', $t->trans('BasketController.remove-product'));
                } else {
                    $shoppingBasket->setQuantity($quantity);
                    $em->persist($shoppingBasket);
                    $this->addFlash('success', $t->trans('BasketController.change-quantity'));
                }
                $em->flush();
                break;
            default:
                return $this->redirectToRoute('app_basket'); // Si jamais la valeur de $change de correspond à aucun de nos cas, on redirige
        }
        $this->addFlash('success', $t->trans('BasketController.cart-updated'));
        return $this->redirectToRoute('app_basket');
    }
}
