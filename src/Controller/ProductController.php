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

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET','POST'])]
    public function index(ProductRepository $productRepository, Request $r): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($r);
        if($form->isSubmitted() && $form->isValid()){
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
        }

        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

/*    #[Route('/product/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepository): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->save($product, true);

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }*/

    #[Route('/product/{id}', name: 'app_product_show', methods: ['GET', 'POST'])]
    public function show(Product $product, Request $r, EntityManagerInterface $em): Response
    {
        $shoppingBasket = new ShoppingBasket();

        $formShoppingBasket = $this->createForm(ShoppingBasketType::class, $shoppingBasket);
        $formShoppingBasket->handleRequest($r);

        if($formShoppingBasket->isSubmitted() && $formShoppingBasket->isValid()){
            if($this->getUser() != null){
                $basket = $em->getRepository(Basket::class)->getCurrentBasket($this->getUser()); // On vient vérifier si on possède déjà un panier qui n'est pas encore "acheter"
                if($basket === null){ // Si on ne trouve aucun panier, on en créé un nouveau
                    $basket = new Basket();
                    $basket->setUser($this->getUser());
                    $em->persist($basket);
                }
                $shoppingBasket->setBasket($basket);
                $shoppingBasket->setProduct($product);
                $shoppingBasket->setCreatedAt(new \DateTime()); // J'ajoute le datetime ici et non pas dans le constructeur de ShoppingBasket, car ShoppingBasket est instancié au moment où l'utilisateur se rend sur la page et non pas au moment où il décide de mettre le produit dans son panier

                $em->persist($shoppingBasket);
                $em->flush();

                $this->addFlash('success', 'Le produit '. $product->getName() . ' a bien été ajouté à votre panier');
            }
            else{
                $this->addFlash('warning', 'Vous devez vous connecter pour pouvoir commander');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'formShoppingBasket' => $formShoppingBasket->createView(),
        ]);
    }

    #[Route('/product/edit/{id}', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        // TODO: CHANGER LE PRIX (*100 ou //100)

        // TODO: Mettre route EDIT et DELETE dans config pour qu'il y ait que l'ADMIN qui puisse le faire

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->save($product, true);

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productRepository->remove($product, true);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
