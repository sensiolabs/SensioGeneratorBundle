<?php

namespace {{ namespace }}\Form{{ entity_namespace ? '\\' ~ entity_namespace : '' }};

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class {{ form_class }} extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        {%- for field in fields %}

            ->add('{{ field }}')

        {%- endfor %}

        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => '{{ namespace }}\Entity{{ entity_namespace ? '\\' ~ entity_namespace : '' }}\{{ entity_class }}'
        ));
    }

    public function getName()
    {
        return '{{ form_type_name }}';
    }
}
