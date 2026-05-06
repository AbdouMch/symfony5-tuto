<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Entity\Spell;
use App\Factory\SpellFactory;
use App\Tests\Functional\AbstractWebTestCase;

class SpellControllerTest extends AbstractWebTestCase
{
    private const ENDPOINT = '/api/v1/spells';

    public function testListRequiresAuthentication(): void
    {
        $this->client->request('GET', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testListReturnsJsonWithSpells(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $spellCout = $em->getRepository(Spell::class)->count([]);
        SpellFactory::createMany(3);

        $this->client->request('GET', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer '.$this->createBearerToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $this->assertCount($spellCout + 3, $body['result']);
    }

    public function testListReturnsJsonWithSpellsPaginated(): void
    {
        SpellFactory::createMany(6);

        $this->client->request('GET', self::ENDPOINT, [
            'limit' => 3,
            'page' => 2,
        ], [], [
            self::AUTH_HEADER => 'Bearer '.$this->createBearerToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $this->assertCount(3, $body['result']);
        $this->assertSame(3, $body['limit']);
        $this->assertSame(2, $body['page']);
    }

    public function testCreateSpell(): void
    {
        $this->client->request('POST', self::ENDPOINT, [
            'name' => 'new  spell',
            'constantCode' => 'new.spell',
        ], [], [
            self::AUTH_HEADER => 'Bearer '.$this->createBearerToken(),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $spell = $body['result'];
        $this->assertSame('new  spell', $spell['name']);
        $this->assertSame('new.spell', $spell['constant_code']);
    }
}
