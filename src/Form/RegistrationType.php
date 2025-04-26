<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => "Email",
                "attr" => [
                    "class" => "form-control mb-3",
                    'placeholder' => 'Entrez votre adresse email'
                ],
                'required' => true
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => "Mot de passe",
                'required' => true,
                "attr" => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control mb-3',
                    'placeholder' => 'Entrez votre mot de passe'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Entrez un mot de passe...',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => "Prénom",
                "attr" => [
                    "class" => "form-control mb-3",
                    'placeholder' => 'Entrez votre prénom'
                ],
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => "Nom",
                "attr" => [
                    "class" => "form-control mb-3",
                    'placeholder' => 'Entrez votre nom'
                ],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
