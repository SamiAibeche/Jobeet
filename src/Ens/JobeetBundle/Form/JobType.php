<?php

namespace Ens\JobeetBundle\Form;

use Ens\JobeetBundle\EnsJobeetBundle;
use Ens\JobeetBundle\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options){

        $builder->add('category')
                ->add('type', 'choice', array('choices' => Job::getTypes(), 'expanded' => true))
                ->add('company')
                ->add('file', 'file', array('label' => 'Company logo', 'required' => false))
                ->add('url')
                ->add('position')
                ->add('location')
                ->add('description')
                ->add('how_to_apply', null, array('label' => 'How to apply?'))
                ->add('is_public', null, array('label' => 'Public?'))
                ->add('email');

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ens\JobeetBundle\Entity\Job'
        ));
    }

    public function getName()
    {
        return 'ens_jobeetbundle_jobtype';
    }
}
