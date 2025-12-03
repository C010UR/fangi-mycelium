<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const string VIEW = 'view_user';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof User && \in_array($attribute, [self::VIEW], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }


        $subjectServers = $subject->getServers();

        foreach ($user->getServers() as $server) {
            if ($subjectServers->contains($server)) {
                return true;
            }
        }

        return false;
    }
}
