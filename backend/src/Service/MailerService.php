<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Server;
use App\Entity\User;
use App\Model\MFA\EmailTwoFactorInterface;
use App\Model\UserActionToken\UserActionToken;
use App\Util\StringHelper;
use SensitiveParameter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        #[Autowire(param: 'mycelium.emails')]
        private array $templates,
    ) {}

    public function sendEmailMfa(EmailTwoFactorInterface $user): void
    {
        $mfa = $user->getEmailMFA();
        $template = $this->templates['mfa'];

        $email = new TemplatedEmail()
            ->to($mfa->getRecipient())
            ->subject($this->translator->trans($template['subject'], domain: 'emails'))
            ->htmlTemplate($template['template'])
            ->context([
                'name' => $user->getName(),
                'code' => $mfa->getAuthCode(),
                'sent_at' => $mfa->getLastCodeSentAt(),
                'expires_in' =>  $mfa->getLastCodeSentAt()->diff($mfa->getLastCodeExpiresAt()),
                'expires_at' => $mfa->getLastCodeExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(
        #[SensitiveParameter]
        UserActionToken $token,
        User $user,
    ): void {
        $template = $this->templates['password_reset'];

        $email = new TemplatedEmail()
            ->to($user->getEmail())
            ->subject($this->translator->trans($template['subject'], domain: 'emails'))
            ->htmlTemplate($template['template'])
            ->context([
                'name' => $user->getName(),
                'url' => StringHelper::replace($template['url_template'], ['token' => $token->getToken()]),
                'sent_at' => $token->getGeneratedAt(),
                'expires_in' => $token->getExpiresIn(),
                'expires_at' => $token->getExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountActivationEmail(
        #[SensitiveParameter]
        UserActionToken $token,
        User $user,
    ): void {
        $template = $this->templates['account_activation'];

        $email = new TemplatedEmail()
            ->to($user->getEmail())
            ->subject($this->translator->trans($template['subject'], domain: 'emails'))
            ->htmlTemplate($template['template'])
            ->context([
                'name' => $user->getName(),
                'created_by_name' => $user->getCreatedByFormattedName(),
                'url' => StringHelper::replace($template['url_template'], ['token' => $token->getToken()]),
                'sent_at' => $token->getGeneratedAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountRegistrationEmail(
        #[SensitiveParameter]
        UserActionToken $token,
        User $user,
    ): void {
        $template = $this->templates['account_registration'];

        $email = new TemplatedEmail()
            ->to($user->getEmail())
            ->subject($this->translator->trans($template['subject'], domain: 'emails'))
            ->htmlTemplate($template['template'])
            ->context([
                'name' => $user->getName(),
                'url' => StringHelper::replace($template['url_template'], ['token' => $token->getToken()]),
                'sent_at' => $token->getGeneratedAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendServerSetupEmail(
        Server $server,
    ): void {
        $template = $this->templates['server_setup'];

        $email = new TemplatedEmail()
            ->to($server->getCreatedBy()->getEmail())
            ->subject($this->translator->trans($template['subject'], domain: 'emails'))
            ->htmlTemplate($template['template'])
            ->context([
                'name' => $server->getCreatedBy()->getName(),
                'client_id' => $server->getClientId(),
                'secret' => $server->getSecret(),
            ]);

        $this->mailer->send($email);
    }
}
