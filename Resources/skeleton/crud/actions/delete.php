
    /**
     * Deletes a {{ entity }} entity.
     *
{% if 'annotation' == format %}
     * @Route("/{id}", name="{{ route_name_prefix }}_delete")
     * @Method("DELETE")
{% endif %}
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('{{ bundle }}:{{ entity }}')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find {{ entity }} entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('{{ route_name_prefix }}'));
    }

    /**
     * Creates a form to delete a {{ entity }} entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }