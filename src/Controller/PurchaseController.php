<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Tickets;
use App\Entity\Purchase;
use App\Form\PurchaseType;
use App\Repository\DrawsRepository;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PurchaseController extends AbstractController
{
    /**
     * @Route("/purchase-ticket/{id}", name="app_purchase_ticket", methods={"GET", "POST"})
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

            // Configurer Stripe avec votre clé secrète
            Stripe::setApiKey($this->getParameter('stripe_secret_key'));

            // Créer une session de paiement Stripe
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Ticket pour ' . $draw->getTitle(),
                        ],
                        'unit_amount' => $draw->getTicketPrice() * 100, // Montant en centimes
                    ],
                    'quantity' => $quantity,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($session->url);
        }

        return $this->render('purchase/buy_ticket.html.twig', [
            'draw' => $draw,
            'form' => $form->createView(),
        ]);
    }
}
