<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Tests\Functional\AbstractWebTestCase;

class QuestionControllerTest extends AbstractWebTestCase
{
    private const ENDPOINT = '/api/v1/questions';

    public function testCreateRequiresAuthentication(): void
    {
        $this->client->request('POST', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateWithValidDataReturns201(): void
    {
        $bearerToken = $this->createBearerToken();

        $this->client->request('POST', self::ENDPOINT, [
            'name' => 'How do I cast a fireball spell?',
            'question' => 'I have been trying to learn the fireball spell but nothing works.',
        ], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $this->assertSame('How do I cast a fireball spell?', $body['result']['name']);
    }

    public function testCreateWithMissingFieldsReturns400(): void
    {
        $bearerToken = $this->createBearerToken();

        $this->client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseFormatSame('json');
    }
}
