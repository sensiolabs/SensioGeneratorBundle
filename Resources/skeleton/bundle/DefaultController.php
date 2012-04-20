<?php

namespace {{ namespace }}\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
{% if 'annotation' == format -%}
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
{% endif %}

class DefaultController extends Controller
{
    {% if 'annotation' == format -%}
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    {% endif -%}
    public function indexAction($name)
    {
        {% if 'annotation' != format -%}
        return $this->render('{{ bundle }}:Default:index.html.twig', array('name' => $name));
        {%- else -%}
        return array('name' => $name);
        {%- endif %}

    }
}
