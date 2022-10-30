<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUserValidator extends ConstraintValidator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param string     $value      the email address of the user
     * @param UniqueUser $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->userRepository->findOneBy(['email' => $value]);

        if (null !== $user) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
