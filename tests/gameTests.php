<?php

namespace App\Tests;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameTests extends WebTestCase
{

    public function testResetGame()
    {
        $client = static::createClient();
        $client->request('GET', '/clearBds');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }


    public function testMove()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/movePiece',
            ['id_piece' => 1],
            [],
            []
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }


    public function testFirstWhiteMove()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/movePiece',
            ['id_piece' => 1],
            [],
            []
        );

        $resultJson = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(true, $resultJson['validMove']);
    }


    public function testStart()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/',
            [],
            [],
            []
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }


}