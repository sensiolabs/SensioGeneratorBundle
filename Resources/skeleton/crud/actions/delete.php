
    /**
     * Deletes a {{ entity }} entity.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/delete", name="{{ route_prefix }}_delete")
{% endif %}
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find {{ entity }} entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('{{ route_prefix }}'));
    }