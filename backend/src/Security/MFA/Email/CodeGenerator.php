<?php

declare(strict_types=1);

namespace App\Security\MFA\Email;

use App\Model\MFA\EmailTwoFactorInterface;
use App\Service\MailerService;
use DateInterval;
use DateTimeImmutable;
use Scheb\TwoFactorBundle\Model\PersisterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CodeGenerator
{
    public function __construct(
        private PersisterInterface $persister,
        private MailerService $mailer,
        private int $digits,
        private string $expirationTime,
    ) {}

    public function generateAndSend(EmailTwoFactorInterface $user): void
    {
        $min = 10 ** ($this->digits - 1);
        $max = (10 ** $this->digits) - 1;
        $code = $this->generateCode($min, $max);
        $createdAt = new DateTimeImmutable();

        $mfa = $user->getEmailMfa();
        $mfa
            ->setAuthCode((string)$code)
            ->setLastCodeSentAt($createdAt)
            ->setLastCodeExpiresAt((clone $createdAt)->add(new DateInterval($this->expirationTime)));

        $this->persister->persist($mfa);

        $mfa = $user->getEmailMFA();

        $this->mailer->sendEmailMfa($user);
    }

    public function reSend(EmailTwoFactorInterface $user): void
    {
        $this->mailer->sendEmailMfa($user);
    }

    protected function generateCode(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
