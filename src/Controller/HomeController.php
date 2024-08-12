<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Repository\DrawsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="default")
     * @Route("/home", name="app_home")
     */
    public function index(Request $request, DrawsRepository $drawsRepository, PaginatorInterface $paginator): Response
        {
            // Récupération de la requête de tous les tirages ouverts triés par date de tirage
            $queryBuilder = $drawsRepository->findByQueryBuilder(['status' => 'open'], ['drawDate' => 'ASC']);
    
            // Utilisation du paginator pour diviser les résultats en pages
            $draws = $paginator->paginate(
                $queryBuilder, // La requête et non le résultat
                $request->query->getInt('page', 1), // numéro de page, 1 par défaut
                5 // nombre d'éléments par page
            );
    
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
     * @Route("/draws/{id}", name="app_tombola_show", requirements={"id"="\d+"})
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
