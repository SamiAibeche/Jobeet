<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;

/**
 * Job controller.
 *
 * @Route("/ens_job")
 */
class JobController extends Controller
{
    /**
     * Lists all Job entities.
     *
     * @Route("/", name="ens_job_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $categories = $em->getRepository('EnsJobeetBundle:Category')->getWithJobs();

        foreach($categories as $category)
        {
            $category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')->getActiveJobs($category->getId(), $this->container->getParameter('max_jobs_on_homepage')));
            $category->setMoreJobs($em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId()) - $this->container->getParameter('max_jobs_on_homepage'));
        }

        $format = $request->getRequestFormat();


        return $this->render('job/index.' . $format . '.twig', array(
            'categories' => $categories,
            'lastUpdated' => $em->getRepository('EnsJobeetBundle:Job')->getLatestPost()->getCreatedAt()->format(DATE_ATOM),
            'feedId' => sha1($this->get('router')->generate('ens_job_index', array('_format'=> 'atom'), true)),
        ));
    }

    /**
     * Creates a new Job entity.
     *
     * @Route("/new", name="ens_job_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $job = new Job();
        $form = $this->createForm('Ens\JobeetBundle\Form\JobType', $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();

            return $this->redirectToRoute('ens_job_show', array('id' => $job->getId()));
        }

        return $this->render('job/new.html.twig', array(
            'job' => $job,
            'company' => $job->getCompanySlug(),
            'location' => $job->getLocationSlug(),
            'token' => $job->getTokenSlug(),
            'position' => $job->getPositionSlug(),
            'howToApply' => $job->getHowToApplySlug(),
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Job entity.
     *
     * @Route("/{id}/{company}/{location}/{position}", name="ens_job_show")
     * @Method("GET")
     */
    public function showAction(Job $job)
    {
        $deleteForm = $this->createDeleteForm($job);

        $session = $this->getRequest()->getSession();

        $tabJobs = $session->get('job_history', array());

        $curJob = $job = array(
            'id' => $job->getId(),
            'position' =>$job->getPosition(),
            'company' => $job->getCompany(),
            'location' => $job->getLocationSlug(),
            'position' => $job->getPositionSlug(),
            'type' => $job->getType(),
            'logo' => $job->getLogo(),
            'url' => $job->getUrl(),
            'description' => $job->getDescription(),
            'howtoapply' => $job->getHowToApply(),
            'createdat' => $job->getCreatedAt() );


        if (!in_array($curJob, $tabJobs)) {
            // add the current job at the beginning of the array
            array_unshift($tabJobs, $curJob);

            // store the new job history back into the session
            $session->set('job_history', array_slice($tabJobs, 0, 3));
        }

        return $this->render('job/show.html.twig', array(
            'job' => $job,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("{token}", name="ens_job_preview")
     * @Method({"GET","POST"})
     */
    public function previewAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $job = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$job) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($job);
        $publishForm = $this->createPublishForm($job->getToken());
        $extendForm = $this->createExtendForm($job->getToken());

        return $this->render('job/show.html.twig', array(
            'job'      => $job,
            'delete_form' => $deleteForm->createView(),
            'publish_form' => $publishForm->createView(),
            'extend_form' => $extendForm->createView(),
        ));
    }



    /**
     * Finds and displays a Job entity.
     *
     * @Route("/{token}/publish", name="ens_job_publish")
     * @Method("POST")
     */
    public function publishAction($token)
    {
        $form = $this->createPublishForm($token);
        $em = $this->getDoctrine()->getEntityManager();
        $job = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $job = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

            if (!$job) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            $job->publish();
            $em->persist($job);
            $em->flush();

            $this->get('session')->setFlash('notice', 'Your job is now online for 30 days.');
        }

        return $this->redirect($this->generateUrl('ens_job_preview', array(
            'company' => $job->getCompanySlug(),
            'location' => $job->getLocationSlug(),
            'token' => $job->getTokenSlug(),
            'position' => $job->getPositionSlug()
        )));
    }
    private function createPublishForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
            ->add('token', 'hidden')
            ->getForm()
            ;
    }

    /**
     * Displays a form to edit an existing Job entity.
     *
     * @Route("/{token}/edit", name="ens_job_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $job = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        $deleteForm = $this->createDeleteForm($job);
        $editForm = $this->createForm('Ens\JobeetBundle\Form\JobType', $job);


        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();

            return $this->redirectToRoute('ens_job_edit', array('id' => $job->getId()));
        }

        return $this->render('job/edit.html.twig', array(
            'job' => $job,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Job entity.
     *
     * @Route("/{id}", name="ens_job_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Job $job)
    {
        $form = $this->createDeleteForm($job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($job);
            $em->flush();
        }

        return $this->redirectToRoute('ens_job_index');
    }

    /**
     * Creates a form to delete a Job entity.
     *
     * @param Job $job The Job entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Job $job)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ens_job_delete', array('id' => $job->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @Route("/{token}/extend", name="ens_job_extend")
     * @Method("POST")
     */
    public function extendAction($token, $request) {
        $form = $this->createExtendForm($token);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            if (!$entity->extend()) {
                throw $this->createNotFoundException('Unable to find extend the Job.');
            }

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', sprintf('Your job validity has been extended until %s.',
                $entity->getExpiresAt()->format('m/d/Y')));
        }

        return $this->redirect($this->generateUrl('ens_job_preview', array(
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'token' => $entity->getToken(),
            'position' => $entity->getPositionSlug()
        )));
    }
    private function createExtendForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
            ->add('token', 'hidden')
            ->getForm()
            ;
    }

}
