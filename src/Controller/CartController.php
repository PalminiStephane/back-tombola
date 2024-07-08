<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * @Route("/cart", name="cart_index", methods={"GET"})
     */
    public function index(): Response
    {
        $cart = $this->cartService->getCart();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    /**
     * @Route("/cart/add/{drawId}", name="cart_add", methods={"POST"})
     */
    public function add(int $drawId, Request $request): Response
    {
        $quantity = $request->request->get('quantity', 1);

        $this->cartService->addToCart($drawId, $quantity);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/cart/remove/{drawId}", name="cart_remove", methods={"POST"})
     */
    public function remove(int $drawId): Response
    {
        $this->cartService->removeFromCart($drawId);

        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/cart/clear", name="cart_clear", methods={"POST"})
     */
    public function clear(): Response
    {
        $this->cartService->clearCart();

        return $this->redirectToRoute('cart_index');
    }
}
