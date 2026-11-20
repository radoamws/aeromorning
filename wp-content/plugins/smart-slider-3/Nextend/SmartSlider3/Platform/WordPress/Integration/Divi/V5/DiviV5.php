<?php

namespace Nextend\SmartSlider3\Platform\WordPress\Integration\Divi\V5;

use Nextend\Framework\Asset\Builder\BuilderJs;
use Nextend\SmartSlider3\Application\ApplicationSmartSlider3;
use Nextend\SmartSlider3\Application\Frontend\ApplicationTypeFrontend;
use Nextend\SmartSlider3\Application\Model\ModelSliders;
use Nextend\SmartSlider3\SmartSlider3Info;

/**
 * Divi 5 integration for Smart Slider 3.
 *
 * Registers the native `smartslider/smart-slider-3` module on the server (see
 * {@see SmartSlider3ModuleV5}) and enqueues the Visual Builder script that adds the
 * matching module to the builder's module list, complete with the slider dropdown
 * and an iframe based live preview.
 */
class DiviV5 {

    public function __construct() {

        /**
         * Register the server side module (block type + frontend render callback).
         *
         * @see \ET\Builder\Packages\ModuleLibrary\Modules
         */
        add_action('divi_module_library_modules_dependency_tree', array(
            $this,
            'registerModule'
        ));

        /**
         * Enqueue the Visual Builder registration script. `divi-module-library` is only
         * loaded in the builder's app window, so we hook the app window specific action.
         */
        add_action('divi_visual_builder_assets_after_enqueue_app_window_scripts', array(
            $this,
            'enqueueVisualBuilderScripts'
        ));

        /**
         * Refresh the cached Visual Builder assets when a slider changes so the dropdown
         * stays in sync with the available sliders.
         */
        add_action('smartslider3_slider_changed', array(
            $this,
            'clearDiviCache'
        ));
    }

    /**
     * Add the Smart Slider 3 module to Divi's dependency tree.
     *
     * @param object $dependency_tree Divi's DependencyTree instance.
     *
     * @return void
     */
    public function registerModule($dependency_tree) {
        $dependency_tree->add_dependency(new SmartSlider3ModuleV5());
    }

    /**
     * Enqueue the Visual Builder module registration script.
     *
     * @return void
     */
    public function enqueueVisualBuilderScripts() {

        $handle = 'smartslider3-divi5-vb';

        $scriptPath = __DIR__ . '/assets/vb-module.js';
        $version    = SmartSlider3Info::$version;
        if (file_exists($scriptPath)) {
            $version .= '.' . filemtime($scriptPath);
        }

        wp_enqueue_script(
            $handle,
            plugin_dir_url(__FILE__) . 'assets/vb-module.js',
            array(
                'divi-vendor-react',
                'divi-module',
                'divi-module-library'
            ),
            $version,
            true
        );

        wp_localize_script($handle, 'SmartSlider3DiviV5Data', array(
            'iframeUrl'    => self::getIframeUrl(),
            'sliderOptions' => $this->getSliderOptions()
        ));

        /**
         * Provide the iframe resizing helper (window.n2SSIframeLoader) in the builder's
         * app window, where the preview iframe lives. Mirrors the Divi 4 module.
         */
        $iframeLoader = $this->getIframeLoaderJs();
        if ($iframeLoader) {
            wp_add_inline_script($handle, $iframeLoader, 'before');
        }
    }

    /**
     * Clear Divi's cached builder assets so the slider dropdown is regenerated.
     *
     * @return void
     */
    public function clearDiviCache() {
        if (function_exists('et_fb_delete_builder_assets')) {
            et_fb_delete_builder_assets();
        }
    }

    /**
     * Build the iframe preview URL used by both the Visual Builder and the frontend.
     *
     * @return string
     */
    public static function getIframeUrl() {
        return site_url('/') . '?n2prerender=1&n2app=smartslider&n2controller=slider&n2action=iframe&h=' . sha1(NONCE_SALT . date('Y-m-d'));
    }

    /**
     * Get the iframe resizing helper JavaScript (window.n2SSIframeLoader).
     *
     * @return string
     */
    private function getIframeLoaderJs() {

        $path = ApplicationTypeFrontend::getAssetsPath() . '/dist/iframe.min.js';

        add_filter('js_escape', 'Nextend\Framework\Sanitize::esc_js_filter', 10, 2);

        $js = '';
        if (file_exists($path)) {
            $js = file_get_contents($path);
        } else if (!N2BUILD) {
            $builderJs = new BuilderJs();
            $output    = $builderJs->getOutput($path);
            if ($output) {
                $js = $output;
            }
        }

        remove_filter('js_escape', 'Nextend\Framework\Sanitize::esc_js_filter', 10);

        return $js;
    }

    /**
     * Build the list of sliders for the module's dropdown.
     *
     * Values are prefixed (`id-<number>` / `alias-<slug>`) so the browser preserves the
     * option order (plain numeric object keys would be reordered). `-1` means "None".
     *
     * @return array Ordered list of `['value' => ..., 'label' => ...]` entries.
     */
    public function getSliderOptions() {

        $options   = array();
        $options[] = array(
            'value' => '-1',
            'label' => 'None'
        );

        $applicationType = ApplicationSmartSlider3::getInstance()
                                                  ->getApplicationTypeAdmin();

        $slidersModel = new ModelSliders($applicationType);

        foreach ($slidersModel->getAll(0, 'published') as $slider) {
            if ($slider['type'] == 'group') {

                if (!empty($slider['alias'])) {
                    $options[] = array(
                        'value' => 'alias-' . $slider['alias'],
                        'label' => '― ' . n2_('Whole group') . ' - ' . $slider['title'] . ' #Alias: ' . $slider['alias']
                    );
                }
                $options[] = array(
                    'value' => 'id-' . $slider['id'],
                    'label' => '― ' . n2_('Whole group') . ' - ' . $slider['title'] . ' #' . $slider['id']
                );

                foreach ($slidersModel->getAll($slider['id'], 'published') as $_slider) {
                    if (!empty($_slider['alias'])) {
                        $options[] = array(
                            'value' => 'alias-' . $_slider['alias'],
                            'label' => '― ' . $_slider['title'] . ' #Alias: ' . $_slider['alias']
                        );
                    }
                    $options[] = array(
                        'value' => 'id-' . $_slider['id'],
                        'label' => '― ' . $_slider['title'] . ' #' . $_slider['id']
                    );
                }
            } else {
                if (!empty($slider['alias'])) {
                    $options[] = array(
                        'value' => 'alias-' . $slider['alias'],
                        'label' => $slider['title'] . ' #Alias: ' . $slider['alias']
                    );
                }
                $options[] = array(
                    'value' => 'id-' . $slider['id'],
                    'label' => $slider['title'] . ' #' . $slider['id']
                );
            }
        }

        return $options;
    }
}
