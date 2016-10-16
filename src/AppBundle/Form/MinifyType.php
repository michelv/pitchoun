<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class MinifyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'Minify this URL',
                'attr' => [
                    'placeholder' => 'Super ultra long URL',
                ],
            ])
            ->add('minify', SubmitType::class)
        ;
    }
}
