<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Entity\Product;
use App\Entity\ShoppingBasket;
use App\Form\BasketType;
use App\Form\ProductType;
use App\Form\ShoppingBasketType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET','POST'])]
    public function index(ProductRepository $productRepository, Request $r, TranslatorInterface $t): Response
    {
        // La fonction index vient afficher la page, elle a pour but d'afficher tous les produits, ainsi que le formulaire pour enregistrer les produits si un administrateur est connecté
        $form = [];
        $user = $this->getUser();
        if($user !== null){
            $role = $user->getRoles()[0];
            if($role === 'ROLE_ADMIN' or $role === 'ROLE_SUPER_ADMIN'){// On vient créer le formulaire uniquement si l'utilisateur en cours est un admin, on évite ainsi les POST extérieurs
                $product = new Product();
                $form = $this->createForm(ProductType::class, $product);
                $form->handleRequest($r);
                if($form->isSubmitted() && $form->isValid()){
                    $product->setPrice($product->getPrice() * 100);
                    $image = $form->get('image')->getData();
                    if ($image) {
                        $newFilename = uniqid().'.'.$image->guessExtension();
                        try {
                            $image->move(
                                $this->getParameter('upload_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            return $this->redirectToRoute('app_product_index');
                        }
                        $product->setImage($newFilename);
                    }
                    $productRepository->save($product, true);
                    $this->addFlash('success', $t->trans('ProductController.admin.product-created'));
                }
                $form = $form->createView();
            }
        }
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
            'form' => $form,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show', methods: ['GET', 'POST'])]
    public function show(Product $product, Request $r, EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        // La fonction "show" vient afficher un produit en détail et propose un formulaire pour permettre à l'utilisateur d'entrer une quantité lorsqu'il veut acheter le produit
        $shoppingBasket = new ShoppingBasket();

        $formShoppingBasket = $this->createForm(ShoppingBasketType::class, $shoppingBasket);
        $formShoppingBasket->handleRequest($r);

        if($formShoppingBasket->isSubmitted() && $formShoppingBasket->isValid()) {
            if ($this->getUser() != null) { // Si l'utilisateur existe on enregistre le produit dans son panier
                $basket = $em->getRepository(Basket::class)->findOneBy(['user' => $this->getUser(), 'state' => false]);// On vient vérifier si on possède déjà un panier qui n'est pas encore "finalisé"
                if ($basket === null) { // Si on ne trouve aucun panier, on en créé un nouveau
                    $basket = new Basket();
                    $basket->setUser($this->getUser());
                    $em->persist($basket);
                } else {
                    // Un problème se pose, si nous mettons deux fois le même produit dans notre panier,
                    // ce dernier va apparaitre 2 fois dans le panier ALORS QUE il serait préférable de voir une seule
                    // ligne de ce produit avec une quantité variable

                    // Ainsi on procède à une requête findBy le produit que nous recevons que le panier en cours
                    $same_shopping_basket = $em->getRepository(ShoppingBasket::class)->findOneBy(['basket' => $basket, 'product' => $product]);
                    if ($same_shopping_basket !== null) { // S'il s'avère que le même produit est déjà dans la base on vient modifier la quantité du produit et on renvoit l'objet modifié dans la base
                        $same_shopping_basket->setQuantity($same_shopping_basket->getQuantity() + 1);
                        $same_shopping_basket->setCreatedAt(new \DateTime());
                        $em->persist($same_shopping_basket);
                        $em->flush();

                        return $this->render('product/show.html.twig', [
                            'product' => $product,
                            'formShoppingBasket' => $formShoppingBasket->createView(),
                        ]);
                    }
                }
                $shoppingBasket->setBasket($basket);
                $shoppingBasket->setProduct($product);
                $shoppingBasket->setCreatedAt(new \DateTime()); // J'ajoute le datetime ici et non pas dans le constructeur de ShoppingBasket, car ShoppingBasket est instancié au moment où l'utilisateur se rend sur la page et non pas au moment où il décide de mettre le produit dans son panier

                $em->persist($shoppingBasket);
                $em->flush();

                $this->addFlash('success', $t->trans('ProductController.product-add-part-one') . ' ' . $product->getName() . ' ' . $t->trans('ProductController.product-add-part-two'));

            } else { // Si l'utilisateur n'est pas connecté, il est redirigé vers la page de connexion où il sera inviter à se connecter
                $this->addFlash('warning', $t->trans('ProductController.connect-first'));
                return $this->redirectToRoute('app_login');
            }
        }


        return $this->render('product/show.html.twig', [
            'product' => $product,
            'formShoppingBasket' => $formShoppingBasket->createView(),
        ]);
    }

    #[Route('/product/edit/{id}', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, ProductRepository $productRepository, TranslatorInterface $t): Response
    {
        // La fonction edit permet de modifier les informations d'un produit
        // Les routes contenant "edit" sont accessibles uniquement pour les admins
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->save($product, true);
            $this->addFlash('success', $t->trans('ProductController.admin.product-updated'));
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, ProductRepository $productRepository, TranslatorInterface $t): Response
    {
        // La fonction delete permet de supprimer un produit
        // Les routes contenant "delete" sont accessibles uniquement pour les admins
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productRepository->remove($product, true);
        }
        $this->addFlash('success', $t->trans('ProductController.admin.product-removed'));
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
