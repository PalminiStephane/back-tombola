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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted; // Import correct pour les annotations

class TicketController extends AbstractController
{
    /**
     * @Route("/buy_ticket/{id}", name="app_buy_ticket", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function buyTicket(int $id, Request $request, DrawsRepository $drawsRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la tombola par ID
        $draw = $drawsRepository->find($id);

        if (!$draw || $draw->getStatus() !== 'open') {
            throw $this->createNotFoundException('Tombola non trouvée ou non ouverte.');
        }

        // Créer un nouveau ticket pour l'utilisateur connecté
        $ticket = new Tickets();
        $ticket->setUser($this->getUser());
        $ticket->setDraw($draw);

        // Mise à jour du nombre de tickets disponibles
        $draw->setTicketsAvailable($draw->getTicketsAvailable() - 1);

        // Persister les modifications
        $entityManager->persist($ticket);
        $entityManager->persist($draw);
        $entityManager->flush();

        $this->addFlash('success', 'Votre ticket a été acheté avec succès.');

        return $this->redirectToRoute('app_tombola_show', ['id' => $draw->getId()]);
    }
}
