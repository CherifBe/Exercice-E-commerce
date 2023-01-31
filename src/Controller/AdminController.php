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
        // TODO: Changer le title et le mettre en anglais
        // Cette fonction permet d'afficher tous les paniers des utilisateurs qui n'ont pas finalisés leur commande
        // Aussi cette fonction vient chercher la liste des utilisateurs en triant du plus récent vers le plus ancien
        $baskets_content = [];
        $users_content = [];
        $baskets = $em->getRepository(Basket::class)->findBy(['state'=>false]); // Je fais appel à tous les paniers en cours qui ne sont pas finalisés
        if($baskets !== null){
            $baskets_content = $baskets;
        }
        $users = $em->getRepository(User::class)->findBy([], ['id'=>'DESC']); // J'appelle les utilisateurs dans l'ordre inverse de leur création, c'est à dire dans l'ordre inverse des ID
        if($users !== null){
            $users_content = $users;
        }
        return $this->render('admin/index.html.twig', [
            'baskets' => $baskets_content,
            'users'=>$users_content,
        ]);
    }
}
