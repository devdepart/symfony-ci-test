<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use App\Form\DataTransformer\UsdCentsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class ProductType extends AbstractType
{
    public function __construct(
        private readonly UsdCentsTransformer $usdCentsTransformer,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product name',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('price', TextType::class, [
                'label' => 'Price (USD)',
                'required' => true,
                'attr' => [
                    'inputmode' => 'decimal',
                    'placeholder' => '125.50',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex(
                        pattern: '/\A\d+(?:\.\d{1,2})?\z/',
                        message: 'Enter a valid amount with no more than two decimal places.',
                    ),
                ],
            ])
        ;

        $builder
            ->get('price')
            ->addModelTransformer($this->usdCentsTransformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => true,
        ]);
    }
}
