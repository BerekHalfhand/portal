<?php

namespace Treto\PortalBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DiscussionControllerTest extends WebTestCase
{
    public function testList()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/discussion/list');
    }

    public function testItem()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/discussion/item');
    }

}
