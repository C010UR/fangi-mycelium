<?php

declare(strict_types=1);

namespace App\Security\AccountRegistration;

use App\Entity\AccountRegistrationRequest;
use App\Entity\User;
use App\Model\UserActionToken\UserActionToken;
use App\Repository\AccountRegistrationRequestRepository;
use App\Security\AccountRegistration\Exception\AccountRegistrationAlreadyExistsException;
use App\Security\AccountRegistration\Exception\InvalidAccountRegistrationTokenException;
use App\Service\MailerService;
use App\Util\StringHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AccountRegistrationHandler
{
    public function __construct(
        private AccountRegistrationTokenGenerator $tokenGenerator,
        private EntityManagerInterface $entityManager,
        private AccountRegistrationRequestRepository $accountRegistrationRequestRepository,
        private MailerService $mailer,
        private int $selectorLength,
        private int $verifierLength,
    ) {
    }

    public function generateAccountRegistrationToken(User $user): UserActionToken
    {
        $createdAt = new DateTimeImmutable();

        $lastRequest = $this->accountRegistrationRequestRepository->findLastByUser($user);

        if (null !== $lastRequest) {
            throw new AccountRegistrationAlreadyExistsException();
        }

        $tokenComponents = $this->tokenGenerator->createToken($user->getUserIdentifier());

        $request = new AccountRegistrationRequest();
        $request
            ->setUser($user)
            ->setCreatedAt($createdAt)
            ->setSelector($tokenComponents->getSelector())
            ->setToken($tokenComponents->getHashedToken());

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return new UserActionToken(
            $tokenComponents->getPublicToken(),
            null,
            $createdAt,
        );
    }

    public function validateResetRequestAndFetchRequest(string $fullToken): AccountRegistrationRequest
    {
        if ($this->verifierLength + $this->selectorLength !== \strlen($fullToken)) {
            throw new InvalidAccountRegistrationTokenException();
        }

        $token = $this->findByToken($fullToken);

        if (!$token) {
            throw new InvalidAccountRegistrationTokenException();
        }

        $lastRequest = $this->accountRegistrationRequestRepository->findLastByUser($token->getUser());

        if ($lastRequest !== $token) {
            throw new InvalidAccountRegistrationTokenException();
        }

        $hashedVerifierToken = $this->tokenGenerator->createToken(
            $token->getUser()->getUserIdentifier(),
            substr($fullToken, $this->selectorLength),
        );

        if (false === hash_equals($token->getToken(), $hashedVerifierToken->getHashedToken())) {
            throw new InvalidAccountRegistrationTokenException();
        }

        return $token;
    }

    public function registerUser(AccountRegistrationRequest $request): void
    {
        $user = $request->getUser();
        $user->setIsActivated(true);

        $this->entityManager->remove($request);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function findByToken(string $token): ?AccountRegistrationRequest
    {
        $selector = substr($token, 0, $this->selectorLength);

        return $this->accountRegistrationRequestRepository->findOneBySelector($selector);
    }

    public function prepareAccountRegistration(User $user): void
    {
        $token = $this->generateAccountRegistrationToken($user);

        $this->mailer->sendAccountRegistrationEmail(
            $token,
            $user,
        );
    }
}
