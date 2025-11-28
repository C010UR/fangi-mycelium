<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ServerVoter extends Voter
{
    public const string VIEW = 'view_server';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Server && in_array($attribute, [self::VIEW], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->getServers()->contains($subject);
    }
}
