<?php

namespace {{ namespace }}\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class {{ controller }}ControllerTest extends WebTestCase
{
{% for action in actions %}
    public function test{{ action.basename|capitalize }}()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '{{ action.route }}');
    }

{% endfor -%}
}
