
    /**
     * Edits an existing {{ entity }} entity.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/update", name="{{ route_prefix }}_update")
     * @Template("{{ bundle }}:{{ entity }}:edit.html.twig")
{% endif %}
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find {{ entity }} entity.');
        }

        $form = $this->createForm(new {{ entity_class }}Type(), $entity);

        $request = $this->getRequest();

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($entity);
                $em->flush();

                return $this->redirect($this->generateUrl('{{ route_prefix }}_edit', array('id' => $id)));
            }
        }

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
