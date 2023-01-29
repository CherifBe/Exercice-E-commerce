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
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        $baskets = $em->getRepository(Basket::class)->findBy(['state'=>false]);
        $users = $em->getRepository(User::class)->findBy([], ['id'=>'DESC']);
        return $this->render('admin/index.html.twig', [
            'baskets' => $baskets,
            'users'=>$users,
        ]);
    }
}
