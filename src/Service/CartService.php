<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function addToCart(int $drawId, int $quantity): void
    {
        $cart = $this->session->get('cart', []);

        if (!isset($cart[$drawId])) {
            $cart[$drawId] = 0;
        }

        $cart[$drawId] += $quantity;
        $this->session->set('cart', $cart);
    }

    public function removeFromCart(int $drawId): void
    {
        $cart = $this->session->get('cart', []);

        if (isset($cart[$drawId])) {
            unset($cart[$drawId]);
        }

        $this->session->set('cart', $cart);
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function clearCart(): void
    {
        $this->session->remove('cart');
    }
}
