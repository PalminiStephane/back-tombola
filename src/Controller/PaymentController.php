<?php

namespace App\Controller;

use Stripe\Stripe;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PaymentController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/payment/create-session", name="payment_create_session")
     * @IsGranted("ROLE_USER")  // Seuls les utilisateurs avec ROLE_USER peuvent acheter des tickets
     */
    public function createSession(Request $request): Response
    {
        // Configurez Stripe avec votre clé secrète
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        // Créez une session de paiement
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Nom du produit',
                    ],
                    'unit_amount' => 2000, // Montant en centimes (20.00 EUR)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
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
            return new Response('', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Invalid signature: ' . $e->getMessage());
            return new Response('', 400);
        }
        

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                // Logique spécifique à la réussite du paiement
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                // Logique spécifique à l'échec du paiement
                break;
            // Ajoutez d'autres événements selon vos besoins...
        }
        

        return new Response('', 200);
    }
}

