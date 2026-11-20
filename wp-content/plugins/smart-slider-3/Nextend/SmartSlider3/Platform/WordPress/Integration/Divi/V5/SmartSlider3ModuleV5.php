<?php

namespace Nextend\SmartSlider3\Platform\WordPress\Integration\Divi\V5;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use WP_Block;

/**
 * Native Divi 5 module for Smart Slider 3.
 *
 * Registers a `smartslider/smart-slider-3` block with the Divi 5 Module Library so
 * the slider can be picked from a dropdown in the Visual Builder and rendered both
 * in the builder preview (see the Visual Builder registration script) and on the
 * published frontend (this class' render callback).
 *
 * This is a dependency class and is added to Divi's `DependencyTree`.
 *
 * @see DependencyInterface
 */
class SmartSlider3ModuleV5 implements DependencyInterface {

    /**
     * Register the module with Divi 5's Module Library.
     *
     * @return void
     */
    public function load(): void {

        if (!class_exists('\ET\Builder\Packages\ModuleLibrary\ModuleRegistration')) {
            return;
        }

        $module_json_folder_path = __DIR__ . '/module/';

        ModuleRegistration::register_module(
            $module_json_folder_path,
            array(
                'render_callback' => array(
                    self::class,
                    'render_callback'
                )
            )
        );
    }

    /**
     * Generate classnames for the module wrapper.
     *
     * @param array $args {
     *     @type object $classnamesInstance Module classnames instance.
     *     @type array  $attrs              Module attributes.
     * }
     *
     * @return void
     */
    public static function module_classnames(array $args): void {
        $classnames_instance = $args['classnamesInstance'];
        $attrs               = $args['attrs'];

        $classnames_instance->add(
            ElementClassnames::classnames(
                array(
                    'attrs' => array_merge(
                        $attrs['module']['decoration'] ?? array(),
                        array(
                            'link' => $attrs['module']['advanced']['link'] ?? array(),
                        )
                    ),
                )
            )
        );
    }

    /**
     * Module script data. Wires the module element interactions/visibility.
     *
     * @param array $args Script data arguments provided by Divi.
     *
     * @return void
     */
    public static function module_script_data(array $args): void {
        $elements = $args['elements'];

        $elements->script_data(
            array(
                'attrName' => 'module',
            )
        );
    }

    /**
     * Module style components.
     *
     * @param array $args Style arguments provided by Divi.
     *
     * @return void
     */
    public static function module_styles(array $args): void {
        $attrs    = $args['attrs'] ?? array();
        $elements = $args['elements'];
        $settings = $args['settings'] ?? array();

        Style::add(
            array(
                'id'            => $args['id'],
                'name'          => $args['name'],
                'orderIndex'    => $args['orderIndex'],
                'storeInstance' => $args['storeInstance'],
                'styles'        => array(
                    $elements->style(
                        array(
                            'attrName'   => 'module',
                            'styleProps' => array(
                                'disabledOn' => array(
                                    'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
                                ),
                            ),
                        )
                    ),
                    CssStyle::style(
                        array(
                            'selector' => $args['orderClass'],
                            'attr'     => $attrs['css'] ?? array(),
                        )
                    ),
                ),
            )
        );
    }

    /**
     * Render the module on the frontend.
     *
     * @param array          $attrs    Block attributes saved by the Divi Builder.
     * @param string         $content  Block content.
     * @param WP_Block       $block    Parsed block object being rendered.
     * @param ModuleElements $elements ModuleElements instance.
     *
     * @return string The rendered HTML.
     */
    public static function render_callback(array $attrs, string $content, WP_Block $block, ModuleElements $elements): string {

        $slider = $attrs['module']['advanced']['slider']['desktop']['value'] ?? '-1';

        $slider_html = self::render_slider($slider);

        return Module::render(
            array(
                // FE only.
                'orderIndex'          => $block->parsed_block['orderIndex'],
                'storeInstance'       => $block->parsed_block['storeInstance'],

                // VB equivalent.
                'attrs'               => $attrs,
                'elements'            => $elements,
                'id'                  => $block->parsed_block['id'],
                'name'                => $block->block_type->name,
                'moduleCategory'      => $block->block_type->category,
                'classnamesFunction'  => array(
                    self::class,
                    'module_classnames'
                ),
                'stylesComponent'     => array(
                    self::class,
                    'module_styles'
                ),
                'scriptDataComponent' => array(
                    self::class,
                    'module_script_data'
                ),
                'children'            => $elements->style_components(
                    array(
                        'attrName' => 'module',
                    )
                ) . $slider_html,
            )
        );
    }

    /**
     * Turn the chosen slider value into the slider HTML via the Smart Slider shortcode.
     *
     * The dropdown stores prefixed values so the option order is preserved in the
     * Visual Builder (plain numeric keys would be reordered by the browser):
     *   - `id-<number>`  → select the slider by id
     *   - `alias-<slug>` → select the slider by alias
     * Bare numeric / non-empty values are still accepted as a fallback for safety.
     *
     * @param string $slider Prefixed slider identifier. `-1` (or empty) means "None".
     *
     * @return string
     */
    public static function render_slider($slider): string {

        $slider = (string)$slider;

        if ('' === $slider || '-1' === $slider) {
            return '';
        }

        if (0 === strpos($slider, 'id-')) {
            return do_shortcode('[smartslider3 slider="' . intval(substr($slider, 3)) . '"]');
        }

        if (0 === strpos($slider, 'alias-')) {
            return do_shortcode('[smartslider3 alias="' . esc_attr(substr($slider, 6)) . '"]');
        }

        // Fallback for values without a prefix.
        if (is_numeric($slider)) {
            return do_shortcode('[smartslider3 slider="' . intval($slider) . '"]');
        }

        return do_shortcode('[smartslider3 alias="' . esc_attr($slider) . '"]');
    }
}
