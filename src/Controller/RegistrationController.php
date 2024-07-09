<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\TombolaAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationController extends AbstractController
{
    private $verifyEmailHelper;
    private $mailer;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
    }
    /**
     * Page d'inscription
     * @Route("/register", name="app_register", methods={"GET", "POST"})
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, UserAuthenticatorInterface $userAuthenticator, TombolaAuthenticator $authentificate, EntityManagerInterface $entityManager): Response
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
                    ->subject('Please Confirm your Email')
                    ->html('<p>Please confirm your email by clicking the following link: <a href="' . $verificationLink . '">Verify Email</a></p>');

                $this->mailer->send($email);

                // Connexion automatique après inscription
                return $userAuthenticator->authenticateUser(
                    $user,
                    $authentificate,
                    $request
                );
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

            $this->addFlash('success', 'Your email has been verified. You can now login.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'The verification link is invalid or expired.');

        return $this->redirectToRoute('app_register');
    }
}
