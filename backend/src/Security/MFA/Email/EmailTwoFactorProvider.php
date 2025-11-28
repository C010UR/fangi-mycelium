<?php

declare(strict_types=1);

namespace App\Security\MFA\Email;

use App\Model\MFA\EmailTwoFactorInterface;
use DateTimeImmutable;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EmailTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private CodeGenerator $codeGenerator,
        private TwoFactorFormRendererInterface $formRenderer,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        // Check if user can do email authentication
        $user = $context->getUser();

        return $user instanceof EmailTwoFactorInterface && $user->isEmailMFAEnabled();
    }

    public function prepareAuthentication(object $user): void
    {
        if (!($user instanceof EmailTwoFactorInterface)) {
            return;
        }

        $this->codeGenerator->generateAndSend($user);
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!($user instanceof EmailTwoFactorInterface)) {
            return false;
        }

        // Strip any user added spaces
        $authenticationCode = str_replace(' ', '', $authenticationCode);

        $mfa = $user->getEmailMfa();

        $isValid = $mfa
            && (!$mfa->getLastCodeExpiresAt() || $mfa->getLastCodeExpiresAt() > new DateTimeImmutable())
            && $mfa->getAuthCode() === $authenticationCode;

        return $isValid;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }
}
