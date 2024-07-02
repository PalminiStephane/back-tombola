<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Repository\DrawsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="default")
     * @Route("/home", name="app_home")
     */
    public function index(DrawsRepository $drawsRepository): Response
    {
        // J'ai besoin de la liste des tombolas pour les afficher sur la page d'accueil
        $draws = $drawsRepository->findBy(['status' => 'open'], ['drawDate' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'draws' => $draws,
        ]);
    }

    /**
     * @Route("/contact", name="app_contact")
     */
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    /**
     * @Route("/about", name="app_about")
     */
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    /**
     * @Route("/draws/{id}", name="app_home_show", requirements={"id"="\d+"})
     * @return Response
     */
    public function show($id, DrawsRepository $drawsRepository): Response
    {
        $draw = $drawsRepository->find($id);
        if (!$draw) {
            throw $this->createNotFoundException('La tombola n\'existe pas');
        }

        return $this->render('home/show.html.twig', [
            'drawForView' => $draw,
        ]);
    }
}
