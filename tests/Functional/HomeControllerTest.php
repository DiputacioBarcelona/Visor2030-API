<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();

        // Tests that frontpage works and outputs expected html.
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('DiBa Starterkit | Diputació de Barcelona');

        // Tests that login works and redirects to backoffice.
        $crawler = $client->request('GET', '/login/in-memory');
        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('main form')->first()->form();
        $form->setValues([
            '_username' => 'admin',
            '_password' => 'admin',
        ]);
        $client->submit($form);
        $this->assertResponseIsSuccessful();
    }
}
