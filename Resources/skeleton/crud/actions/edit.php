
    /**
     * Displays a form to edit an existing {{ entity }} entity.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/edit", name="{{ route_prefix }}_edit")
     * @Template()
{% endif %}
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find {{ entity }} entity.');
        }

        $form = $this->createForm(new {{ entity_class }}Type(), $entity);

{% if 'annotation' == format %}
        return array(
            'entity' => $entity,
            'form'   => $form->createView()
        );
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:edit.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
{% endif %}
    }
