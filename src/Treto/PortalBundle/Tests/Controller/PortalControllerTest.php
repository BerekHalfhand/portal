<?php

namespace Treto\PortalBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortalControllerTest extends WebTestCase
{
    public function testGet_themes()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/get_themes');
    }

    public function testGet_tasks()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/get_tasks');
    }

}
