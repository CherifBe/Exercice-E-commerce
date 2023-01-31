<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Form\UpdateProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $r, EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        // Cette fonction vient afficher la page d'accueil du profil de l'utilisateur
        // Sur cette page d'accueil l'utilisateur peut modifier ses propres données
        // La route profile est accessible uniquement si l'utilisateur a au minimum le role "ROLE_USER"
        // TODO: faire navigation dans le profile
        // TODO: Vérifier si password n'est pas modifier car on ne met rien dans l'input password
        $user = $this->getUser();
        $form = $this->createForm(UpdateProfileType::class, $user);
        $form->handleRequest($r);

        if($form->isSubmitted() && $form->isValid()){
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', $t->trans('ProfileController.credentials-updated'));
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/orders', name: 'app_profile_order')]
    public function showOrders(EntityManagerInterface $em): Response
    {
        // Cette fonction vient afficher les différentes commandes passées par l'utilisateur
        $orders_content = [];
        // On vient chercher tous les commandes finalisées de l'utilisateur courant, en les triant de la plus récente à la plus ancienne
        $orders = $em->getRepository(Basket::class)->findBy(['user'=>$this->getUser(),'state'=> true], ['createdAt'=>'DESC']);
        if($orders !== null){ // Si la requête nous renvoie quelque chose on initialise $orders_content
            $orders_content = $orders;
        }
        return $this->render('profile/order.html.twig', [
            'orders' => $orders_content,
        ]);
    }

    #[Route('/profile/orders/{id}', name: 'app_profile_show_order')]
    public function showOrder(Basket $basket, TranslatorInterface $t): Response
    {
        // Cette fonction vient afficher la commande de l'utilisateur en détail
        $user = $basket->getUser();
        if(($this->getUser() === null) or ($user->getId() !== $this->getUser()->getId())){ // Si jamais l'utilisateur passe un autre ID dans l'URL et qu'il ne s'agit pas de SON panier, on le redirige
            $this->addFlash('warning', $t->trans('ProfileController.no-access'));
            return $this->redirect('/');
        }

        return $this->render('profile/ordershow.html.twig', [
            'order'=>$basket
        ]);



    }
}
