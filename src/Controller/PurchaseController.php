<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Purchase;
use App\Form\PurchaseType;
use App\Repository\DrawsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseController extends AbstractController
{
    /**
     * @Route("/buy-ticket/{id}", name="app_buy_ticket", methods={"GET", "POST"})
     */
    public function buyTicket(int $id, Request $request, DrawsRepository $drawsRepository, EntityManagerInterface $entityManager): Response
    {
        $draw = $drawsRepository->find($id);

        if (!$draw) {
            throw $this->createNotFoundException('Tombola non trouvée');
        }

        if ($draw->getStatus() !== 'open') {
            $this->addFlash('error', 'Les ventes de tickets sont fermées pour cette tombola.');
            return $this->redirectToRoute('app_tombola_show', ['id' => $id]);
        }

        $form = $this->createForm(PurchaseType::class, null, [
            'max_tickets' => $draw->getTicketsAvailable(),
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

        return $this->render('purchase/buy_ticket.html.twig', [
            'draw' => $draw,
            'form' => $form->createView(),
        ]);
    }
}
