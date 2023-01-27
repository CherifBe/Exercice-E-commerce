<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Entity\ShoppingBasket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasketController extends AbstractController
{
    #[Route('/basket', name: 'app_basket')]
    public function index(EntityManagerInterface $em): Response
    {
        $shoppingBasket = $em->getRepository(ShoppingBasket::class)->getProductsFromCurrentBasket($this->getUser());
        return $this->render('basket/index.html.twig', [
            'products' => $shoppingBasket,
        ]);
    }

    #[Route('/basket/buy', name: 'app_basket_buy')]
    public function buy(EntityManagerInterface $em): Response
    {
        //TODO: Revoir ça, faire lifecycle ou un truc dans le genre
        $products = $em->getRepository(ShoppingBasket::class)->getProductsFromCurrentBasket($this->getUser());

        if(!$products[0]->getBasket()->isState()){
            $basket = $products[0]->getBasket();
            $basket->setState(true);
            $basket->setCreatedAt(new \DateTime()); // Date d'achat
            $em->persist($basket);
        }

        foreach($products as $productInBasket){
            $product = $productInBasket->getProduct();
            $stock = $product->getStock();
            $stock -= $productInBasket->getQuantity();
            $product->setStock($stock);
            $em->persist($product);
            $em->flush();
        }

        $this->addFlash('success', 'Votre commande a été bien prise en compte!');
        return $this->render('basket/thanks.html.twig');
    }

    #[Route('/basket/{id}/{change}', name: 'app_basket_change')]
    public function change(ShoppingBasket $shoppingBasket, EntityManagerInterface $em, string $change): Response
    {
        if($change != 'delete'){
            $quantity = $shoppingBasket->getQuantity();
            if($change == 'add'){
                $quantity++;
            }
            else if($change == 'minus'){
                $quantity--;
            }
            $shoppingBasket->setQuantity($quantity);
            $em->persist($shoppingBasket);
            $em->flush();
        }
        else {
            $em->remove($shoppingBasket);
            $em->flush();
        }

        $this->addFlash('success', 'Panier mis à jour');
        return $this->redirectToRoute('app_basket');
    }
}
