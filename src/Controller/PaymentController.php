<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Draws;
use App\Entity\Tickets;
use App\Entity\Purchase;
use App\Form\PurchaseType;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use App\Repository\DrawsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

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

            // Créer l'enregistrement Purchase
            $purchase = new Purchase();
            $purchase->setUser($this->getUser());
            $purchase->setDraw($draw);
            $purchase->setQuantity($quantity);
            $purchase->setPurchaseDate(new \DateTime());
            $purchase->setStatus('pending');
            $purchase->setStripeSessionId($session->id);

            $entityManager->persist($purchase);
            $entityManager->flush();

            // Rediriger vers Stripe Checkout
            return $this->redirect($session->url);
        }

        return $this->render('payment/buy_ticket.html.twig', [
            'draw' => $draw,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/purchase/{drawId}/{quantity}", name="purchase_ticket")
     */
    public function createCheckoutSession(int $drawId, int $quantity): Response
    {
        // Logic for creating a Stripe checkout session
        $user = $this->getUser();

        $draw = $this->entityManager->getRepository(Draws::class)->find($drawId);
        if (!$draw) {
            throw $this->createNotFoundException('Tombola non trouvée.');
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $draw->getTitle(),
                    ],
                    'unit_amount' => $draw->getTicketPrice() * 100,
                ],
                'quantity' => $quantity,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $purchase = new Purchase();
        $purchase->setUser($user);
        $purchase->setDraw($draw);
        $purchase->setQuantity($quantity);
        $purchase->setPurchaseDate(new \DateTime());
        $purchase->setStatus('pending');
        $purchase->setStripeSessionId($session->id);

        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $this->redirect($session->url, 303);
    }

    /**
     * @Route("/payment/success", name="payment_success")
     */
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    /**
     * @Route("/payment/cancel", name="payment_cancel")
     */
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    /**
     * @Route("/payment/webhook", name="payment_webhook")
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $this->getParameter('stripe_webhook_secret');

        $this->logger->info('Webhook received', ['payload' => $payload, 'signature' => $sigHeader]);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch(\UnexpectedValueException $e) {
            $this->logger->error('Invalid payload', ['error' => $e->getMessage()]);
            return new Response('', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Invalid signature', ['error' => $e->getMessage()]);
            return new Response('', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $this->logger->info('Processing checkout.session.completed event', ['session_id' => $session->id]);

            $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
                'stripeSessionId' => $session->id,
            ]);

            if ($purchase) {
                $purchase->setStatus('completed');

                for ($i = 0; $i < $purchase->getQuantity(); $i++) {
                    $ticket = new Tickets();
                    $ticket->setUser($purchase->getUser());
                    $ticket->setDraw($purchase->getDraw());
                    $ticket->setTicketNumber(mt_rand(100000, 999999));
                    $ticket->setPurchaseDate(new \DateTime());
                    $ticket->setStatus('purchased');
                    $ticket->setPurchase($purchase);

                    $this->entityManager->persist($ticket);
                }

                $purchase->getDraw()->setTicketsAvailable(
                    $purchase->getDraw()->getTicketsAvailable() - $purchase->getQuantity()
                );

                $this->entityManager->flush();
                $this->logger->info('Purchase completed and tickets created', ['purchase_id' => $purchase->getId()]);
            } else {
                $this->logger->error('No purchase found for session ID: ' . $session->id);
            }
        }

        return new Response('Webhook handled', 200);
    }

}
