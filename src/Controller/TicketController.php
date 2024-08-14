<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Tickets;
use App\Repository\DrawsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TicketController extends AbstractController
{
    /**
     * @Route("/buy-ticket/{id}", name="app_buy_ticket", methods={"POST"})
     */
    public function buyTicket(int $id, Request $request, DrawsRepository $drawsRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la tombola par son ID
        $draw = $drawsRepository->find($id);

        if (!$draw) {
            throw $this->createNotFoundException('Tombola non trouvée');
        }

        // Vérifier que la tombola est toujours ouverte
        if ($draw->getStatus() !== 'open') {
            $this->addFlash('error', 'Les ventes de tickets sont fermées pour cette tombola.');
            return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
        }

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour acheter un ticket.');
        }

        // Récupérer le nombre de tickets à acheter
        $quantity = (int) $request->request->get('quantity', 1);

        // Vérifier la disponibilité des tickets
        if ($quantity > $draw->getTicketsAvailable()) {
            $this->addFlash('error', 'Le nombre de tickets demandés dépasse le nombre disponible.');
            return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
        }

        // Créer les tickets et les associer à l'utilisateur et à la tombola
        for ($i = 0; $i < $quantity; $i++) {
            $ticket = new Tickets();
            $ticket->setUser($user);
            $ticket->setDraw($draw);
            $ticket->setTicketNumber(mt_rand(100000, 999999)); // Numéro de ticket unique
            $ticket->setPurchaseDate(new \DateTime());
            $ticket->setStatus('purchased');

            $entityManager->persist($ticket);
        }

        // Mettre à jour le nombre de tickets disponibles
        $draw->setTicketsAvailable($draw->getTicketsAvailable() - $quantity);

        // Sauvegarder les changements
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez acheté ' . $quantity . ' ticket(s) avec succès.');
        return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
    }
}
