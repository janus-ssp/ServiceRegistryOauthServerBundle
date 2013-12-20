<?php

namespace Janus\ConnectionsBundle\Form\Type\Connection;

use Janus\Model\Connection\Metadata\ConfigFieldsParser;
use Janus\Model\Connection\Metadata\FieldConfig;
use Janus\Model\Connection\Metadata\FieldConfigCollection;
use sspmod_janus_Model_Connection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MetadataType extends AbstractType
{
    /**
     * @var array
     */
    private $fieldsConfig;

    /**
     * @param array $fieldsConfig
     */
    public function __construct(array $fieldsConfig)
    {
        $this->fieldsConfig = $fieldsConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->fieldsConfig as $name => $fieldInfo) {
            if ($fieldInfo instanceof FieldConfigCollection) {
                // Add a collection of fields or field groups
                $type = $fieldInfo->getType();
                $supportedKeys = implode(',', $fieldInfo->getSupportedKeys());
                $builder->add($name, 'collection', array(
                    'type' => $type,
                    'attr' => array(
                        'class' => 'field-collection',
                        'data-supported-keys' => $supportedKeys
                    ),
                    'required' => true,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true
                ));
            } elseif ($fieldInfo instanceof FieldConfig) {
                $type = $fieldInfo->getType();
                if ($type instanceof MetadataType) {
                    // Add a nested group of fields
                    $builder->add($name, $type, array(
                        'attr' => array(
                            'class' => 'field-group',
                            'required' => true
                        )
                    ));
                } else {
                    $options = array(
                        'required' => $fieldInfo->getIsRequired(),
                    );

                    if ($type === 'choice') {
                        $options['choices'] = $fieldInfo->getChoices();
                    }

                    // Requiring checkboxes is not necessary for symfony forms
                    // Since false will be posted when unchecked
                    if ($type === 'checkbox') {
                        $options['required'] = false;
                    }

                    // Add a field
                    $builder->add($name, $type, $options);
                }
            } else {
                throw new \InvalidArgumentException(
                    "Unknown field info type '" . is_object($fieldInfo) ? get_class($fieldInfo) : gettype($fieldInfo) . "'"
                );
            }
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     *
     * @todo Correct options
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'intention' => 'connection',
            'translation_domain' => 'JanusConnectionsBundle'
        ));
    }

    public function getName()
    {
        return 'metadata';
    }
}