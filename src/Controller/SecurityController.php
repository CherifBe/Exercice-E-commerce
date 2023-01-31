<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Cette fonction vient afficher la page de connexion

        // Symfony nous propose un exemple de sécurité que l'on peut appliquer, c'est à dire de rediriger l'utilisateur
        // si jamais il est déjà connecté, que nous pouvons accompagner de addFlash pour informer l'utilisateur
        // if ($this->getUser()) {
        //     $this->addFlash('warning', 'You are already connected');
        //     return $this->redirectToRoute('target_path');
        // }

        // Mais à la place nous laissons la sécurité de base, qui renvoit un message si l'utilisateur est déjà connecté
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette fonction vient déconnecter l'utilisateur
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
