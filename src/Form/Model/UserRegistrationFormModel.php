<?php

namespace App\Form\Model;

use App\Validator\UniqueUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * a DTO for user registration.
 */
class UserRegistrationFormModel
{
    /**
     * @Assert\NotBlank(message="registration.email.empty")
     * @Assert\Email()
     * @UniqueUser(message="registration.user.unique")
     */
    public string $email;
    /**
     * @Assert\NotBlank(message="registration.firstName.empty")
     */
    public string $firstName;
    /**
     * @Assert\NotBlank(message="registration.password.empty")
     * @Assert\Length(min=10, minMessage="registration.password.min_length", max=4096)
     */
    public string $plainPassword;
    /**
     * @Assert\IsTrue(message="registration.agree_terms.not_checked")
     */
    public bool $agreeTerms;
}
