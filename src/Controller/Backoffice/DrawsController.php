<?php

namespace App\Controller\Backoffice;

use App\Entity\Draws;
use App\Form\DrawsType;
use App\Repository\DrawsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backoffice/draws")
 */
class DrawsController extends AbstractController
{
    /**
     * @Route("/", name="app_backoffice_draws_index", methods={"GET"})
     */
    public function index(DrawsRepository $drawsRepository): Response
    {
        return $this->render('backoffice/draws/index.html.twig', [
            'draws' => $drawsRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_backoffice_draws_new", methods={"GET", "POST"})
     */
    public function new(Request $request, DrawsRepository $drawsRepository): Response
    {
        $draw = new Draws();
        $form = $this->createForm(DrawsType::class, $draw);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $drawsRepository->add($draw, true);

            return $this->redirectToRoute('app_backoffice_draws_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/draws/new.html.twig', [
            'draw' => $draw,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_draws_show", methods={"GET"})
     */
    public function show(Draws $draw): Response
    {
        return $this->render('backoffice/draws/show.html.twig', [
            'draw' => $draw,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_backoffice_draws_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Draws $draw, DrawsRepository $drawsRepository): Response
    {
        $form = $this->createForm(DrawsType::class, $draw);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $drawsRepository->add($draw, true);

            return $this->redirectToRoute('app_backoffice_draws_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/draws/edit.html.twig', [
            'draw' => $draw,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_draws_delete", methods={"POST"})
     */
    public function delete(Request $request, Draws $draw, DrawsRepository $drawsRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$draw->getId(), $request->request->get('_token'))) {
            $drawsRepository->remove($draw, true);
        }

        return $this->redirectToRoute('app_backoffice_draws_index', [], Response::HTTP_SEE_OTHER);
    }
}
