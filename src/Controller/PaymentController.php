<?php

namespace App\Controller;

use App\Entity\User;
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

class PaymentController extends AbstractController
{
    private $logger;
    private $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/payment/create-session/{drawId}/{quantity}", name="payment_create_session")
     * @IsGranted("ROLE_USER")
     */
    public function createSession(int $drawId, int $quantity, int $userId): Response
{

    $user = $this->entityManager->getRepository(User::class)->find($userId);
    $draw = $this->entityManager->getRepository(Draws::class)->find($drawId);

    if (!$draw) {
        throw $this->createNotFoundException('Tombola non trouvée');
    }

    Stripe::setApiKey($this->getParameter('stripe_secret_key'));

    // Debug: Log les valeurs des métadonnées avant la création de la session
    $this->logger->info('Création de la session avec métadonnées', [
        'user_id' => $user->getId(),
        'draw_id' => $draw->getId(),
        'quantity' => $quantity,
    ]);

    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $draw->getTitle(),
                ],
                'unit_amount' => $draw->getTicketPrice() * 100, // en centimes
            ],
            'quantity' => $quantity,
        ]],
        'mode' => 'payment',
        'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
        'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        'metadata' => [
            'user_id' => $user->getId(),
            'draw_id' => $draw->getId(),
            'quantity' => $quantity
        ],
    ]);

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
 * @Route("/payment/webhook", name="payment_webhook", methods={"POST"})
 */
public function webhook(Request $request): Response
{
    $payload = $request->getContent();
    $sig_header = $request->headers->get('stripe-signature');
    $endpoint_secret = $this->getParameter('stripe_webhook_secret');

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        $this->logger->error('Invalid payload: ' . $e->getMessage());
        return new Response('Invalid payload', 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        $this->logger->error('Invalid signature: ' . $e->getMessage());
        return new Response('Invalid signature', 400);
    }

    $this->logger->info('Received event', ['type' => $event->type]);

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;

        $this->logger->info('Processing checkout.session.completed', ['session_id' => $session->id]);

        try {
            // Ajoutez des logs pour chaque étape de traitement
            $this->logger->info('Fetching metadata', [
                'user_id' => $session->metadata->user_id,
                'draw_id' => $session->metadata->draw_id,
                'quantity' => $session->metadata->quantity
            ]);

            $user = $this->entityManager->getRepository(User::class)->find($session->metadata->user_id);
            $draw = $this->entityManager->getRepository(Draws::class)->find($session->metadata->draw_id);

            if (!$user || !$draw) {
                $this->logger->error('User or draw not found', [
                    'user_id' => $session->metadata->user_id,
                    'draw_id' => $session->metadata->draw_id,
                ]);
                return new Response('User or draw not found', 404);
            }

            // Log avant de créer l'achat
            $this->logger->info('Creating purchase record');

            $purchase = new Purchase();
            $purchase->setUser($user);
            $purchase->setDraw($draw);
            $purchase->setQuantity($session->metadata->quantity);
            $purchase->setPurchaseDate(new \DateTime());
            $purchase->setStatus('completed');

            $this->entityManager->persist($purchase);

            // Log avant de créer les tickets
            $this->logger->info('Creating tickets');

            for ($i = 0; $i < $session->metadata->quantity; $i++) {
                $ticket = new Tickets();
                $ticket->setUser($user);
                $ticket->setDraw($draw);
                $ticket->setTicketNumber(mt_rand(100000, 999999));
                $ticket->setPurchaseDate(new \DateTime());
                $ticket->setStatus('purchased');
                $ticket->setPurchase($purchase);

                $this->entityManager->persist($ticket);
            }

            $draw->setTicketsAvailable($draw->getTicketsAvailable() - $session->metadata->quantity);

            // Log avant de sauvegarder en base de données
            $this->logger->info('Flushing data to the database');

            $this->entityManager->flush();
            $this->logger->info('Purchase and tickets saved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error during webhook processing: ' . $e->getMessage());
            return new Response('Error during webhook processing', 500);
        }
    }

    return new Response('Webhook handled', 200);
}

}
