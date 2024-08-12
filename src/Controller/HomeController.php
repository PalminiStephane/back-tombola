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
        // Récupère les tombolas ouvertes et non passées, triées par date de tirage
        $query = $drawsRepository->findOpenDraws();

        // Pagination des résultats
        $draws = $paginator->paginate(
            $query, // query NOT result
            $request->query->getInt('page', 1), // Numéro de la page actuelle, 1 par défaut
            5 // Limit par page
        );

        // Récupérer la dernière tombola fermée (c'est-à-dire dont le statut est "closed")
        $lastDraw = $drawsRepository->findOneBy(['status' => 'closed'], ['drawDate' => 'DESC']);

        // Initialiser la variable du dernier gagnant et du lot
        $lastWinner = null;

        if ($lastDraw) {
            $lastWinner = [
                'winnerName' => $lastDraw->getWinnerName(),
                'prize' => $lastDraw->getPrize()
            ];
        }

        return $this->render('home/index.html.twig', [
            'draws' => $draws,
            'lastWinner' => $lastWinner,
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
