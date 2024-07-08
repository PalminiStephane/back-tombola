<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Page de réinitialisation du mot de passe
     * @Route("/forgot-password", name="security_forgot_password", methods={"GET", "POST"})
     */
    public function forgotPassword(Request $request): Response
    {
        // Logique pour gérer la réinitialisation du mot de passe
        return $this->render('security/forgot_password.html.twig');
    }

    /**
     * Réinitialisation du mot de passe avec token
     * @Route("/reset-password/{token}", name="security_reset_password", methods={"GET", "POST"})
     */
    public function resetPassword(string $token, Request $request): Response
    {
        // Logique pour gérer la réinitialisation du mot de passe avec token
        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
