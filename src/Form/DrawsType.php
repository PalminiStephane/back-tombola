<?php

namespace App\Form;

use App\Entity\Draws;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DrawsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('drawDate')
            ->add('ticketPrice')
            ->add('ticketsAvailable')
            ->add('totalTickets')
            ->add('status')
            ->add('ticketValidityDuration')
            ->add('winners')
            ->add('winnerName')
            ->add('prize')
            ->add('picture')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Draws::class,
        ]);
    }
}
