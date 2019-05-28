<?php

namespace App\Form;

use App\Entity\Image;
use App\Entity\ImageFile;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageFormType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ImageFormType constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        var_dump('here');


        $builder
//            ->add('files', FileType::class, [
//                'mapped' => false,
//                'multiple' => true,
//                'required' => true,
//                'constraints' => [ new File(['maxSize' => round(3*1024)]) ]
//            ])
            ->add('files', CollectionType::class, [
                // each entry in the array will be an "email" field
                'mapped' => false,
//                'required' => true,
                'entry_type' => FileType::class,
                'allow_add' => true,
                // these options are passed to each "email" type
                'entry_options' => [
                    'required' => true,
//                    'multiple' => true,
                    'constraints' => [ new File(['maxSize' => $this->container->getParameter('file_upload.max_size')]) ]
                ],
            ])

//            ->add('emails', CollectionType::class, [
//                // each entry in the array will be an "email" field
//                'entry_type' => TextType::class,
//                'mapped' => false,
//                // these options are passed to each "email" type
//                'entry_options' => [
//                    'attr' => ['class' => 'email-box'],
//                ],
//            ])

            ->add('save', SubmitType::class, ['label' => 'Create'])
            ->setAction('/api/images/store-files')
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
