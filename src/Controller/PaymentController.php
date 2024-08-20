<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Draws;
use App\Entity\Tickets;
use App\Entity\Purchase;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Helper\Dumper;

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
     * @Route("/purchase/{drawId}/{quantity}", name="purchase_ticket")
     * @IsGranted("ROLE_USER")
     */
    public function createCheckoutSession(int $drawId, int $quantity): Response
    {
        $user = $this->getUser();

        $draw = $this->entityManager->getRepository(Draws::class)->find($drawId);
        if (!$draw) {
            throw $this->createNotFoundException('Tombola non trouvée.');
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        try {
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
                'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            // Sauvegarder les informations d'achat dans la BDD avec le statut "pending"
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
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la session Stripe : ' . $e->getMessage());
            throw $this->createNotFoundException('Erreur lors de la création de la session de paiement.');
        }
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
     * @Route("/payment/webhook", name="payment_webhook", methods={"POST"})
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
    
        $this->logger->info('Webhook event type', ['type' => $event->type]);
    
        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
    
            $this->logger->info('Processing checkout.session.completed event', ['session_id' => $session->id]);
    
            // Mettre à jour l'achat associé
            $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
                'stripeSessionId' => $session->id,
            ]);
    
            if ($purchase) {
                $purchase->setStatus('completed');
                $this->logger->info('Purchase found and status updated', ['purchase_id' => $purchase->getId()]);
    
                // Créer des tickets pour l'utilisateur
                for ($i = 0; $i < $purchase->getQuantity(); $i++) {
                    $ticket = new Tickets();
                    $ticket->setUser($purchase->getUser());
                    $ticket->setDraw($purchase->getDraw());
                    $ticket->setTicketNumber(mt_rand(100000, 999999));
                    $ticket->setPurchaseDate(new \DateTime());
                    $ticket->setStatus('purchased');
                    $ticket->setPurchase($purchase);
    
                    $this->entityManager->persist($ticket);
                    $this->logger->info('Ticket created', ['ticket_number' => $ticket->getTicketNumber()]);
                }
    
                // Mettre à jour les tickets disponibles
                $purchase->getDraw()->setTicketsAvailable(
                    $purchase->getDraw()->getTicketsAvailable() - $purchase->getQuantity()
                );
    
                $this->entityManager->flush();
                $this->logger->info('Database changes flushed');
            } else {
                $this->logger->warning('No purchase found for session', ['session_id' => $session->id]);
            }
        } else {
            $this->logger->info('Event type not handled', ['type' => $event->type]);
        }
    
        return new Response('Webhook handled', 200);
    }
    



        //     // Log all headers
        // $headers = $request->headers->all();
        // $this->logger->info('Received headers', $headers);

        // // Extract the Stripe signature header
        // $sigHeader = $request->headers->get('stripe-signature');
        // if (!$sigHeader) {
        //     $this->logger->error('Missing stripe-signature header');
        //     return new Response('Missing stripe-signature header', 400);
        // }
        // $payload = $request->getContent();
        // $endpointSecret = $this->getParameter('stripe_webhook_secret');

        // $this->logger->info('Received Stripe webhook with payload: ' . $payload);


        // try {
        //     $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        //     $this->logger->info('Webhook event received: ' . $event->type);
        // } catch(\UnexpectedValueException $e) {
        //     $this->logger->error('Invalid payload: ' . $e->getMessage());
        //     return new Response('Invalid payload', 400);
        // } catch(\Stripe\Exception\SignatureVerificationException $e) {
        //     $this->logger->error('Invalid signature: ' . $e->getMessage());
        //     return new Response('Invalid signature', 400);
        // }

        // $this->logger->info('Stripe event type: ' . $event->type);

        // // Handle the event
        // switch ($event->type) {
        //     case 'checkout.session.completed':
        //         $session = $event->data->object;
        //         $this->logger->info('Checkout session completed: ' . json_encode($session));

        //         // Mettre à jour l'achat associé
        //         $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy([
        //             'stripeSessionId' => $session->id,
        //         ]);

        //         if ($purchase) {
        //             $this->logger->info('Purchase found for session: ' . $session->id);
        //             $purchase->setStatus('completed');

        //             // Créer des tickets pour l'utilisateur
        //             for ($i = 0; $i < $purchase->getQuantity(); $i++) {
        //                 $ticket = new Tickets();
        //                 $ticket->setUser($purchase->getUser());
        //                 $ticket->setDraw($purchase->getDraw());
        //                 $ticket->setTicketNumber(mt_rand(100000, 999999));
        //                 $ticket->setPurchaseDate(new \DateTime());
        //                 $ticket->setStatus('purchased');
        //                 $ticket->setPurchase($purchase);

        //                 $this->entityManager->persist($ticket);
        //             }

        //             // Mettre à jour les tickets disponibles
        //             $purchase->getDraw()->setTicketsAvailable(
        //                 $purchase->getDraw()->getTicketsAvailable() - $purchase->getQuantity()
        //             );

        //             $this->entityManager->flush();
        //             $this->logger->info('Purchase and tickets updated for session: ' . $session->id);
        //         } else {
        //             $this->logger->error('Achat non trouvé pour cette session Stripe : ' . $session->id);
        //         }

        //         break;
        //     // handle other event types
        //     default:
        //         $this->logger->warning('Event type not handled: ' . $event->type);
        //         return new Response('Event type not handled', 400);
        // }

        // return new Response('Webhook handled', 200);
    // }
}
