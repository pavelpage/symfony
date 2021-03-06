<?php

namespace App\Form;

use App\Entity\Image;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Url;

class ExternalUrlsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urls', CollectionType::class, [
                'mapped' => false,
                'entry_type' => UrlType::class,
                'allow_add' => true,

                'entry_options' => [
                    'required' => true,
                    'constraints' => [ new Url() ]
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Create'])
            ->setAction('/api/images/store-from-remote-source')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
            'csrf_protection' => false,
        ]);
    }
}
