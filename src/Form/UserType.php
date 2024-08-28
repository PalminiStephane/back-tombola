<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => "Votre E-Mail"
            ])
            ->add('isEmailVerified')
            ->add('emailVerificationToken')
            ->add('roles', ChoiceType::class,  [
                "choices" => [
                    "ADMIN" => "ROLE_ADMIN",
                    "MANAGER" => "ROLE_MANAGER", 
                    "USER" => "ROLE_USER"
                ], 
                'multiple' => true,
                'expanded' => true
            ])
            ->add('password')
            ->add('name')
            ->add('registrationDate')
            ->add('lastLoginDate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
