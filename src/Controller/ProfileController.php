<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Form\UpdateProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $r, EntityManagerInterface $em): Response
    {
        // TODO: Vérifier si password n'est pas modifier car on ne met rien dans l'input password
        $user = $this->getUser();
        $form = $this->createForm(UpdateProfileType::class, $user);
        $form->handleRequest($r);

        if($form->isSubmitted() && $form->isValid()){
            $em->persist($user);
            $em->flush();
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/orders', name: 'app_profile_order')]
    public function showOrders(EntityManagerInterface $em): Response
    {
        // TODO: Voir si symfony nous propose pas déjà une fonction toute faite pour éviter de créer nous même là query
        $orders = $em->getRepository(Basket::class)->findBy(['user'=>$this->getUser(),'state'=> true]); // TODO: Mettre orderBy

        return $this->render('profile/order.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/profile/orders/{id}', name: 'app_profile_show_order')]
    public function showOrder(Basket $basket): Response
    {
        // TODO: Vérifier les ID plutot que les objets User
        if($basket->getUser() !== $this->getUser()){ // Si jamais l'utilisateur passe un autre ID dans l'URL et qu'il ne s'agit pas de SON panier, on le redirige
            $this->addFlash('warning', 'Page non accessible');
            return $this->redirect('/');
        }

        return $this->render('profile/ordershow.html.twig', [
            'order'=>$basket
        ]);



    }
}
