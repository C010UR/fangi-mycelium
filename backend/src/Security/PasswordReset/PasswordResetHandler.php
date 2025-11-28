<?php

declare(strict_types=1);

namespace App\Security\PasswordReset;

use App\Entity\PasswordResetRequest;
use App\Entity\Server;
use App\Entity\User;
use App\Model\UserActionToken\UserActionToken;
use App\Repository\PasswordResetRequestRepository;
use App\Security\PasswordReset\Exception\ExpiredPasswordResetTokenException;
use App\Security\PasswordReset\Exception\InvalidPasswordResetTokenException;
use App\Service\MailerService;
use App\Util\StringHelper;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SensitiveParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetHandler
{
    public function __construct(
        private PasswordResetTokenGenerator $tokenGenerator,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordResetRequestRepository $passwordResetRequestRepository,
        private MailerService $mailer,
        private int $selectorLength,
        private int $verifierLength,
        private string $expirationTime,
    ) {
    }

    public function generateResetToken(User $user, bool $canExpire = true): UserActionToken
    {
        $createdAt = new DateTimeImmutable();

        if ($canExpire) {
            $expiresAt = (clone $createdAt)->add(new DateInterval($this->expirationTime));
        } else {
            $expiresAt = null;
        }

        $tokenComponents = $this->tokenGenerator->createToken($user->getUserIdentifier(), $expiresAt);

        $request = new PasswordResetRequest();
        $request
            ->setUser($user)
            ->setCreatedAt($createdAt)
            ->setExpiresAt($expiresAt)
            ->setSelector($tokenComponents->getSelector())
            ->setToken($tokenComponents->getHashedToken());

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return new UserActionToken(
            $tokenComponents->getPublicToken(),
            $expiresAt,
            $createdAt,
        );
    }

    public function validateResetRequestAndFetchRequest(string $fullToken): PasswordResetRequest
    {
        $this->passwordResetRequestRepository->purgeExpiredRequests();

        if ($this->verifierLength + $this->selectorLength !== \strlen($fullToken)) {
            throw new InvalidPasswordResetTokenException();
        }

        $token = $this->findByToken($fullToken);

        if (!$token) {
            throw new InvalidPasswordResetTokenException();
        }

        $lastRequest = $this->passwordResetRequestRepository->findLastByUser($token->getUser());

        if ($lastRequest !== $token) {
            throw new InvalidPasswordResetTokenException();
        }

        $now = new DateTimeImmutable();

        if ($token->getExpiresAt() && $now >= $token->getExpiresAt()) {
            throw new ExpiredPasswordResetTokenException();
        }

        $hashedVerifierToken = $this->tokenGenerator->createToken(
            $token->getUser()->getUserIdentifier(),
            $token->getExpiresAt(),
            substr($fullToken, $this->selectorLength),
        );

        if (false === hash_equals($token->getToken(), $hashedVerifierToken->getHashedToken())) {
            throw new InvalidPasswordResetTokenException();
        }

        return $token;
    }

    public function resetPassword(PasswordResetRequest $request, #[SensitiveParameter] string $password): void
    {
        $user = $request->getUser();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setIsActivated(true);
        $user->setPassword($hashedPassword);

        $this->entityManager->remove($request);
        $this->entityManager->persist($user);

        $this->entityManager->flush();
    }

    private function findByToken(string $token): ?PasswordResetRequest
    {
        $selector = substr($token, 0, $this->selectorLength);

        return $this->passwordResetRequestRepository->findOneBySelector($selector);
    }

    public function preparePasswordReset(User $user): void
    {
        $token = $this->generateResetToken($user);

        $this->mailer->sendPasswordResetEmail(
            $token,
            $user,
        );
    }

    public function prepareAccountActivation(User $user): void
    {
        $user->setPassword('');
        $this->entityManager->persist($user);

        $token = $this->generateResetToken($user, canExpire: false);

        $this->mailer->sendAccountActivationEmail(
            $token,
            $user,
        );
    }
}
