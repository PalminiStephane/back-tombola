<?php

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

                // Après avoir enregistré l'utilisateur et envoyé l'e-mail de vérification
                $this->addFlash('info', 'Votre compte a été créé avec succès. Veuillez vérifier votre boîte e-mail pour valider votre inscription.');

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
        try {
            // Generate the email verification link with a signature
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email', // The route name for email verification
                $user->getId(), // User ID
                $user->getEmail(), // User's email
                ['id' => $user->getId()] // Only ID is needed now
            );

            // Get the signed URL
            $verificationLink = $signatureComponents->getSignedUrl();

            // Create the email
            $email = (new Email())
                ->from('palministephane@gmail.com') // Use a verified sender email address
                ->to($user->getEmail()) // The user's email
                ->subject('Vérification de votre adresse email') // Email subject
                ->html($this->renderView(
                    'emails/verification.html.twig', // Twig template for the email body
                    ['verification_link' => $verificationLink] // Pass the verification link to the template
                ));

            // Send the email
            $this->mailer->send($email);

            // Add a flash message to inform the user that the email has been sent
            $this->addFlash('success', 'A verification email has been sent to you.');
        } catch (\Exception $e) {
            // Log the error if sending the email fails
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());

            // Add a flash message to inform the user of the failure
            $this->addFlash('error', 'Failed to send the verification email.');

            // Throw an exception to ensure the error is handled properly
            throw new \RuntimeException('Failed to send verification email.');
        }
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_register');
        }

        // Marquer l'email comme vérifié
        $user->setIsEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been successfully verified.');

        return $this->redirectToRoute('app_login');
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
