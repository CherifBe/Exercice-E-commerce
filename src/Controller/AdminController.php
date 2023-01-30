<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    // Cette route est accessible uniquement pour les super admin
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        $baskets = $em->getRepository(Basket::class)->findBy(['state'=>false]); // Je fais appel à tous les paniers en cours qui ne sont pas finalisés
        $users = $em->getRepository(User::class)->findBy([], ['id'=>'DESC']); // J'appelle les utilisateurs dans l'ordre inverse de leur création, c'est à dire dans l'ordre inverse des ID
        return $this->render('admin/index.html.twig', [
            'baskets' => $baskets,
            'users'=>$users,
        ]);
    }
}
