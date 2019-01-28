<?php

namespace Treto\PortalBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testGroups()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/contact/groups');
    }

    public function testContacts()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/contact/contacts');
    }

    public function testContact()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/contact/contact');
    }

}
