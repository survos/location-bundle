<?php

namespace Survos\LocationBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;
use Survos\LocationBundle\Entity\Location;
use Symfony\Component\Form\AbstractType;

#[AsEntityAutocompleteField]
class LocationAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Location::class,
            'placeholder' => 'Where?',

            // choose which fields to use in the search
            // if not passed, *all* fields are used
//            'searchable_fields' => ['name', 'stateCode', 'countryCode'],

            // ... any other normal EntityType options
            // e.g. query_builder, choice_label
        ]);
    }

    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }
}
