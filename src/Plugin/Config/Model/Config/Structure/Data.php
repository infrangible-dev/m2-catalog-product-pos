<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Plugin\Config\Model\Config\Structure;

use Infrangible\CatalogProductPOS\Helper\Data as Helper;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Config\Model\Config\Structure\Data as Subject;

/**
 * Adds a group of settings for each option of the point-of-sale attribute to the configuration section of the module.
 */
class Data
{
    private const SECTION = 'infrangible_catalogproductpos';

    /**
     * @param Helper $helper
     */
    public function __construct(protected Helper $helper)
    {
    }

    /**
     * Adds the groups of the options to the section of the module.
     *
     * @param Subject $subject
     * @param array $config
     * @phpstan-param array<int|string, mixed> $config
     *
     * @return array
     * @phpstan-return array{0: array<int|string, mixed>}
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeMerge(Subject $subject, array $config): array
    {
        $configNode = $config[ 'config' ] ?? null;
        $system = is_array($configNode) ? $configNode[ 'system' ] ?? null : null;
        $sections = is_array($system) ? $system[ 'sections' ] ?? null : null;
        $section = is_array($sections) ? $sections[ self::SECTION ] ?? null : null;

        if (! is_array($configNode) || ! is_array($system) || ! is_array($sections) || ! is_array($section) ||
            ! isset($section[ 'children' ][ 'attribute' ][ 'children' ][ 'attribute' ])) {

            return [$config];
        }

        $sortOrder = 10;

        foreach ($this->helper->getAttributeOptions() as $optionId => $optionLabel) {
            $groupId = sprintf('option_id_%d', $optionId);

            $section[ 'children' ][ $groupId ] = [
                'id'            => $groupId,
                'type'          => 'text',
                'sortOrder'     => (string)++$sortOrder,
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'label'         => $optionLabel,
                'children'      => [
                    'not_saleable' => $this->createField($groupId, 'not_saleable', 'Not Saleable', 'select', 10, []),
                    'commanding'   => $this->createField(
                        $groupId,
                        'commanding',
                        'Commanding',
                        'select',
                        20,
                        ['not_saleable']
                    ),
                    'show_button'  => $this->createField(
                        $groupId,
                        'show_button',
                        'Show Button',
                        'select',
                        30,
                        ['not_saleable']
                    ),
                    'button_text'  => $this->createField(
                        $groupId,
                        'button_text',
                        'Button Text',
                        'text',
                        40,
                        ['not_saleable', 'show_button']
                    ),
                    'button_url'   => $this->createField(
                        $groupId,
                        'button_url',
                        'Button Url',
                        'text',
                        50,
                        ['not_saleable', 'show_button']
                    )
                ],
                '_elementType'  => 'group',
                'path'          => self::SECTION
            ];
        }

        $sections[ self::SECTION ] = $section;
        $system[ 'sections' ] = $sections;
        $configNode[ 'system' ] = $system;
        $config[ 'config' ] = $configNode;

        return [$config];
    }

    /**
     * Returns the configuration of a field of an option group.
     *
     * The field is shown when the given yes/no fields of the group are set to yes.
     *
     * @param string $groupId
     * @param string $fieldId
     * @param string $label
     * @param string $type "select" for a yes/no field, "text" for a text field
     * @param int $sortOrder
     * @param list<string> $dependsOn
     *
     * @return array<string, mixed>
     */
    private function createField(
        string $groupId,
        string $fieldId,
        string $label,
        string $type,
        int $sortOrder,
        array $dependsOn
    ): array {
        $field = [
            'id'            => $fieldId,
            'translate'     => 'label',
            'type'          => $type,
            'sortOrder'     => (string)$sortOrder,
            'showInDefault' => '1',
            'showInWebsite' => '1',
            'showInStore'   => '1',
            'label'         => $label,
            '_elementType'  => 'field',
            'path'          => sprintf('%s/%s', self::SECTION, $groupId)
        ];

        if ($type === 'select') {
            $field[ 'source_model' ] = Yesno::class;
        }

        foreach ($dependsOn as $dependency) {
            $field[ 'depends' ][ 'fields' ][ $dependency ] = [
                '_elementType' => 'field',
                'id'           => sprintf('%s/%s/%s', self::SECTION, $groupId, $dependency),
                'value'        => '1',
                'dependPath'   => [self::SECTION, $groupId, $dependency]
            ];
        }

        return $field;
    }
}
