<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Plugin\Config\Model\Config\Structure;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Magento\Config\Model\Config\Source\Yesno;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    /** @var Arrays */
    protected $arrays;

    /** @var Variables */
    protected $variables;

    /** @var \Infrangible\CatalogProductPOS\Helper\Data */
    protected $helper;

    public function __construct(
        Arrays $arrays,
        Variables $variables,
        \Infrangible\CatalogProductPOS\Helper\Data $helper
    ) {
        $this->arrays = $arrays;
        $this->variables = $variables;
        $this->helper = $helper;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function beforeMerge(\Magento\Config\Model\Config\Structure\Data $object, array $config): array
    {
        $sections = $this->arrays->getValue(
            $config,
            'config:system:sections'
        );

        if (! $sections) {
            return [$config];
        }

        foreach ($sections as $sectionName => $sectionData) {
            if ($sectionName === 'infrangible_catalogproductpos') {
                $groupsData = $this->arrays->getValue(
                    $sectionData,
                    'children',
                    []
                );

                foreach ($groupsData as $groupsKey => $groupData) {
                    if ($groupsKey === 'attribute') {
                        $fieldsData = $this->arrays->getValue(
                            $groupData,
                            'children',
                            []
                        );

                        foreach ($fieldsData as $fieldKey => $fieldData) {
                            if ($fieldKey === 'attribute') {
                                $sortOrder = 10;
                                $attributeOptions = $this->helper->getAttributeOptions();

                                foreach ($attributeOptions as $optionId => $optionLabel) {
                                    $sortOrder++;

                                    $optionGroupKey = sprintf(
                                        'option_id_%d',
                                        $optionId
                                    );

                                    $sectionData[ 'children' ][ $optionGroupKey ] = [
                                        'id'            => $optionGroupKey,
                                        'type'          => 'text',
                                        'sortOrder'     => $this->variables->stringValue($sortOrder),
                                        'showInDefault' => '1',
                                        'showInWebsite' => '1',
                                        'showInStore'   => '1',
                                        'label'         => $optionLabel,
                                        'children'      => [
                                            'saleable'    => [
                                                'id'            => 'not_saleable',
                                                'translate'     => 'label',
                                                'type'          => 'select',
                                                'sortOrder'     => '10',
                                                'showInDefault' => '1',
                                                'showInWebsite' => '1',
                                                'showInStore'   => '1',
                                                'label'         => 'Not Saleable',
                                                'source_model'  => Yesno::class,
                                                '_elementType'  => 'field',
                                                'path'          => sprintf(
                                                    'infrangible_catalogproductpos/%s',
                                                    $optionGroupKey
                                                )
                                            ],
                                            'show_button' => [
                                                'id'            => 'show_button',
                                                'translate'     => 'label',
                                                'type'          => 'select',
                                                'sortOrder'     => '20',
                                                'showInDefault' => '1',
                                                'showInWebsite' => '1',
                                                'showInStore'   => '1',
                                                'label'         => 'Show Button',
                                                'source_model'  => Yesno::class,
                                                '_elementType'  => 'field',
                                                'path'          => sprintf(
                                                    'infrangible_catalogproductpos/%s',
                                                    $optionGroupKey
                                                ),
                                                'depends'       => [
                                                    'fields' => [
                                                        'not_saleable' => [
                                                            '_elementType' => 'field',
                                                            'id'           => sprintf(
                                                                'infrangible_catalogproductpos/%s/not_saleable',
                                                                $optionGroupKey
                                                            ),
                                                            'value'        => '1',
                                                            'dependPath'   => [
                                                                'infrangible_catalogproductpos',
                                                                $optionGroupKey,
                                                                'not_saleable'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            'button_text' => [
                                                'id'            => 'button_text',
                                                'translate'     => 'label',
                                                'type'          => 'text',
                                                'sortOrder'     => '30',
                                                'showInDefault' => '1',
                                                'showInWebsite' => '1',
                                                'showInStore'   => '1',
                                                'label'         => 'Button Text',
                                                '_elementType'  => 'field',
                                                'path'          => sprintf(
                                                    'infrangible_catalogproductpos/%s',
                                                    $optionGroupKey
                                                ),
                                                'depends'       => [
                                                    'fields' => [
                                                        'not_saleable' => [
                                                            '_elementType' => 'field',
                                                            'id'           => sprintf(
                                                                'infrangible_catalogproductpos/%s/not_saleable',
                                                                $optionGroupKey
                                                            ),
                                                            'value'        => '1',
                                                            'dependPath'   => [
                                                                'infrangible_catalogproductpos',
                                                                $optionGroupKey,
                                                                'not_saleable'
                                                            ]
                                                        ],
                                                        'show_button'  => [
                                                            '_elementType' => 'field',
                                                            'id'           => sprintf(
                                                                'infrangible_catalogproductpos/%s/show_button',
                                                                $optionGroupKey
                                                            ),
                                                            'value'        => '1',
                                                            'dependPath'   => [
                                                                'infrangible_catalogproductpos',
                                                                $optionGroupKey,
                                                                'show_button'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            'button_url' => [
                                                'id'            => 'button_url',
                                                'translate'     => 'label',
                                                'type'          => 'text',
                                                'sortOrder'     => '40',
                                                'showInDefault' => '1',
                                                'showInWebsite' => '1',
                                                'showInStore'   => '1',
                                                'label'         => 'Button Url',
                                                '_elementType'  => 'field',
                                                'path'          => sprintf(
                                                    'infrangible_catalogproductpos/%s',
                                                    $optionGroupKey
                                                ),
                                                'depends'       => [
                                                    'fields' => [
                                                        'not_saleable' => [
                                                            '_elementType' => 'field',
                                                            'id'           => sprintf(
                                                                'infrangible_catalogproductpos/%s/not_saleable',
                                                                $optionGroupKey
                                                            ),
                                                            'value'        => '1',
                                                            'dependPath'   => [
                                                                'infrangible_catalogproductpos',
                                                                $optionGroupKey,
                                                                'not_saleable'
                                                            ]
                                                        ],
                                                        'show_button'  => [
                                                            '_elementType' => 'field',
                                                            'id'           => sprintf(
                                                                'infrangible_catalogproductpos/%s/show_button',
                                                                $optionGroupKey
                                                            ),
                                                            'value'        => '1',
                                                            'dependPath'   => [
                                                                'infrangible_catalogproductpos',
                                                                $optionGroupKey,
                                                                'show_button'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ],
                                        '_elementType'  => 'group',
                                        'path'          => 'infrangible_catalogproductpos'
                                    ];
                                }
                            }
                        }
                    }
                }

                $config[ 'config' ][ 'system' ][ 'sections' ][ $sectionName ] = $sectionData;
            }
        }

        return [$config];
    }
}
