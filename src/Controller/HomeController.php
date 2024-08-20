<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Tickets;
use App\Entity\Purchase;
use App\Form\ContactType;
use App\Form\PurchaseType;
use Symfony\Component\Mime\Email;
use App\Repository\DrawsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
                'winnerName' => $lastDraw->getWinners(),
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
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactData = $form->getData();

            // Créer et envoyer l'email
            $email = (new Email())
                ->from($contactData['email'])
                ->to('palministephane@gmail.com') // Remplacez par votre adresse email
                ->subject($contactData['subject'])
                ->text($contactData['message']);

            $mailer->send($email);

            // Ajouter un message flash pour informer l'utilisateur
            $this->addFlash('success', 'Votre message a bien été envoyé.');

            // Rediriger vers la même page pour réinitialiser le formulaire
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('home/contact.html.twig', [
            'contactForm' => $form->createView(),
        ]);
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
    public function show($id, DrawsRepository $drawsRepository, Request $request): Response
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

            // Redirection vers la route de création de session de paiement
            return $this->redirectToRoute('purchase_ticket', [
                'drawId' => $draw->getId(),
                'quantity' => $quantity,
            ]);
        }

        return $this->render('home/show.html.twig', [
            'drawForView' => $draw,
            'form' => $form->createView(),
        ]);
    }

}