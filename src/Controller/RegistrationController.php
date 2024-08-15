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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoding the password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Generating email verification token
            $token = bin2hex(random_bytes(32));
            $user->setEmailVerificationToken($token);

            // Setting the registration date
            $user->setRegistrationDate(new \DateTime());

            // Setting default role
            $user->setRoles(['ROLE_USER']);

            // Validate user data before persisting
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_register');
            }

            // Registering the new user
            try {
                $entityManager->beginTransaction();
                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->commit();

                // Sending verification email
                $this->sendVerificationEmail($user);

                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->logger->error('Error during registration or email sending: ' . $e->getMessage());
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred during registration. Please try again.');
            }
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Sends the verification email to the user.
     */
    private function sendVerificationEmail(User $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['token' => $user->getEmailVerificationToken()]
        );

        $verificationLink = $signatureComponents->getSignedUrl();

        // Vérification de l'URL générée
        dump($verificationLink); // Affiche l'URL dans le profiler Symfony

        $email = (new Email())
            ->from('palministephane@gmail.com') // à changer par 'noreply@yourdomain.com' lors de la mise en prod etds le mailer.yaml
            ->to($user->getEmail())
            ->subject('Vérification de votre adresse email')
            ->html($this->renderView(
                'emails/verification.html.twig',
                ['verification_link' => $verificationLink]
            ));

        try {
            $this->mailer->send($email);
            $this->addFlash('success', 'A verification email has been sent to you.');
        } catch (\Exception $e) {
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());
            $this->addFlash('error', 'Failed to send the verification email.');
            throw new \RuntimeException('Failed to send verification email.');
        }
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = $request->get('token');
        $signature = $request->get('signature');
        $expires = $request->get('expires');

        if (null === $token || null === $signature || null === $expires) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_register');
        }

        // Récupérer l'utilisateur via le token
        $user = $entityManager->getRepository(User::class)->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_register');
        }

        try {
            // Vérification de la validité du lien
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
    
            // Vérification de la date d'expiration du lien
            if (time() > $expires) {
                $this->addFlash('error', 'Verification link has expired.');
                return $this->redirectToRoute('app_register');
            }
    
            // Marquer l'email comme vérifié
            $user->setIsEmailVerified(true);
            $user->setEmailVerificationToken(null);
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            $this->addFlash('success', 'Your email has been successfully verified.');
    
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid or expired verification link.');
            return $this->redirectToRoute('app_register');
        }
    }

     /**
     * @Route("/test-send-email", name="test_send_email")
     */
    public function testSendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('palministephane@gmail.com')  // Assurez-vous que cette adresse est vérifiée dans votre compte SES
            ->to('stefax@live.fr')   // Adresse de test
            ->subject('Test Email from Symfony via SES')
            ->html('<p>This is a test email to confirm SES integration.</p>');

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Email sent successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_register');  // Redirigez vers la page que vous voulez après le test
    }

    /**
     * @Route("/email-not-verified", name="app_email_not_verified")
     */
    public function emailNotVerified(): Response
    {
        return $this->render('security/email_not_verified.html.twig');
    }
}