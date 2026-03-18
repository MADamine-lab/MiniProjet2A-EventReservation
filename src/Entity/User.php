<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(name: 'password_hash')]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verificationTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passkeyCredentialId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passkeyPublicKey = null;

    #[ORM\Column(nullable: true)]
    private ?int $passkeyCounter = 0;

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getUserIdentifier(): string { return (string) $this->username; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): static { $this->verificationToken = $token; return $this; }

    public function getVerificationTokenExpiresAt(): ?\DateTimeInterface { return $this->verificationTokenExpiresAt; }
    public function setVerificationTokenExpiresAt(?\DateTimeInterface $dt): static { $this->verificationTokenExpiresAt = $dt; return $this; }

    public function getPasskeyCredentialId(): ?string { return $this->passkeyCredentialId; }
    public function setPasskeyCredentialId(?string $id): static { $this->passkeyCredentialId = $id; return $this; }

    public function getPasskeyPublicKey(): ?string { return $this->passkeyPublicKey; }
    public function setPasskeyPublicKey(?string $key): static { $this->passkeyPublicKey = $key; return $this; }

    public function getPasskeyCounter(): ?int { return $this->passkeyCounter; }
    public function setPasskeyCounter(?int $counter): static { $this->passkeyCounter = $counter; return $this; }
}
