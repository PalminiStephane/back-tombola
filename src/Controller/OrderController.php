<?php

namespace App\Controller;

use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    /**
     * @Route("/order/history", name="app_order_history")
     */
    public function orderHistory(EntityManagerInterface $entityManager, UserInterface $user, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer les achats de l'utilisateur connecté
        $query = $entityManager->getRepository(Purchase::class)->findBy(['user' => $user]);

        // Paginer les résultats
        $orders = $paginator->paginate(
            $query, // Requête
            $request->query->getInt('page', 1), // Numéro de page, 1 par défaut
            10 // Limite de résultats par page
        );

        return $this->render('order/history.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @Route("/order/{id}/tickets", name="app_order_tickets")
     */
    public function orderTickets(Purchase $purchase): Response
    {
        $user = $this->getUser();

        // Vérifie si l'utilisateur est le propriétaire de l'achat
        if ($purchase->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette ressource.');
        }

        // Vérifie si l'email de l'utilisateur est vérifié
        if (!$user->getIsEmailVerified()) {
            return $this->redirectToRoute('app_email_not_verified');
        }

        return $this->render('order/tickets.html.twig', [
            'purchase' => $purchase,
            'tickets' => $purchase->getTickets(),
        ]);
    }
}
