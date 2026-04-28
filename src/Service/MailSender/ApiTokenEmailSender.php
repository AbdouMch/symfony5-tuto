<?php

namespace App\Service\MailSender;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ApiTokenEmailSender
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(User $user, string $plainToken): void
    {
        $email = (new TemplatedEmail())
            ->from('fabien@example.com')
            ->to(new Address($user->getEmail()))
            ->subject('Your API token')
            ->htmlTemplate('emails/api_token.html.twig')
            ->context(['user' => $user, 'plain_token' => $plainToken]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Transport failure must not prevent the response — the token is still returned.
        }
    }
}