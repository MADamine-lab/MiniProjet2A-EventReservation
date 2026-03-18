<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_events');
        }
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Handled by Symfony firewall.');
    }

    #[Route('/email-not-verified', name: 'app_email_not_verified')]
    public function emailNotVerified(): Response
    {
        return $this->render('security/email_not_verified.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        EmailVerificationService $emailService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_events');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(false);

            $token = $emailService->generateToken();
            $user->setVerificationToken($token);
            $user->setVerificationTokenExpiresAt(new \DateTime('+24 hours'));

            $em->persist($user);
            $em->flush();

            // Generate verification URL
            $verifyUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Try to send email
            $emailSent = false;
            try {
                $emailService->sendVerificationEmail($user);
                $emailSent = true;
            } catch (\Exception $e) {
                // Email failed - show link directly
            }

            if ($emailSent) {
                $this->addFlash('success',
                    '✅ Compte créé ! Email envoyé à <strong>' . htmlspecialchars($user->getEmail()) . '</strong>'
                );
            } else {
                // Show link directly in flash (dev mode)
                $this->addFlash('verify_link', $verifyUrl);
                $this->addFlash('info',
                    '✅ Compte créé ! Email non disponible en mode dev. Utilisez le lien ci-dessous.'
                );
            }

            return $this->redirectToRoute('app_verify_pending', ['email' => $user->getEmail(), 'url' => $verifyUrl]);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify-pending', name: 'app_verify_pending')]
    public function verifyPending(Request $request): Response
    {
        return $this->render('security/verify_pending.html.twig', [
            'email'      => $request->query->get('email'),
            'verifyUrl'  => $request->query->get('url'),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', '❌ Lien invalide ou déjà utilisé.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getVerificationTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', '⏰ Lien expiré. Demandez un nouveau lien.');
            return $this->redirectToRoute('app_resend_verification');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $em->flush();

        $this->addFlash('success', '🎉 Email vérifié ! Vous pouvez vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EntityManagerInterface $em,
        EmailVerificationService $emailService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user  = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && !$user->isVerified()) {
                $token = $emailService->generateToken();
                $user->setVerificationToken($token);
                $user->setVerificationTokenExpiresAt(new \DateTime('+24 hours'));
                $em->flush();

                $verifyUrl = $this->generateUrl(
                    'app_verify_email',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $emailService->sendVerificationEmail($user);
                    $this->addFlash('success', '📧 Email renvoyé !');
                } catch (\Exception $e) {
                    return $this->redirectToRoute('app_verify_pending', [
                        'email' => $user->getEmail(),
                        'url'   => $verifyUrl,
                    ]);
                }
            } else {
                $this->addFlash('info', 'Si cet email existe, un lien a été envoyé.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/resend_verification.html.twig');
    }

    #[Route('/admin/login', name: 'admin_login')]
    public function adminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() && $this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        return $this->render('admin/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function adminLogout(): void
    {
        throw new \LogicException('Handled by Symfony firewall.');
    }
}
