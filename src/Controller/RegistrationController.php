<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationController extends AbstractController
{
    private $verifyEmailHelper;
    private $mailer;
    private $logger;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Page d'inscription
     * @Route("/register", name="app_register", methods={"GET", "POST"})
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encodage du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Génération du token de vérification de l'email
            $token = bin2hex(random_bytes(32));
            $user->setEmailVerificationToken($token);

            // Enregistrement de la date d'inscription
            $user->setRegistrationDate(new \DateTime());

            // Rôle par défaut
            $user->setRoles(['ROLE_USER']);

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Génération du lien de vérification de l'email
                $signatureComponents = $this->verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    $user->getId(),
                    $user->getEmail(),
                    ['token' => $token]
                );

                $verificationLink = $signatureComponents->getSignedUrl();

                // Envoi de l'email de vérification
                $email = (new Email())
                    ->from('noreply@yourdomain.com')
                    ->to($user->getEmail())
                    ->subject('Vérification de votre adresse email')
                    ->html('<p>S\'il vous plaît cliquez sur le lien suivant pour vérifier votre adresse email: <a href="' . $verificationLink . '">Vérifier mon email</a></p>');

                $this->mailer->send($email);

                $this->addFlash('success', 'Votre compte a été créé avec succès. Un email de vérification vous a été envoyé.');

                return $this->redirectToRoute('app_login');
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription');
            }
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->get('id');
        $token = $request->get('token');

        if (null === $id || null === $token) {
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // Verify the token
        if ($user->getEmailVerificationToken() === $token) {
            $user->setIsEmailVerified(true);
            $user->setEmailVerificationToken(null);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la vérification de votre adresse email.');

        return $this->redirectToRoute('app_register');
    }
    /**
     * @Route("/test-email", name="test_email")
     */
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('palministephane@gmail.com')
            ->to('lofax59792@cartep.com')  // adresse e-mail de test
            ->subject('Test Email')
            ->html('<p>This is a test email.</p>');

            try {
                $this->logger->info('Attempting to send email...');
                $mailer->send($email);
                $this->logger->info('Email sent successfully.');
                return new Response('Email sent!');
            } catch (\Exception $e) {
                $this->logger->error('Email not sent: '.$e->getMessage());
                return new Response('Email not sent: '.$e->getMessage());
            }
        }
 }