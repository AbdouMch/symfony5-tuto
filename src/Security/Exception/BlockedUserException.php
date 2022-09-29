<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BlockedUserException extends AuthenticationException
{
    private $messageKey = 'Your account is blocked for some reasons. Please contact the site admin';

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }
}
