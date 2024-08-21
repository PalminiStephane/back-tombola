<?php

namespace App\Controller\Backoffice;

use App\Entity\Purchase;
use App\Form\Purchase1Type;
use App\Repository\PurchaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backoffice/purchase")
 */
class PurchaseController extends AbstractController
{
    /**
     * @Route("/", name="app_backoffice_purchase_index", methods={"GET"})
     */
    public function index(PurchaseRepository $purchaseRepository): Response
    {
        return $this->render('backoffice/purchase/index.html.twig', [
            'purchases' => $purchaseRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_backoffice_purchase_new", methods={"GET", "POST"})
     */
    public function new(Request $request, PurchaseRepository $purchaseRepository): Response
    {
        $purchase = new Purchase();
        $form = $this->createForm(Purchase1Type::class, $purchase);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $purchaseRepository->add($purchase, true);

            return $this->redirectToRoute('app_backoffice_purchase_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/purchase/new.html.twig', [
            'purchase' => $purchase,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_purchase_show", methods={"GET"})
     */
    public function show(Purchase $purchase): Response
    {
        return $this->render('backoffice/purchase/show.html.twig', [
            'purchase' => $purchase,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_backoffice_purchase_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Purchase $purchase, PurchaseRepository $purchaseRepository): Response
    {
        $form = $this->createForm(Purchase1Type::class, $purchase);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $purchaseRepository->add($purchase, true);

            return $this->redirectToRoute('app_backoffice_purchase_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('backoffice/purchase/edit.html.twig', [
            'purchase' => $purchase,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_backoffice_purchase_delete", methods={"POST"})
     */
    public function delete(Request $request, Purchase $purchase, PurchaseRepository $purchaseRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$purchase->getId(), $request->request->get('_token'))) {
            $purchaseRepository->remove($purchase, true);
        }

        return $this->redirectToRoute('app_backoffice_purchase_index', [], Response::HTTP_SEE_OTHER);
    }
}
