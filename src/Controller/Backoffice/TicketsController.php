<?php

namespace App\Controller\Backoffice;

use App\Entity\Tickets;
use App\Form\TicketsType;
use App\Repository\TicketsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backoffice/tickets")
 */
class TicketsController extends AbstractController
{
    /**
     * @Route("/", name="app_backoffice_tickets_index", methods={"GET"})
     */
    public function index(TicketsRepository $ticketsRepository): Response
    {
        return $this->render('backoffice/tickets/index.html.twig', [
            'tickets' => $ticketsRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_backoffice_tickets_new", methods={"GET", "POST"})
     */
    public function new(Request $request, TicketsRepository $ticketsRepository): Response
    {
        $ticket = new Tickets();
        $form = $this->createForm(TicketsType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketsRepository->add($ticket, true);

            return $this->redirectToRoute('app_backoffice_tickets_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/tickets/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_tickets_show", methods={"GET"})
     */
    public function show(Tickets $ticket): Response
    {
        return $this->render('backoffice/tickets/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_backoffice_tickets_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Tickets $ticket, TicketsRepository $ticketsRepository): Response
    {
        $form = $this->createForm(TicketsType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketsRepository->add($ticket, true);

            return $this->redirectToRoute('app_backoffice_tickets_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/tickets/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_tickets_delete", methods={"POST"})
     */
    public function delete(Request $request, Tickets $ticket, TicketsRepository $ticketsRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $ticketsRepository->remove($ticket, true);
        }

        return $this->redirectToRoute('app_backoffice_tickets_index', [], Response::HTTP_SEE_OTHER);
    }
}
