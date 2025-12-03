<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Module;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ModuleVoter extends Voter
{
    public const string VIEW = 'view_module';
    public const string UPDATE = 'update_module';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Module && \in_array($attribute, [self::VIEW, self::UPDATE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (self::VIEW === $attribute) {
            return
                $subject->getCreatedBy() === $user
                || ($subject->isActive() && !$subject->isBanned());
        } elseif (self::UPDATE === $attribute) {
            return $subject->getCreatedBy() === $user;
        }

        return false;
    }
}
