<?php

namespace {{ namespace }}\Controller{{ entity_namespace ? '\\' ~ entity_namespace : '' }};

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
{% if 'annotation' == format -%}
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
{%- endif %}

use {{ namespace }}\Entity\{{ entity }};
use {{ namespace }}\Form\{{ entity }}Type;

/**
 * {{ entity }} controller.
 *
{% if 'annotation' == format %}
 * @Route("/{{ route_prefix }}")
{% endif %}
 */
class {{ entity_class }}Controller extends Controller
{

    {%- if 'index' in actions %}
        {%- include dir ~ '/actions/index.php' %}
    {%- endif %}

    {%- if 'show' in actions %}
        {%- include dir ~ '/actions/show.php' %}
    {%- endif %}

    {%- if 'new' in actions %}
        {%- include dir ~ '/actions/new.php' %}
        {%- include dir ~ '/actions/create.php' %}
    {%- endif %}

    {%- if 'edit' in actions %}
        {%- include dir ~ '/actions/edit.php' %}
        {%- include dir ~ '/actions/update.php' %}
    {%- endif %}

    {%- if 'delete' in actions %}
        {%- include dir ~ '/actions/delete.php' %}
    {%- endif %}

}
