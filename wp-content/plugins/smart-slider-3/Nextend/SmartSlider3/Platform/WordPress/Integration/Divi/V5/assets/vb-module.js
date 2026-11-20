/**
 * Smart Slider 3 - native Divi 5 Visual Builder module.
 *
 * Registers a `smartslider/smart-slider-3` module in the Divi 5 builder. The module
 * offers a dropdown to pick a slider and renders a live preview inside an <iframe>
 * (the same isolated preview approach used by the Divi 4 module). The server side
 * counterpart (SmartSlider3ModuleV5.php) renders the slider on the published page.
 *
 * This runs in the builder's *app window*, where `window.vendor.React`,
 * `window.divi.module` and `window.divi.moduleLibrary` are available.
 */
(function () {
    'use strict';

    if (!window.divi || !window.divi.moduleLibrary || !window.divi.module || !window.vendor || !window.vendor.React) {
        // Divi's module APIs are not available - nothing to register.
        return;
    }

    var MODULE_NAME = 'smartslider/smart-slider-3';

    // Avoid registering twice if the script somehow runs more than once.
    try {
        if (window.divi.data && window.divi.data.select('divi/module-library').getModule(MODULE_NAME)) {
            return;
        }
    } catch (e) {
        // Store not ready yet - continue and register.
    }

    var React = window.vendor.React;
    var registerModule = window.divi.moduleLibrary.registerModule;
    var ModuleContainer = window.divi.module.ModuleContainer;
    var StyleContainer = window.divi.module.StyleContainer;
    var CssStyle = window.divi.module.CssStyle;
    var h = React.createElement;

    var data = window.SmartSlider3DiviV5Data || {};
    var iframeUrl = data.iframeUrl || '';
    var sliderOptions = data.sliderOptions || [{value: '-1', label: 'None'}];

    /**
     * Build the `divi/select` options object from the ordered list provided by PHP.
     * All keys are non-numeric (`-1`, `id-*`, `alias-*`) so insertion order is kept.
     */
    function buildOptions() {
        var options = {};
        for (var i = 0; i < sliderOptions.length; i++) {
            options[sliderOptions[i].value] = {label: sliderOptions[i].label};
        }
        return options;
    }

    /**
     * Strip the value prefix so the raw slider id/alias can be passed to the iframe.
     */
    function sliderIdForPreview(value) {
        value = '' + (value || '');
        if (value.indexOf('id-') === 0) {
            return value.substring(3);
        }
        if (value.indexOf('alias-') === 0) {
            return value.substring(6);
        }
        return value;
    }

    /**
     * Resolve the iframe resizing helper (with a Firefox fallback to the parent
     * window, mirroring the Divi 4 module).
     */
    function getIframeLoader() {
        if (typeof window.n2SSIframeLoader === 'function') {
            return window.n2SSIframeLoader;
        }
        if (window.parent && typeof window.parent.n2SSIframeLoader === 'function') {
            return window.parent.n2SSIframeLoader;
        }
        return null;
    }

    /**
     * Edit component: renders the module preview as an iframe.
     */
    function SmartSlider3Edit(props) {
        var attrs = props.attrs || {};
        var chosen = '-1';
        try {
            chosen = attrs.module.advanced.slider.desktop.value;
        } catch (e) {
            chosen = '-1';
        }

        var frameObjectRef = React.useRef(null);
        var detachListenerRef = React.useRef(null);
        var normalizedRef = React.useRef(false);

        // Self-heal Divi 4 -> Divi 5 converted values. The conversion maps the raw D4
        // `slider` value (e.g. "12" or "iceland-featured") without the `id-`/`alias-`
        // prefix this module stores, so the dropdown would show no selection. Once per
        // module instance, rewrite a raw value to the prefixed form so the dropdown
        // matches. Rendering is unaffected either way (the frontend accepts both).
        React.useEffect(function () {
            if (normalizedRef.current) {
                return;
            }
            normalizedRef.current = true;

            var raw = '' + (chosen == null ? '' : chosen);
            if (raw === '' || raw === '-1' || raw.indexOf('id-') === 0 || raw.indexOf('alias-') === 0) {
                return;
            }

            var normalized = /^[0-9]+$/.test(raw) ? ('id-' + raw) : ('alias-' + raw);

            try {
                window.divi.data.dispatch('divi/edit-post').editModuleAttribute({
                    id: props.id,
                    attrName: 'module.advanced.slider',
                    value: {desktop: {value: normalized}}
                });
            } catch (e) {
            }
        }, []);

        // Ref callback: (re)attach the load listener whenever the iframe node is
        // mounted, and detach it on unmount. The same iframe node is reused when
        // only the slider (src) changes, so we must NOT key the iframe by slider -
        // otherwise the node would remount and resetting the detached previous node
        // would throw (this caused the "content could not be displayed" error).
        function setIframeNode(node) {
            if (detachListenerRef.current) {
                detachListenerRef.current();
                detachListenerRef.current = null;
            }
            frameObjectRef.current = null;

            if (!node) {
                return;
            }

            var loader = getIframeLoader();

            function onLoad(e) {
                if (loader) {
                    try {
                        frameObjectRef.current = loader(e.target);
                    } catch (err) {
                    }
                }
            }

            node.addEventListener('load', onLoad);
            detachListenerRef.current = function () {
                node.removeEventListener('load', onLoad);
            };
        }

        // When the selected slider changes, reset the (still mounted) frame so the
        // resizing helper re-initialises for the newly loaded slider. Best-effort:
        // never let a reset error bubble up to Divi's error boundary.
        React.useEffect(function () {
            if (frameObjectRef.current && typeof frameObjectRef.current.reset === 'function') {
                try {
                    frameObjectRef.current.reset();
                } catch (e) {
                }
            }
        }, [chosen]);

        var previewChildren;
        if (!chosen || chosen === '-1') {
            previewChildren = h(
                'div',
                {
                    className: 'n2-ss-slider-frame-placeholder',
                    style: {
                        padding: '40px 20px',
                        textAlign: 'center',
                        border: '1px dashed #ccc',
                        color: '#666',
                        fontSize: '14px'
                    }
                },
                'Select a slider to display.'
            );
        } else {
            previewChildren = h('iframe', {
                ref: setIframeNode,
                title: 'smart-slider-3-preview',
                className: 'n2-ss-slider-frame intrinsic-ignore',
                src: iframeUrl + '&sliderid=' + encodeURIComponent(sliderIdForPreview(chosen)),
                frameBorder: '0',
                style: {
                    width: '100%',
                    display: 'block',
                    border: '0',
                    maxWidth: 'none'
                }
            });
        }

        return h(
            ModuleContainer,
            {
                attrs: attrs,
                id: props.id,
                name: props.name,
                elements: props.elements,
                defaultPrintedStyleAttrs: props.defaultPrintedStyleAttrs,
                stylesComponent: SmartSlider3Styles
            },
            // Same children as the server side render callback.
            props.elements.styleComponents({attrName: 'module'}),
            h('div', {className: 'n2_ss_slider_frame_container'}, previewChildren)
        );
    }

    /**
     * Styles component: the builder side counterpart of
     * SmartSlider3ModuleV5::module_styles(). `disabledOn` is what dims the module in
     * the builder when Advanced > Visibility disables it on the current breakpoint,
     * and hides it on the frontend; `CssStyle` renders the Advanced > CSS group.
     */
    function SmartSlider3Styles(props) {
        return h(
            StyleContainer,
            {
                mode: props.mode,
                state: props.state,
                noStyleTag: props.noStyleTag,
                isInsideStickyModule: props.isInsideStickyModule,
                stickyParentOrderClass: props.stickyParentOrderClass
            },
            props.elements.style({
                attrName: 'module',
                styleProps: {
                    disabledOn: {
                        disabledModuleVisibility: props.settings && props.settings.disabledModuleVisibility
                    }
                }
            }),
            h(CssStyle, {
                selector: props.orderClass,
                attr: props.attrs.css
            })
        );
    }

    var metadata = {
        name: MODULE_NAME,
        d4Shortcode: 'et_pb_nextend_smart_slider_3',
        moduleClassName: 'et_pb_smart_slider_3',
        moduleOrderClassName: 'et_pb_smart_slider_3',
        title: 'Smart Slider 3',
        titles: 'Smart Slider 3',
        moduleIcon: 'divi/module',
        category: 'module',
        attributes: {
            module: {
                type: 'object',
                selector: '{{selector}}',
                // Smart Slider 3 controls its own layout and styling, so this module
                // exposes only the slider dropdown plus the Advanced panel's Attributes
                // ("Add Attribute") and Visibility ("Disable On") groups - both are applied
                // by Divi core (Module::render() and the disabledOn style props), so no
                // extra server side work is needed. Every other group-generating declaration
                // is left out (no `advanced.link`, no other `decoration` options) and the
                // Design panel is dropped from
                // `settings` below. Divi still always renders the Content/Design/Advanced tab
                // headers (its settings modal normalizes to those three), but with nothing
                // declared for Design that panel stays empty. The conversionOutline below
                // still maps the Divi 4 fields; decoration and link are universal module
                // attributes, so a converted D4 module keeps its values even where no editing
                // UI is exposed for them.
                //
                // Keep this metadata in sync with module/module.json (server side).
                settings: {
                    advanced: {
                        slider: {
                            groupType: 'group-item',
                            item: {
                                groupSlug: 'sliderContent',
                                attrName: 'module.advanced.slider',
                                label: 'Slider',
                                description: 'Select the Smart Slider 3 slider that will be displayed by this module.',
                                category: 'configuration',
                                features: {
                                    responsive: false,
                                    hover: false,
                                    sticky: false,
                                    preset: 'content'
                                },
                                render: true,
                                priority: 10,
                                component: {
                                    type: 'field',
                                    name: 'divi/select',
                                    props: {
                                        options: buildOptions()
                                    }
                                }
                            }
                        }
                    },
                    decoration: {
                        attributes: {},
                        disabledOn: {}
                    }
                }
            }
        },
        customCssFields: {},
        settings: {
            content: 'auto',
            advanced: 'auto',
            groups: {
                sliderContent: {
                    panel: 'content',
                    groupName: 'sliderContent',
                    priority: 10,
                    multiElements: true,
                    component: {
                        name: 'divi/composite',
                        props: {
                            groupLabel: 'Slider'
                        }
                    }
                }
            }
        }
    };

    /**
     * Map a raw Divi 4 slider value (id or alias) to the prefixed value the native
     * module stores, so a Divi 4 module converts to the correct dropdown selection.
     */
    function sliderValueFromD4(value) {
        value = '' + (value == null ? '' : value);
        if (value === '' || value === '-1') {
            return '-1';
        }
        if (/^[0-9]+$/.test(value)) {
            return 'id-' + value;
        }
        return 'alias-' + value;
    }

    /**
     * Conversion outline: lets a Divi 4 `et_pb_nextend_smart_slider_3` module convert
     * into this native Divi 5 module (instead of falling back to the legacy shortcode
     * module). Maps the D4 `slider` field to the D5 attribute and the standard Divi
     * option groups so no attribute is left "unknown" (which would trigger the legacy
     * fallback).
     */
    var conversionOutline = {
        advanced: {
            admin_label: 'module.meta.adminLabel',
            animation: 'module.decoration.animation',
            background: 'module.decoration.background',
            borders: {
                default: 'module.decoration.border'
            },
            box_shadow: {
                default: 'module.decoration.boxShadow'
            },
            disabled_on: 'module.decoration.disabledOn',
            display_conditions: 'module.decoration.conditions',
            filters: {
                default: 'module.decoration.filters'
            },
            height: 'module.decoration.sizing',
            link_options: 'module.advanced.link',
            margin_padding: 'module.decoration.spacing',
            max_width: 'module.decoration.sizing',
            module: 'module.advanced.htmlAttributes',
            overflow: 'module.decoration.overflow',
            position_fields: 'module.decoration.position',
            scroll: 'module.decoration.scroll',
            sticky: 'module.decoration.sticky',
            transform: 'module.decoration.transform',
            transition: 'module.decoration.transition',
            z_index: 'module.decoration.zIndex'
        },
        css: {
            after: 'css.*.after',
            before: 'css.*.before',
            free_form: 'css.*.freeForm',
            main_element: 'css.*.mainElement'
        },
        module: {
            slider: 'module.advanced.slider.*'
        },
        valueExpansionFunctionMap: {
            slider: function (value) {
                return sliderValueFromD4(value);
            }
        }
    };

    var config = {
        renderers: {
            edit: SmartSlider3Edit,
            styles: SmartSlider3Styles
        },
        conversionOutline: conversionOutline
    };

    registerModule(metadata, config);
})();
