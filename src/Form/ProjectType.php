<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Type\Provider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('provider', EnumType::class, [
                'class' => Provider::class,
                'expanded' => true,
                'choice_label' => static fn (Provider $provider): string => $provider->label(),
                'label' => 'Provider',
            ])
            ->add('url', TextType::class, [
                'label' => 'URL du dépôt',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'help' => 'Déduit de l\'URL (owner/repo) si laissé vide.',
            ])
            ->add('plainToken', PasswordType::class, [
                'label' => 'Token de lecture',
                'required' => false,
                'always_empty' => true,
                'help' => 'À l\'édition, laissez vide pour conserver le token existant.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectFormData::class,
            'validation_groups' => ['Default'],
        ]);
    }
}
