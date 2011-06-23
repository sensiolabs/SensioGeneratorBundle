
    /**
     * Finds and displays a {{ entity }} entity.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/show", name="{{ route_prefix }}_show")
     * @Template()
{% endif %}
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find {{ entity }} entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

{% if 'annotation' == format %}
        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
{% endif %}
    }
