<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('{{ extension_alias }}_homepage', new Route('/hello/{name}', array(
    '_controller' => '{{ bundle }}:Default:index',
)));

return $collection;
