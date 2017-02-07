<?php

namespace ITDoors\SenderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EmailCampaignType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', 'text', [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Subject is required'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Subject cannot be longer than 255'
                    ])
                ]
            ])
            ->add('body', 'text', [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Body is required'
                    ])
                ]
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
