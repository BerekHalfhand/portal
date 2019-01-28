<?php

namespace Treto\PortalBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    public function testTasks()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/task/tasks');
    }

    public function testAdd()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/task/add');
    }

}
