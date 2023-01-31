<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Form\UpdateProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $r, EntityManagerInterface $em, TranslatorInterface $t, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        // Cette fonction vient afficher la page d'accueil du profil de l'utilisateur
        // Sur cette page d'accueil l'utilisateur peut modifier ses propres données
        // La route profile est accessible uniquement si l'utilisateur a au minimum le role "ROLE_USER"
        $user = $this->getUser();
        $old_password = $user->getPassword();
        $form = $this->createForm(UpdateProfileType::class, $user);
        $form->handleRequest($r);

        if($form->isSubmitted() && $form->isValid()){
            $password = $form->get('password')->getData();
            if($password != null){ // Si l'utilisateur passe un nouveau mot de passe, on vient le hasher et l'initialiser dans l'objet user
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    ));
            } else { // Si l'utilisateur ne passe aucun mot de passe, on garde le même mot de passe
                $user->setPassword($old_password);
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', $t->trans('ProfileController.credentials-updated'));
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/orders', name: 'app_profile_orders')]
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
    public function showOrder(?Basket $basket, TranslatorInterface $t): Response
    {
        if($basket === null){
            return $this->redirectToRoute('app_profile_orders');
        }
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
