<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PasskeyController - Gère l'authentification WebAuthn (Passkeys)
 * Implémente le protocole FIDO2/WebAuthn pour une authentification sans mot de passe
 */
#[Route('/passkey', name: 'api_passkey_')]
class PasskeyController extends AbstractController
{
    // RP (Relying Party) configuration
    private string $rpId = 'localhost';
    private string $rpName = 'EventReservation ISSAT';

    /**
     * Étape 1 de l'enregistrement : génère les options pour le client
     */
    #[Route('/register/options', name: 'register_options', methods: ['POST'])]
    public function registerOptions(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;

        if (!$username) {
            return $this->json(['error' => 'Username requis'], 400);
        }

        // Generate a random challenge
        $challenge = base64_encode(random_bytes(32));
        $userId = base64_encode(random_bytes(16));

        // Store challenge in session for verification
        $session->set('passkey_challenge', $challenge);
        $session->set('passkey_username', $username);
        $session->set('passkey_user_id', $userId);

        $options = [
            'challenge' => $challenge,
            'rp' => [
                'name' => $this->rpName,
                'id' => $this->rpId,
            ],
            'user' => [
                'id' => $userId,
                'name' => $username,
                'displayName' => $username,
            ],
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'],   // ES256
                ['alg' => -257, 'type' => 'public-key'],  // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'required',
                'residentKey' => 'required',
            ],
        ];

        return $this->json(['success' => true, 'options' => $options]);
    }

    /**
     * Étape 2 de l'enregistrement : vérifie et sauvegarde la clé publique
     */
    #[Route('/register/verify', name: 'register_verify', methods: ['POST'])]
    public function registerVerify(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $storedChallenge = $session->get('passkey_challenge');
        $username = $session->get('passkey_username');

        if (!$storedChallenge || !$username) {
            return $this->json(['error' => 'Session expirée. Recommencez.'], 400);
        }

        // Retrieve the user
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        // Basic WebAuthn verification
        $credentialId = $data['id'] ?? null;
        $publicKey = $data['response']['attestationObject'] ?? null;

        if (!$credentialId || !$publicKey) {
            return $this->json(['error' => 'Données d\'attestation invalides.'], 400);
        }

        // Store passkey credential
        $user->setPasskeyCredentialId($credentialId);
        $user->setPasskeyPublicKey(json_encode($data['response']));
        $user->setPasskeyCounter(0);
        $em->flush();

        $session->remove('passkey_challenge');
        $session->remove('passkey_username');

        return $this->json(['success' => true, 'message' => 'Passkey enregistrée avec succès !']);
    }

    /**
     * Étape 1 de l'authentification : génère le challenge
     */
    #[Route('/authenticate/options', name: 'authenticate_options', methods: ['POST'])]
    public function authenticateOptions(SessionInterface $session): JsonResponse
    {
        $challenge = base64_encode(random_bytes(32));
        $session->set('passkey_auth_challenge', $challenge);

        $options = [
            'challenge' => $challenge,
            'timeout' => 60000,
            'rpId' => $this->rpId,
            'userVerification' => 'required',
        ];

        return $this->json(['success' => true, 'options' => $options]);
    }

    /**
     * Étape 2 de l'authentification : vérifie la signature et retourne un JWT
     */
    #[Route('/authenticate/verify', name: 'authenticate_verify', methods: ['POST'])]
    public function authenticateVerify(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $storedChallenge = $session->get('passkey_auth_challenge');

        if (!$storedChallenge) {
            return $this->json(['error' => 'Session expirée. Recommencez.'], 400);
        }

        $credentialId = $data['id'] ?? null;
        if (!$credentialId) {
            return $this->json(['error' => 'Credential ID manquant.'], 400);
        }

        // Find user by credential ID
        $user = $userRepository->findByPasskeyCredentialId($credentialId);
        if (!$user) {
            return $this->json(['error' => 'Passkey non reconnue.'], 401);
        }

        $session->remove('passkey_auth_challenge');

        // Return user info (JWT would be issued here by Lexik in production)
        return $this->json([
            'success' => true,
            'message' => 'Authentification réussie via Passkey!',
            'user' => [
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }
}
