<?php

namespace App\EventListener;

use App\Form\Exception\Api\FormValidationException;
use App\Model\Api\Error;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionEventListener
{
    private const JSON_RESPONSE = 'json';

    private ExceptionEvent $event;
    private UrlGeneratorInterface $urlGenerator;
    private SerializerInterface $serializer;
    private string $environment;
    private TranslatorInterface $translator;

    public function __construct(
        string              $environment, UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer,
        TranslatorInterface $translator
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
        $this->environment = $environment;
        $this->translator = $translator;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->event = $event;
        $exception = $this->event->getThrowable();
        $message = $exception->getMessage();
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        switch (true) {
            case $exception instanceof ForeignKeyConstraintViolationException:
                $message = "Can't delete a resource : The Resource has a relationship";
                $statusCode = Response::HTTP_BAD_REQUEST;
                break;
            case $exception instanceof HttpException:
                $statusCode = $exception->getStatusCode();
                break;
            case $exception instanceof FormValidationException:
                $this->event->setResponse($exception->getResponse());
                return;
            default:
        }

        $this->setResponse($message, $statusCode);
    }

    private function setResponse(string $message, int $statusCode): void
    {

        if ('dev' !== $this->environment && $statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = $this->translator->trans('technical_error.message', [], 'exception');
        }

        $request = $this->event->getRequest();

        $uri = $request->getPathInfo();
        $responseType = null;
        $headers = [];

        if (preg_match("/^\/(v\d*\/)?api\/.+/", $uri)) {
            $responseType = self::JSON_RESPONSE;
        }

        if (self::JSON_RESPONSE === $responseType) {
            $error = $this->serializer->serialize(
                new Error($message, $statusCode),
                'json',
                [
                    AbstractNormalizer::GROUPS => ['api:error'],
                ]
            );
            $response = new JsonResponse($error, $statusCode, $headers, true);
            $this->event->setResponse($response);

            return;
        }

        if ('dev' !== $this->environment) {
            $session = $this->event->getRequest()->getSession();
            $session->getFlashBag()->add('error', $message);
            $this->event->setResponse(
                new RedirectResponse($this->urlGenerator->generate('app_homepage'))
            );
        }
    }
}