<?php

namespace App\Controller;

use App\Entity\Draws;
use App\Entity\Tickets;
use App\Entity\Purchase;
use App\Repository\DrawsRepository;
use App\Form\PurchaseType;
use App\Service\PayPalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private $paypalService;
    private $entityManager;

    public function __construct(PayPalService $paypalService, EntityManagerInterface $entityManager)
    {
        $this->paypalService = $paypalService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/purchase-ticket/{id}", name="app_purchase_ticket", methods={"GET", "POST"})
     */
    public function buyTicket(int $id, Request $request, DrawsRepository $drawsRepository): Response
    {
        $draw = $drawsRepository->find($id);

        if (!$draw) {
            throw $this->createNotFoundException('Tombola non trouvée');
        }

        $form = $this->createForm(PurchaseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $form->get('quantity')->getData();
            $amount = $draw->getTicketPrice() * $quantity;

            $order = $this->paypalService->createOrder($amount);

            if ($order && isset($order['id'])) {
                $purchase = new Purchase();
                $purchase->setUser($this->getUser());
                $purchase->setDraw($draw);
                $purchase->setQuantity($quantity);
                $purchase->setPurchaseDate(new \DateTime()); // Assurez-vous que cette ligne est présente
                $purchase->setStatus('pending');
                $purchase->setPaypalOrderId($order['id']);
                $this->entityManager->persist($purchase);
                $this->entityManager->flush();

                foreach ($order['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        return $this->redirect($link['href']);
                    }
                }
            }
        }

        return $this->render('payment/buy_ticket.html.twig', [
            'form' => $form->createView(),
            'draw' => $draw,
        ]);
    }

/**
 * @Route("/payment/success", name="payment_success")
 */
public function success(Request $request): Response
{
    $orderId = $request->query->get('token');
    $capture = $this->paypalService->captureOrder($orderId);

    if ($capture && isset($capture['status']) && $capture['status'] === 'COMPLETED') {
        $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
            'paypalOrderId' => $orderId,
        ]);

        if ($purchase) {
            $purchase->setStatus('completed');

            // Génération des tickets après succès du paiement
            for ($i = 0; $i < $purchase->getQuantity(); $i++) {
                $ticket = new Tickets(); // Utilisation de l'entité Tickets
                $ticket->setUser($purchase->getUser());
                $ticket->setDraw($purchase->getDraw());
                $ticket->setTicketNumber(mt_rand(100000, 999999)); // Vous pouvez utiliser une méthode plus robuste pour générer des numéros uniques
                $ticket->setPurchaseDate(new \DateTime());
                $ticket->setStatus('purchased');
                $ticket->setPurchase($purchase);

                $this->entityManager->persist($ticket);
            }

            // Mise à jour du nombre de tickets disponibles pour le tirage
            $remainingTickets = $purchase->getDraw()->getTicketsAvailable() - $purchase->getQuantity();
            $purchase->getDraw()->setTicketsAvailable($remainingTickets);

            $this->entityManager->flush();
            $this->addFlash('success', 'Paiement réussi et vos tickets ont été créés !');
        }
    }

    return $this->render('payment/success.html.twig');
}


    /**
     * @Route("/payment/cancel", name="payment_cancel")
     */
    public function cancel(): Response
    {
        $this->addFlash('error', 'Le paiement a été annulé.');
        return $this->render('payment/cancel.html.twig');
    }
}

