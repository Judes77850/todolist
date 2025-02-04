<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
	        ->add('username', TextType::class, [
		        'label' => 'Nom d\'utilisateur',
			])
	        ->add('password', PasswordType::class, [
		        'label' => 'Mot de passe',
		        'mapped' => false,
	        ])
	        ->add('save', SubmitType::class, ['label' => 'Créer un utilisateur'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
	        'csrf_protection' => true,
	        'csrf_field_name' => '_token',
	        'csrf_token_id' => 'create_user',
        ]);
    }
}
