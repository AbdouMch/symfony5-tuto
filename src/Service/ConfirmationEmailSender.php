<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ConfirmationEmailSender
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendConfirmationEmail(User $user, string $confirmationLink): void
    {
        $email = (new TemplatedEmail())
            ->from('fabien@example.com')
            ->to(new Address($user->getEmail()))
            ->subject('Thanks for signing up!')
            ->htmlTemplate('emails/registration_confirmation.html.twig')
            ->context(['confirmation_link' => $confirmationLink, 'user' => $user]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
        }
    }
}
