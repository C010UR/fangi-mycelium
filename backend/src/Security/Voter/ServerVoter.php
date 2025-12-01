<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ServerVoter extends Voter
{
    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public const string VIEW = 'view_server';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Server && \in_array($attribute, [self::VIEW], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->getServers()->contains($subject)) {
            return false;
        }

        if (
            !$this->accessDecisionManager->decide($token, [UserRole::ADMIN])
            && (!$subject->isActive() || $subject->isBanned())
        ) {
            return false;
        }

        return true;
    }
}
