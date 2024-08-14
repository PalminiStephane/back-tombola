<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Purchase;
use App\Form\PurchaseType;
use App\Repository\DrawsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


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
     * @IsGranted("ROLE_USER")  // Seuls les utilisateurs avec ROLE_USER peuvent acheter des tickets
     */
    public function show($id, DrawsRepository $drawsRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $draw = $drawsRepository->find($id);

        if (!$draw) {
            throw $this->createNotFoundException('La tombola n\'existe pas');
        }

        // Créer le formulaire pour l'achat de tickets
        $form = $this->createForm(PurchaseType::class, null, [
            'max_tickets' => $draw->getTicketsAvailable(),
            'data'=> ['quantity' => 1], // Valeur par défaut pour le champ de quantité
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $form->get('quantity')->getData();

            if ($quantity > $draw->getTicketsAvailable()) {
                $this->addFlash('error', 'Le nombre de tickets demandés dépasse le nombre disponible.');
                return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
            }

            $purchase = new Purchase();
            $purchase->setUser($this->getUser());
            $purchase->setDraw($draw);
            $purchase->setQuantity($quantity);
            $purchase->setPurchaseDate(new \DateTime());
            $purchase->setStatus('completed');

            $entityManager->persist($purchase);

            $draw->setTicketsAvailable($draw->getTicketsAvailable() - $quantity);

            $entityManager->flush();

            $this->addFlash('success', 'Achat de tickets réalisé avec succès.');
            return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
        }

        return $this->render('home/show.html.twig', [
            'drawForView' => $draw,
            'form' => $form->createView(),
        ]);
    }
}