<?php

namespace App\Form;

use App\Entity\Piece;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PieceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('code')
            ->add('promoted')
            ->add('color')
            ->add('row', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'attr' => array(
                    'class' => 'rowinput'
                )
            ))
            ->add('col', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'attr' => array(
                    'class' => 'colinput'
                )
            ))
            ->add('generator')
            ->add('promotedgenerator')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Piece::class,
        ]);
    }
}
