<?php

if (!defined('ABSPATH')) exit;


use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\CoreExtension;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* newsletter/editor.html */
class __TwigTemplate_aa06caf4713b6d5ac2d6909b4a0a21f60afeea73e802e676faab4d7dcfdcb31a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'templates' => [$this, 'block_templates'],
            'content' => [$this, 'block_content'],
            'after_javascript' => [$this, 'block_after_javascript'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "layout.html";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("layout.html", "newsletter/editor.html", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_templates($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "  ";
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_tools_generic", "newsletter/templates/blocks/base/toolsGeneric.hbs");
        // line 7
        yield "
  ";
        // line 8
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_block", "newsletter/templates/blocks/automatedLatestContent/block.hbs");
        // line 11
        yield "
  ";
        // line 12
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_widget", "newsletter/templates/blocks/automatedLatestContent/widget.hbs");
        // line 15
        yield "
  ";
        // line 16
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_settings", "newsletter/templates/blocks/automatedLatestContent/settings.hbs");
        // line 19
        yield "
  ";
        // line 20
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_block", "newsletter/templates/blocks/automatedLatestContentLayout/block.hbs");
        // line 23
        yield "
  ";
        // line 24
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_widget", "newsletter/templates/blocks/automatedLatestContentLayout/widget.hbs");
        // line 27
        yield "
  ";
        // line 28
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_automated_latest_content_layout_settings", "newsletter/templates/blocks/automatedLatestContentLayout/settings.hbs");
        // line 31
        yield "
  ";
        // line 32
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_dynamic_products_block", "newsletter/templates/blocks/dynamicProducts/block.hbs");
        // line 35
        yield "
  ";
        // line 36
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_dynamic_products_widget", "newsletter/templates/blocks/dynamicProducts/widget.hbs");
        // line 39
        yield "
  ";
        // line 40
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_dynamic_products_settings", "newsletter/templates/blocks/dynamicProducts/settings.hbs");
        // line 43
        yield "
  ";
        // line 44
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_block", "newsletter/templates/blocks/button/block.hbs");
        // line 47
        yield "
  ";
        // line 48
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_widget", "newsletter/templates/blocks/button/widget.hbs");
        // line 51
        yield "
  ";
        // line 52
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_button_settings", "newsletter/templates/blocks/button/settings.hbs");
        // line 55
        yield "
  ";
        // line 56
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_block", "newsletter/templates/blocks/container/block.hbs");
        // line 59
        yield "
  ";
        // line 60
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_block_empty", "newsletter/templates/blocks/container/emptyBlock.hbs");
        // line 63
        yield "
  ";
        // line 64
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_one_column_widget", "newsletter/templates/blocks/container/oneColumnLayoutWidget.hbs");
        // line 67
        yield "
  ";
        // line 68
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget.hbs");
        // line 71
        yield "
  ";
        // line 72
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_12_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget12.hbs");
        // line 75
        yield "
  ";
        // line 76
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_two_column_21_widget", "newsletter/templates/blocks/container/twoColumnLayoutWidget21.hbs");
        // line 79
        yield "
  ";
        // line 80
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_three_column_widget", "newsletter/templates/blocks/container/threeColumnLayoutWidget.hbs");
        // line 83
        yield "
  ";
        // line 84
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_settings", "newsletter/templates/blocks/container/settings.hbs");
        // line 87
        yield "
  ";
        // line 88
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_container_column_settings", "newsletter/templates/blocks/container/columnSettings.hbs");
        // line 91
        yield "
  ";
        // line 92
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_block", "newsletter/templates/blocks/divider/block.hbs");
        // line 95
        yield "
  ";
        // line 96
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_widget", "newsletter/templates/blocks/divider/widget.hbs");
        // line 99
        yield "
  ";
        // line 100
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_divider_settings", "newsletter/templates/blocks/divider/settings.hbs");
        // line 103
        yield "
  ";
        // line 104
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_block", "newsletter/templates/blocks/footer/block.hbs");
        // line 107
        yield "
  ";
        // line 108
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_widget", "newsletter/templates/blocks/footer/widget.hbs");
        // line 111
        yield "
  ";
        // line 112
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_footer_settings", "newsletter/templates/blocks/footer/settings.hbs");
        // line 115
        yield "
  ";
        // line 116
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_block", "newsletter/templates/blocks/header/block.hbs");
        // line 119
        yield "
  ";
        // line 120
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_widget", "newsletter/templates/blocks/header/widget.hbs");
        // line 123
        yield "
  ";
        // line 124
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_header_settings", "newsletter/templates/blocks/header/settings.hbs");
        // line 127
        yield "
  ";
        // line 128
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_block", "newsletter/templates/blocks/image/block.hbs");
        // line 131
        yield "
  ";
        // line 132
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_widget", "newsletter/templates/blocks/image/widget.hbs");
        // line 135
        yield "
  ";
        // line 136
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_image_settings", "newsletter/templates/blocks/image/settings.hbs");
        // line 139
        yield "
  ";
        // line 140
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_block", "newsletter/templates/blocks/posts/block.hbs");
        // line 143
        yield "
  ";
        // line 144
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_widget", "newsletter/templates/blocks/posts/widget.hbs");
        // line 147
        yield "
  ";
        // line 148
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings", "newsletter/templates/blocks/posts/settings.hbs");
        // line 151
        yield "
  ";
        // line 152
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_display_options", "newsletter/templates/blocks/posts/settingsDisplayOptions.hbs");
        // line 155
        yield "
  ";
        // line 156
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_selection", "newsletter/templates/blocks/posts/settingsSelection.hbs");
        // line 159
        yield "
  ";
        // line 160
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_selection_empty", "newsletter/templates/blocks/posts/settingsSelectionEmpty.hbs");
        // line 163
        yield "
  ";
        // line 164
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_posts_settings_single_post", "newsletter/templates/blocks/posts/settingsSinglePost.hbs");
        // line 167
        yield "
  ";
        // line 168
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_block", "newsletter/templates/blocks/products/block.hbs");
        // line 171
        yield "
  ";
        // line 172
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_widget", "newsletter/templates/blocks/products/widget.hbs");
        // line 175
        yield "
  ";
        // line 176
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings", "newsletter/templates/blocks/products/settings.hbs");
        // line 179
        yield "
  ";
        // line 180
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_display_options", "newsletter/templates/blocks/products/settingsDisplayOptions.hbs");
        // line 183
        yield "
  ";
        // line 184
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_selection", "newsletter/templates/blocks/products/settingsSelection.hbs");
        // line 187
        yield "
  ";
        // line 188
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_selection_empty", "newsletter/templates/blocks/products/settingsSelectionEmpty.hbs");
        // line 191
        yield "
  ";
        // line 192
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_products_settings_single_post", "newsletter/templates/blocks/products/settingsSinglePost.hbs");
        // line 195
        yield "
  ";
        // line 196
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_block", "newsletter/templates/blocks/abandonedCartContent/block.hbs");
        // line 199
        yield "
  ";
        // line 200
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_widget", "newsletter/templates/blocks/abandonedCartContent/widget.hbs");
        // line 203
        yield "
  ";
        // line 204
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_settings", "newsletter/templates/blocks/abandonedCartContent/settings.hbs");
        // line 207
        yield "
  ";
        // line 208
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_acc_settings_display_options", "newsletter/templates/blocks/abandonedCartContent/settingsDisplayOptions.hbs");
        // line 211
        yield "
  ";
        // line 212
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_block", "newsletter/templates/blocks/social/block.hbs");
        // line 215
        yield "
  ";
        // line 216
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_block_icon", "newsletter/templates/blocks/social/blockIcon.hbs");
        // line 219
        yield "
  ";
        // line 220
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_widget", "newsletter/templates/blocks/social/widget.hbs");
        // line 223
        yield "
  ";
        // line 224
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings", "newsletter/templates/blocks/social/settings.hbs");
        // line 227
        yield "
  ";
        // line 228
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_icon", "newsletter/templates/blocks/social/settingsIcon.hbs");
        // line 231
        yield "
  ";
        // line 232
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_icon_selector", "newsletter/templates/blocks/social/settingsIconSelector.hbs");
        // line 235
        yield "
  ";
        // line 236
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_social_settings_styles", "newsletter/templates/blocks/social/settingsStyles.hbs");
        // line 239
        yield "
  ";
        // line 240
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_block", "newsletter/templates/blocks/spacer/block.hbs");
        // line 243
        yield "
  ";
        // line 244
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_widget", "newsletter/templates/blocks/spacer/widget.hbs");
        // line 247
        yield "
  ";
        // line 248
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_spacer_settings", "newsletter/templates/blocks/spacer/settings.hbs");
        // line 251
        yield "
  ";
        // line 252
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_block", "newsletter/templates/blocks/text/block.hbs");
        // line 255
        yield "
  ";
        // line 256
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_widget", "newsletter/templates/blocks/text/widget.hbs");
        // line 259
        yield "
  ";
        // line 260
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_text_settings", "newsletter/templates/blocks/text/settings.hbs");
        // line 263
        yield "
  ";
        // line 264
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_heading", "newsletter/templates/components/heading.hbs");
        // line 267
        yield "
  ";
        // line 268
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_history", "newsletter/templates/components/history.hbs");
        // line 271
        yield "
  ";
        // line 272
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_save", "newsletter/templates/components/save.hbs");
        // line 275
        yield "
  ";
        // line 276
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_styles", "newsletter/templates/components/styles.hbs");
        // line 279
        yield "
  ";
        // line 280
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_newsletter_preview", "newsletter/templates/components/newsletterPreview.hbs");
        // line 283
        yield "
  ";
        // line 284
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar", "newsletter/templates/components/sidebar/sidebar.hbs");
        // line 287
        yield "
  ";
        // line 288
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_content", "newsletter/templates/components/sidebar/content.hbs");
        // line 291
        yield "
  ";
        // line 292
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_layout", "newsletter/templates/components/sidebar/layout.hbs");
        // line 295
        yield "
  ";
        // line 296
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_sidebar_styles", "newsletter/templates/components/sidebar/styles.hbs");
        // line 299
        yield "
  ";
        // line 300
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_new_account_content", "newsletter/templates/blocks/woocommerceContent/new_account.hbs");
        // line 303
        yield "
  ";
        // line 304
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_processing_order_content", "newsletter/templates/blocks/woocommerceContent/processing_order.hbs");
        // line 307
        yield "
  ";
        // line 308
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_completed_order_content", "newsletter/templates/blocks/woocommerceContent/completed_order.hbs");
        // line 311
        yield "
  ";
        // line 312
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_customer_note_content", "newsletter/templates/blocks/woocommerceContent/customer_note.hbs");
        // line 315
        yield "
  ";
        // line 316
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_new_account_content_improved", "newsletter/templates/blocks/woocommerceContentImproved/new_account.hbs");
        // line 319
        yield "
  ";
        // line 320
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_processing_order_content_improved", "newsletter/templates/blocks/woocommerceContentImproved/processing_order.hbs");
        // line 323
        yield "
  ";
        // line 324
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_completed_order_content_improved", "newsletter/templates/blocks/woocommerceContentImproved/completed_order.hbs");
        // line 327
        yield "
  ";
        // line 328
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_customer_note_content_improved", "newsletter/templates/blocks/woocommerceContentImproved/customer_note.hbs");
        // line 331
        yield "
  ";
        // line 332
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_content_widget", "newsletter/templates/blocks/woocommerceContent/widget.hbs");
        // line 335
        yield "
  ";
        // line 336
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_heading_block", "newsletter/templates/blocks/woocommerceHeading/block.hbs");
        // line 339
        yield "
  ";
        // line 340
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_woocommerce_heading_widget", "newsletter/templates/blocks/woocommerceHeading/widget.hbs");
        // line 343
        yield "
  ";
        // line 344
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_unknown_block_fallback_block", "newsletter/templates/blocks/unknownBlockFallback/block.hbs");
        // line 347
        yield "
  ";
        // line 348
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_unknown_block_fallback_widget", "newsletter/templates/blocks/unknownBlockFallback/widget.hbs");
        // line 351
        yield "
  ";
        // line 352
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_block", "newsletter/templates/blocks/coupon/block.hbs");
        // line 355
        yield "
  ";
        // line 356
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_widget", "newsletter/templates/blocks/coupon/widget.hbs");
        // line 359
        yield "
  ";
        // line 360
        yield $this->extensions['MailPoet\Twig\Handlebars']->generatePartial($this->env, $context, "newsletter_editor_template_coupon_settings", "newsletter/templates/blocks/coupon/settings.hbs");
        // line 363
        yield "
";
        return; yield '';
    }

    // line 366
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 367
        yield "<!-- Hidden heading for notices to appear under -->
<h1 style=\"display:none\">";
        // line 368
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Newsletter Editor");
        yield "</h1>
<div id=\"mailpoet_editor\">
  <div id=\"mailpoet_editor_steps_heading\"></div>
  <div class=\"clearfix\"></div>
  <div id=\"mailpoet_editor_heading_left\">
    <div id=\"mailpoet_editor_heading\"></div>
  </div>
  <div id=\"mailpoet_editor_heading_right\">
    <div id=\"mailpoet_editor_top\"></div>
  </div>
  <div class=\"clearfix\"></div>
  <div id=\"mailpoet_editor_main_wrapper\">
    <div id=\"mailpoet_editor_styles\"></div>
    <div id=\"mailpoet_editor_content_container\">
      <div class=\"mailpoet_newsletter_wrapper\">
        <div id=\"mailpoet_editor_content\"></div>
      </div>
    </div>
    <div id=\"mailpoet_editor_sidebar\"></div>
    <div class=\"clear\"></div>
  </div>
  <div id=\"mailpoet_editor_bottom\"></div>

  <div class=\"mailpoet_layer_overlay\" style=\"display:none;\"></div>
</div>

<script type=\"text/javascript\">
  var mailpoet_email_editor_tutorial_seen  = \x27";
        // line 395
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["editor_tutorial_seen"] ?? null), "html", null, true);
        yield "\x27;
  var mailpoet_email_editor_tutorial_url = \x27";
        // line 396
        yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("newsletter-editor/editor-drag-demo.20190226-1505.mp4");
        yield "\x27;
  var mailpoet_installed_at = \x27";
        // line 397
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "installed_at", [], "any", false, false, false, 397), "html", null, true);
        yield "\x27;
  var mailpoet_newsletters_api = ";
        // line 398
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["api"] ?? null));
        yield ";
</script>

";
        // line 401
        if (CoreExtension::getAttribute($this->env, $this->source, ($context["brand_styles"] ?? null), "available", [], "any", false, false, false, 401)) {
            // line 402
            yield "<script type=\"text/javascript\">
  var mailpoet_brand_styles = {
    available: true
  };
</script>
";
        }
        // line 408
        yield "
";
        return; yield '';
    }

    // line 411
    public function block_after_javascript($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 412
        yield "
  ";
        // line 413
        yield do_action("mailpoet_newsletter_editor_after_javascript");
        yield "

  <script type=\"text/javascript\">
    function renderWithFont(node) {
      if (!node.element) return node.text;
      var \$wrapper = jQuery(\x27<span></span>\x27);
      \$wrapper.css({\x27font-family\x27: Handlebars.helpers.fontWithFallback(node.element.value)});
      \$wrapper.text(node.text);
      return \$wrapper;
    }
    function fontsSelect(selector) {
      jQuery(selector).select2({
        minimumResultsForSearch: Infinity,
        templateSelection: renderWithFont,
        templateResult: renderWithFont
      });
    }

    var templates = {
      styles: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_styles\x27).html()
      ),
      save: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_save\x27).html()
      ),
      heading: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_heading\x27).html()
      ),
      history: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_history\x27).html()
      ),

      sidebar: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_sidebar\x27).html()
      ),
      sidebarContent: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_sidebar_content\x27).html()
      ),
      sidebarLayout: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_sidebar_layout\x27).html()
      ),
      sidebarStyles: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_sidebar_styles\x27).html()
      ),
      newsletterPreview: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_newsletter_preview\x27).html()
      ),

      genericBlockTools: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_tools_generic\x27).html()
      ),

      containerBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_block\x27).html()
      ),
      containerEmpty: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_block_empty\x27).html()
      ),
      oneColumnLayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_one_column_widget\x27).html()
      ),
      twoColumnLayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_two_column_widget\x27).html()
      ),
      twoColumn12LayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_two_column_12_widget\x27).html()
      ),
      twoColumn21LayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_two_column_21_widget\x27).html()
      ),
      threeColumnLayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_three_column_widget\x27).html()
      ),
      containerBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_settings\x27).html()
      ),
      containerBlockColumnSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_container_column_settings\x27).html()
      ),

      buttonBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_button_block\x27).html()
      ),
      buttonInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_button_widget\x27).html()
      ),
      buttonBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_button_settings\x27).html()
      ),

      dividerBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_divider_block\x27).html()
      ),
      dividerInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_divider_widget\x27).html()
      ),
      dividerBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_divider_settings\x27).html()
      ),

      footerBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_footer_block\x27).html()
      ),
      footerInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_footer_widget\x27).html()
      ),
      footerBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_footer_settings\x27).html()
      ),

      headerBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_header_block\x27).html()
      ),
      headerInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_header_widget\x27).html()
      ),
      headerBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_header_settings\x27).html()
      ),

      imageBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_image_block\x27).html()
      ),
      imageInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_image_widget\x27).html()
      ),
      imageBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_image_settings\x27).html()
      ),

      socialBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_block\x27).html()
      ),
      socialIconBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_block_icon\x27).html()
      ),
      socialInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_widget\x27).html()
      ),
      socialBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_settings\x27).html()
      ),
      socialSettingsIconSelector: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_settings_icon_selector\x27).html()
      ),
      socialSettingsIcon: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_settings_icon\x27).html()
      ),
      socialSettingsStyles: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_social_settings_styles\x27).html()
      ),

      spacerBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_spacer_block\x27).html()
      ),
      spacerInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_spacer_widget\x27).html()
      ),
      spacerBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_spacer_settings\x27).html()
      ),

      automatedLatestContentBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_block\x27).html()
      ),
      automatedLatestContentInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_widget\x27).html()
      ),
      automatedLatestContentBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_settings\x27).html()
      ),

      automatedLatestContentLayoutBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_layout_block\x27).html()
      ),
      automatedLatestContentLayoutInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_layout_widget\x27).html()
      ),
      automatedLatestContentLayoutBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_automated_latest_content_layout_settings\x27).html()
      ),

      postsBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_block\x27).html()
      ),
      postsInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_widget\x27).html()
      ),
      postsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_settings\x27).html()
      ),
      postSelectionPostsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_settings_selection\x27).html()
      ),
      emptyPostPostsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_settings_selection_empty\x27).html()
      ),
      singlePostPostsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_settings_single_post\x27).html()
      ),
      displayOptionsPostsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_posts_settings_display_options\x27).html()
      ),

      productsBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_block\x27).html()
      ),
      productsInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_widget\x27).html()
      ),
      productsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_settings\x27).html()
      ),
      postSelectionProductsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_settings_selection\x27).html()
      ),
      emptyPostProductsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_settings_selection_empty\x27).html()
      ),
      singlePostProductsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_settings_single_post\x27).html()
      ),
      displayOptionsProductsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_products_settings_display_options\x27).html()
      ),

      abandonedCartContentBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_acc_block\x27).html()
      ),
      abandonedCartContentInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_acc_widget\x27).html()
      ),
      abandonedCartContentBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_acc_settings\x27).html()
      ),
      displayOptionsAbandonedCartContentBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_acc_settings_display_options\x27).html()
      ),

      textBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_text_block\x27).html()
      ),
      textInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_text_widget\x27).html()
      ),
      textBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_text_settings\x27).html()
      ),

      woocommerceNewAccount: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_new_account_content\x27).html()
      ),
      woocommerceProcessingOrder: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_processing_order_content\x27).html()
      ),
      woocommerceCompletedOrder: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_completed_order_content\x27).html()
      ),
      woocommerceCustomerNote: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_customer_note_content\x27).html()
      ),
      woocommerceContentInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_content_widget\x27).html()
      ),

      woocommerceNewAccountImproved: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_new_account_content_improved\x27).html()
      ),
      woocommerceProcessingOrderImproved: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_processing_order_content_improved\x27).html()
      ),
      woocommerceCompletedOrderImproved: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_completed_order_content_improved\x27).html()
      ),
      woocommerceCustomerNoteImproved: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_customer_note_content_improved\x27).html()
      ),

      woocommerceHeadingBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_heading_block\x27).html()
      ),
      woocommerceHeadingInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_woocommerce_heading_widget\x27).html()
      ),

      unknownBlockFallbackBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_unknown_block_fallback_block\x27).html()
      ),
      unknownBlockFallbackInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_unknown_block_fallback_widget\x27).html()
      ),
      couponBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_coupon_block\x27).html()
      ),
      couponInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_coupon_widget\x27).html()
      ),
      couponBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_coupon_settings\x27).html()
      ),

      dynamicProductsBlock: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_dynamic_products_block\x27).html()
      ),
      dynamicProductsInsertion: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_dynamic_products_widget\x27).html()
      ),
      dynamicProductsBlockSettings: Handlebars.compile(
        jQuery(\x27#newsletter_editor_template_dynamic_products_settings\x27).html()
      ),
    };

    var currentUserEmail = \x27";
        // line 725
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["current_wp_user"] ?? null), "user_email", [], "any", false, false, false, 725), "html", null, true);
        yield "\x27;
    var mailpoet_product_categories = ";
        // line 726
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["product_categories"] ?? null));
        yield ";
    var mailpoet_products = ";
        // line 727
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["products"] ?? null));
        yield ";
    var mailpoet_original_template_body = ";
        // line 728
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["original_template_body"] ?? null));
        yield ";

    var config = {
      availableStyles: {
        textSizes: [
          \x279px\x27, \x2710px\x27, \x2711px\x27, \x2712px\x27, \x2713px\x27, \x2714px\x27, \x2715px\x27, \x2716px\x27,
          \x2717px\x27, \x2718px\x27, \x2719px\x27, \x2720px\x27, \x2721px\x27, \x2722px\x27, \x2723px\x27, \x2724px\x27
        ],
        headingSizes: [
          \x2710px\x27, \x2712px\x27, \x2714px\x27, \x2716px\x27, \x2718px\x27, \x2720px\x27, \x2722px\x27, \x2724px\x27,
          \x2726px\x27, \x2730px\x27, \x2736px\x27, \x2740px\x27
        ],
        lineHeights: [
          \x271.0\x27,
          \x271.2\x27,
          \x271.4\x27,
          \x271.6\x27,
          \x271.8\x27,
          \x272.0\x27,
        ],
        fonts: {
          standard: [
            \x27Arial\x27,
            \x27Comic Sans MS\x27,
            \x27Courier New\x27,
            \x27Georgia\x27,
            \x27Lucida\x27,
            \x27Tahoma\x27,
            \x27Times New Roman\x27,
            \x27Trebuchet MS\x27,
            \x27Verdana\x27
            ";
        // line 759
        if (($context["customFontsEnabled"] ?? null)) {
            // line 760
            yield "          ],
          custom: [
            \x27Arvo\x27,
            \x27Lato\x27,
            \x27Lora\x27,
            \x27Merriweather\x27,
            \x27Merriweather Sans\x27,
            \x27Noticia Text\x27,
            \x27Open Sans\x27,
            \x27Playfair Display\x27,
            \x27Roboto\x27,
            \x27Source Sans Pro\x27,
            \x27Oswald\x27,
            \x27Raleway\x27,
            \x27Permanent Marker\x27,
            \x27Pacifico\x27,
            ";
        }
        // line 777
        yield "          ]
        },
        socialIconSets: {
          \x27official\x27: {
            \x27custom\x27: \x27";
        // line 781
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Website.png");
        // line 783
        yield "\x27,
            \x27behance\x27: \x27";
        // line 784
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Behance.png");
        // line 786
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 787
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Bluesky.png");
        // line 789
        yield "\x27,
            \x27discord\x27: \x27";
        // line 790
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Discord.png");
        // line 792
        yield "\x27,
            \x27email\x27: \x27";
        // line 793
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Email.png");
        // line 795
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 796
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Facebook.png");
        // line 798
        yield "\x27,
            \x27feed\x27: \x27";
        // line 799
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Feed.png");
        // line 801
        yield "\x27,
            \x27github\x27: \x27";
        // line 802
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Github.png");
        // line 804
        yield "\x27,
            \x27gravatar\x27: \x27";
        // line 805
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Gravatar.png");
        // line 807
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 808
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Instagram.png");
        // line 810
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 811
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Linkedin.png");
        // line 813
        yield "\x27,
            \x27mastodon\x27: \x27";
        // line 814
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Mastodon.png");
        // line 816
        yield "\x27,
            \x27medium\x27: \x27";
        // line 817
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Medium.png");
        // line 819
        yield "\x27,
            \x27patreon\x27: \x27";
        // line 820
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Patreon.png");
        // line 822
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 823
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Pinterest.png");
        // line 825
        yield "\x27,
            \x27reddit\x27: \x27";
        // line 826
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Reddit.png");
        // line 828
        yield "\x27,
            \x27spotify\x27: \x27";
        // line 829
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Spotify.png");
        // line 831
        yield "\x27,
            \x27telegram\x27: \x27";
        // line 832
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Telegram.png");
        // line 834
        yield "\x27,
            \x27threads\x27: \x27";
        // line 835
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Threads.png");
        // line 837
        yield "\x27,
            \x27tiktok\x27: \x27";
        // line 838
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Tiktok.png");
        // line 840
        yield "\x27,
            \x27tumblr\x27: \x27";
        // line 841
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Tumblr.png");
        // line 843
        yield "\x27,
            \x27twitch\x27: \x27";
        // line 844
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Twitch.png");
        // line 846
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 847
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Twitter.png");
        // line 849
        yield "\x27,
            \x27vimeo\x27: \x27";
        // line 850
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Vimeo.png");
        // line 852
        yield "\x27,
            \x27website\x27: \x27";
        // line 853
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Website.png");
        // line 855
        yield "\x27,
            \x27whatsapp\x27: \x27";
        // line 856
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Whatsapp.png");
        // line 858
        yield "\x27,
            \x27wordpress\x27: \x27";
        // line 859
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Wordpress.png");
        // line 861
        yield "\x27,
            \x27x\x27: \x27";
        // line 862
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/X.png");
        // line 864
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 865
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/11-official/Youtube.png");
        // line 867
        yield "\x27
          },
          \x27official-white\x27: {
            \x27custom\x27: \x27";
        // line 870
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Website.png");
        // line 872
        yield "\x27,
            \x27behance\x27: \x27";
        // line 873
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Behance.png");
        // line 875
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 876
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Bluesky.png");
        // line 878
        yield "\x27,
            \x27discord\x27: \x27";
        // line 879
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Discord.png");
        // line 881
        yield "\x27,
            \x27email\x27: \x27";
        // line 882
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Email.png");
        // line 884
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 885
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Facebook.png");
        // line 887
        yield "\x27,
            \x27feed\x27: \x27";
        // line 888
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Feed.png");
        // line 890
        yield "\x27,
            \x27github\x27: \x27";
        // line 891
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Github.png");
        // line 893
        yield "\x27,
            \x27gravatar\x27: \x27";
        // line 894
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Gravatar.png");
        // line 896
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 897
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Instagram.png");
        // line 899
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 900
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Linkedin.png");
        // line 902
        yield "\x27,
            \x27mastodon\x27: \x27";
        // line 903
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Mastodon.png");
        // line 905
        yield "\x27,
            \x27medium\x27: \x27";
        // line 906
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Medium.png");
        // line 908
        yield "\x27,
            \x27patreon\x27: \x27";
        // line 909
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Patreon.png");
        // line 911
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 912
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Pinterest.png");
        // line 914
        yield "\x27,
            \x27reddit\x27: \x27";
        // line 915
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Reddit.png");
        // line 917
        yield "\x27,
            \x27spotify\x27: \x27";
        // line 918
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Spotify.png");
        // line 920
        yield "\x27,
            \x27telegram\x27: \x27";
        // line 921
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Telegram.png");
        // line 923
        yield "\x27,
            \x27threads\x27: \x27";
        // line 924
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Threads.png");
        // line 926
        yield "\x27,
            \x27tiktok\x27: \x27";
        // line 927
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Tiktok.png");
        // line 929
        yield "\x27,
            \x27tumblr\x27: \x27";
        // line 930
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Tumblr.png");
        // line 932
        yield "\x27,
            \x27twitch\x27: \x27";
        // line 933
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Twitch.png");
        // line 935
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 936
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Twitter.png");
        // line 938
        yield "\x27,
            \x27vimeo\x27: \x27";
        // line 939
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Vimeo.png");
        // line 941
        yield "\x27,
            \x27website\x27: \x27";
        // line 942
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Website.png");
        // line 944
        yield "\x27,
            \x27whatsapp\x27: \x27";
        // line 945
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Whatsapp.png");
        // line 947
        yield "\x27,
            \x27wordpress\x27: \x27";
        // line 948
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Wordpress.png");
        // line 950
        yield "\x27,
            \x27x\x27: \x27";
        // line 951
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/X.png");
        // line 953
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 954
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/12-official-white/Youtube.png");
        // line 956
        yield "\x27
          },
          \x27default\x27: {
            \x27custom\x27: \x27";
        // line 959
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 961
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 962
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Facebook.png");
        // line 964
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 965
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Twitter.png");
        // line 967
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 968
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Youtube.png");
        // line 970
        yield "\x27,
            \x27website\x27: \x27";
        // line 971
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Website.png");
        // line 973
        yield "\x27,
            \x27email\x27: \x27";
        // line 974
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Email.png");
        // line 976
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 977
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Instagram.png");
        // line 979
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 980
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Pinterest.png");
        // line 982
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 983
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/LinkedIn.png");
        // line 985
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 986
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Bluesky.png");
        // line 988
        yield "\x27
          },
          \x27grey\x27: {
            \x27custom\x27: \x27";
        // line 991
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 993
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 994
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Facebook.png");
        // line 996
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 997
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Twitter.png");
        // line 999
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1000
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Youtube.png");
        // line 1002
        yield "\x27,
            \x27website\x27: \x27";
        // line 1003
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Website.png");
        // line 1005
        yield "\x27,
            \x27email\x27: \x27";
        // line 1006
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Email.png");
        // line 1008
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1009
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Instagram.png");
        // line 1011
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1012
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Pinterest.png");
        // line 1014
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1015
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/LinkedIn.png");
        // line 1017
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1018
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/02-grey/Bluesky.png");
        // line 1020
        yield "\x27,
          },
          \x27white\x27: {
              \x27custom\x27: \x27";
        // line 1023
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1025
        yield "\x27,
              \x27facebook\x27: \x27";
        // line 1026
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Facebook.png");
        // line 1028
        yield "\x27,
              \x27twitter\x27: \x27";
        // line 1029
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Twitter.png");
        // line 1031
        yield "\x27,
              \x27youtube\x27: \x27";
        // line 1032
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Youtube.png");
        // line 1034
        yield "\x27,
              \x27website\x27: \x27";
        // line 1035
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Website.png");
        // line 1037
        yield "\x27,
              \x27email\x27: \x27";
        // line 1038
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Email.png");
        // line 1040
        yield "\x27,
              \x27instagram\x27: \x27";
        // line 1041
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Instagram.png");
        // line 1043
        yield "\x27,
              \x27pinterest\x27: \x27";
        // line 1044
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Pinterest.png");
        // line 1046
        yield "\x27,
              \x27linkedin\x27: \x27";
        // line 1047
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/LinkedIn.png");
        // line 1049
        yield "\x27,
              \x27bluesky\x27: \x27";
        // line 1050
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/09-white/Bluesky.png");
        // line 1052
        yield "\x27,
          },
          \x27black\x27: {
              \x27custom\x27: \x27";
        // line 1055
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1057
        yield "\x27,
              \x27facebook\x27: \x27";
        // line 1058
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Facebook.png");
        // line 1060
        yield "\x27,
              \x27twitter\x27: \x27";
        // line 1061
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Twitter.png");
        // line 1063
        yield "\x27,
              \x27youtube\x27: \x27";
        // line 1064
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Youtube.png");
        // line 1066
        yield "\x27,
              \x27website\x27: \x27";
        // line 1067
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Website.png");
        // line 1069
        yield "\x27,
              \x27email\x27: \x27";
        // line 1070
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Email.png");
        // line 1072
        yield "\x27,
              \x27instagram\x27: \x27";
        // line 1073
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Instagram.png");
        // line 1075
        yield "\x27,
              \x27pinterest\x27: \x27";
        // line 1076
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Pinterest.png");
        // line 1078
        yield "\x27,
              \x27linkedin\x27: \x27";
        // line 1079
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/LinkedIn.png");
        // line 1081
        yield "\x27,
              \x27bluesky\x27: \x27";
        // line 1082
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/10-black/Bluesky.png");
        // line 1084
        yield "\x27,
          },
          \x27circles\x27: {
            \x27custom\x27: \x27";
        // line 1087
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1089
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1090
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Facebook.png");
        // line 1092
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1093
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Twitter.png");
        // line 1095
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1096
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Youtube.png");
        // line 1098
        yield "\x27,
            \x27website\x27: \x27";
        // line 1099
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Website.png");
        // line 1101
        yield "\x27,
            \x27email\x27: \x27";
        // line 1102
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Email.png");
        // line 1104
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1105
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Instagram.png");
        // line 1107
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1108
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Pinterest.png");
        // line 1110
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1111
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/LinkedIn.png");
        // line 1113
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1114
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/03-circles/Bluesky.png");
        // line 1116
        yield "\x27,
          },
          \x27full-flat-roundrect\x27: {
            \x27custom\x27: \x27";
        // line 1119
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1121
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1122
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Facebook.png");
        // line 1124
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1125
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Twitter.png");
        // line 1127
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1128
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Youtube.png");
        // line 1130
        yield "\x27,
            \x27website\x27: \x27";
        // line 1131
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Website.png");
        // line 1133
        yield "\x27,
            \x27email\x27: \x27";
        // line 1134
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Email.png");
        // line 1136
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1137
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Instagram.png");
        // line 1139
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1140
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Pinterest.png");
        // line 1142
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1143
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/LinkedIn.png");
        // line 1145
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1146
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/04-full-flat-roundrect/Bluesky.png");
        // line 1148
        yield "\x27,
          },
          \x27full-gradient-square\x27: {
            \x27custom\x27: \x27";
        // line 1151
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1153
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1154
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Facebook.png");
        // line 1156
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1157
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Twitter.png");
        // line 1159
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1160
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Youtube.png");
        // line 1162
        yield "\x27,
            \x27website\x27: \x27";
        // line 1163
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Website.png");
        // line 1165
        yield "\x27,
            \x27email\x27: \x27";
        // line 1166
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Email.png");
        // line 1168
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1169
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Instagram.png");
        // line 1171
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1172
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Pinterest.png");
        // line 1174
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1175
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/LinkedIn.png");
        // line 1177
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1178
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/05-full-gradient-square/Bluesky.png");
        // line 1180
        yield "\x27,
          },
          \x27full-symbol-color\x27: {
            \x27custom\x27: \x27";
        // line 1183
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1185
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1186
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Facebook.png");
        // line 1188
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1189
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Twitter.png");
        // line 1191
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1192
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Youtube.png");
        // line 1194
        yield "\x27,
            \x27website\x27: \x27";
        // line 1195
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Website.png");
        // line 1197
        yield "\x27,
            \x27email\x27: \x27";
        // line 1198
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Email.png");
        // line 1200
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1201
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Instagram.png");
        // line 1203
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1204
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Pinterest.png");
        // line 1206
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1207
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/LinkedIn.png");
        // line 1209
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1210
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/06-full-symbol-color/Bluesky.png");
        // line 1212
        yield "\x27,
          },
          \x27full-symbol-black\x27: {
            \x27custom\x27: \x27";
        // line 1215
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1217
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1218
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Facebook.png");
        // line 1220
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1221
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Twitter.png");
        // line 1223
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1224
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Youtube.png");
        // line 1226
        yield "\x27,
            \x27website\x27: \x27";
        // line 1227
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Website.png");
        // line 1229
        yield "\x27,
            \x27email\x27: \x27";
        // line 1230
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Email.png");
        // line 1232
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1233
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Instagram.png");
        // line 1235
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1236
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Pinterest.png");
        // line 1238
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1239
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/LinkedIn.png");
        // line 1241
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1242
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/07-full-symbol-black/Bluesky.png");
        // line 1244
        yield "\x27,
          },
          \x27full-symbol-grey\x27: {
            \x27custom\x27: \x27";
        // line 1247
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/custom.png");
        // line 1249
        yield "\x27,
            \x27facebook\x27: \x27";
        // line 1250
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Facebook.png");
        // line 1252
        yield "\x27,
            \x27twitter\x27: \x27";
        // line 1253
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Twitter.png");
        // line 1255
        yield "\x27,
            \x27youtube\x27: \x27";
        // line 1256
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Youtube.png");
        // line 1258
        yield "\x27,
            \x27website\x27: \x27";
        // line 1259
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Website.png");
        // line 1261
        yield "\x27,
            \x27email\x27: \x27";
        // line 1262
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Email.png");
        // line 1264
        yield "\x27,
            \x27instagram\x27: \x27";
        // line 1265
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Instagram.png");
        // line 1267
        yield "\x27,
            \x27pinterest\x27: \x27";
        // line 1268
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Pinterest.png");
        // line 1270
        yield "\x27,
            \x27linkedin\x27: \x27";
        // line 1271
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/LinkedIn.png");
        // line 1273
        yield "\x27,
            \x27bluesky\x27: \x27";
        // line 1274
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/08-full-symbol-grey/Bluesky.png");
        // line 1276
        yield "\x27,
          },
        },
        dividers: [
          \x27hidden\x27,
          \x27dotted\x27,
          \x27dashed\x27,
          \x27solid\x27,
          \x27double\x27,
          \x27groove\x27,
          \x27ridge\x27
        ]
      },
      socialIcons: {
        \x27behance\x27: {
          title: \x27Behance\x27,
          defaultLink: \x27https://www.behance.net/\x27,
        },
        \x27bluesky\x27: {
          title: \x27Bluesky\x27,
          defaultLink: \x27https://bsky.app/\x27,
        },
        \x27custom\x27: {
          title: \x27";
        // line 1299
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Custom"), "js"), "html", null, true);
        yield "\x27,
          defaultLink: \x27\x27,
        },
        \x27discord\x27: {
          title: \x27Discord\x27,
          defaultLink: \x27https://discord.com/\x27,
        },
        \x27email\x27: {
          title: \x27";
        // line 1307
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Email"), "js"), "html", null, true);
        yield "\x27,
          linkFieldName: \x27";
        // line 1308
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Email"), "js"), "html", null, true);
        yield "\x27,
          defaultLink: \x27\x27,
        },
        \x27facebook\x27: {
          title: \x27Facebook\x27,
          defaultLink: \x27https://www.facebook.com/\x27,
        },
        \x27feed\x27: {
          title: \x27";
        // line 1316
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("RSS Feed"), "js"), "html", null, true);
        yield "\x27,
          defaultLink: \x27\x27,
        },
        \x27github\x27: {
          title: \x27GitHub\x27,
          defaultLink: \x27https://github.com/\x27,
        },
        \x27gravatar\x27: {
          title: \x27Gravatar\x27,
          defaultLink: \x27https://gravatar.com/\x27,
        },
        \x27instagram\x27: {
          title: \x27Instagram\x27,
          defaultLink: \x27https://www.instagram.com/\x27,
        },
        \x27linkedin\x27: {
          title: \x27LinkedIn\x27,
          defaultLink: \x27https://www.linkedin.com/\x27,
        },
        \x27mastodon\x27: {
          title: \x27Mastodon\x27,
          defaultLink: \x27https://mastodon.social/\x27,
        },
        \x27medium\x27: {
          title: \x27Medium\x27,
          defaultLink: \x27https://medium.com/\x27,
        },
        \x27patreon\x27: {
          title: \x27Patreon\x27,
          defaultLink: \x27https://www.patreon.com/\x27,
        },
        \x27pinterest\x27: {
          title: \x27Pinterest\x27,
          defaultLink: \x27https://www.pinterest.com/\x27,
        },
        \x27reddit\x27: {
          title: \x27Reddit\x27,
          defaultLink: \x27https://www.reddit.com/\x27,
        },
        \x27spotify\x27: {
          title: \x27Spotify\x27,
          defaultLink: \x27https://open.spotify.com/\x27,
        },
        \x27telegram\x27: {
          title: \x27Telegram\x27,
          defaultLink: \x27https://t.me/\x27,
        },
        \x27threads\x27: {
          title: \x27Threads\x27,
          defaultLink: \x27https://www.threads.net/\x27,
        },
        \x27tiktok\x27: {
          title: \x27TikTok\x27,
          defaultLink: \x27https://www.tiktok.com/\x27,
        },
        \x27tumblr\x27: {
          title: \x27Tumblr\x27,
          defaultLink: \x27https://www.tumblr.com/\x27,
        },
        \x27twitch\x27: {
          title: \x27Twitch\x27,
          defaultLink: \x27https://www.twitch.tv/\x27,
        },
        \x27twitter\x27: {
          title: \x27Twitter / X\x27,
          defaultLink: \x27https://x.com/\x27,
        },
        \x27vimeo\x27: {
          title: \x27Vimeo\x27,
          defaultLink: \x27https://vimeo.com/\x27,
        },
        \x27website\x27: {
          title: \x27";
        // line 1388
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Website"), "js"), "html", null, true);
        yield "\x27,
          defaultLink: \x27\x27,
        },
        \x27whatsapp\x27: {
          title: \x27WhatsApp\x27,
          defaultLink: \x27https://wa.me/\x27,
        },
        \x27wordpress\x27: {
          title: \x27WordPress\x27,
          defaultLink: \x27https://wordpress.com/\x27,
        },
        \x27x\x27: {
          title: \x27X\x27,
          defaultLink: \x27https://x.com/\x27,
        },
        \x27youtube\x27: {
          title: \x27YouTube\x27,
          defaultLink: \x27https://www.youtube.com/\x27,
        },
      },
      blockDefaults: {
        abandonedCartContent: {
          amount: \x272\x27,
          withLayout: true,
          contentType: \x27product\x27,
          postStatus: \x27publish\x27, // \x27draft\x27|\x27pending\x27|\x27publish\x27
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: \x27alternate\x27, // \x27centered\x27|\x27left\x27|\x27right\x27|\x27alternate\x27|\x27none\x27,
          pricePosition: \x27below\x27, // \x27hidden\x27|\x27above\x27|\x27below\x27
          readMoreType: \x27none\x27, // \x27link\x27|\x27button\x27|\x27none\x27
          readMoreText: \x27\x27,
          readMoreButton: {},
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          divider: {
            context: \x27abandonedCartContent.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        automatedLatestContent: {
          amount: \x275\x27,
          withLayout: false,
          contentType: \x27post\x27, // \x27post\x27|\x27page\x27|\x27mailpoet_page\x27
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27|\x27ul\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: \x27belowTitle\x27, // \x27belowTitle\x27|\x27aboveTitle\x27|\x27none\x27,
          showAuthor: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          authorPrecededBy: \x27";
        // line 1454
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "\x27,
          showCategories: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          categoriesPrecededBy: \x27";
        // line 1456
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "\x27,
          readMoreType: \x27button\x27, // \x27link\x27|\x27button\x27
          readMoreText: \x27";
        // line 1458
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
          readMoreButton: {
            text: \x27";
        // line 1460
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
            url: \x27[postLink]\x27,
            context: \x27automatedLatestContent.readMoreButton\x27,
            styles: {
              block: {
                backgroundColor: \x27#2ea1cd\x27,
                borderColor: \x27#0074a2\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27180px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#ffffff\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              }
            }
          },
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          divider: {
            context: \x27automatedLatestContent.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        automatedLatestContentLayout: {
          amount: \x275\x27,
          withLayout: true,
          contentType: \x27post\x27, // \x27post\x27|\x27page\x27|\x27mailpoet_page\x27
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27|\x27ul\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: \x27alternate\x27, // \x27centered\x27|\x27left\x27|\x27right\x27|\x27alternate\x27|\x27none\x27,
          showAuthor: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          authorPrecededBy: \x27";
        // line 1509
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "\x27,
          showCategories: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          categoriesPrecededBy: \x27";
        // line 1511
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "\x27,
          readMoreType: \x27button\x27, // \x27link\x27|\x27button\x27
          readMoreText: \x27";
        // line 1513
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
          readMoreButton: {
            text: \x27";
        // line 1515
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
            url: \x27[postLink]\x27,
            context: \x27automatedLatestContentLayout.readMoreButton\x27,
            styles: {
              block: {
                backgroundColor: \x27#2ea1cd\x27,
                borderColor: \x27#0074a2\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27180px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#ffffff\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              }
            }
          },
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          divider: {
            context: \x27automatedLatestContentLayout.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        button: {
          text: \x27";
        // line 1553
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Button"), "js"), "html", null, true);
        yield "\x27,
          url: \x27\x27,
          styles: {
            block: {
              backgroundColor: \x27#2ea1cd\x27,
              borderColor: \x27#0074a2\x27,
              borderWidth: \x271px\x27,
              borderRadius: \x275px\x27,
              borderStyle: \x27solid\x27,
              width: \x27180px\x27,
              lineHeight: \x2740px\x27,
              fontColor: \x27#ffffff\x27,
              fontFamily: \x27Verdana\x27,
              fontSize: \x2718px\x27,
              fontWeight: \x27normal\x27,
              textAlign: \x27center\x27,
            },
          },
        },
        container: {
          image: {
            src: null,
            display: \x27scale\x27,
          },
          styles: {
            block: {
              backgroundColor: \x27transparent\x27,
            },
          },
        },
        divider: {
          styles: {
            block: {
              backgroundColor: \x27transparent\x27,
              padding: \x2713px\x27,
              borderStyle: \x27solid\x27,
              borderWidth: \x273px\x27,
              borderColor: \x27#aaaaaa\x27,
            },
          },
        },
        dynamicProducts: {
          amount: \x2710\x27,
          withLayout: true,
          contentType: \x27product\x27,
          terms: [], // List of category and tag objects
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          excludeOutOfStock: true, // true|false
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          titlePosition: \x27abovePost\x27, // \x27abovePost\x27|\x27aboveExcerpt\x27
          featuredImagePosition: \x27left\x27, // \x27centered\x27|\x27right\x27|\x27left\x27|\x27alternate\x27|\x27none\x27
          pricePosition: \x27below\x27, // \x27hidden\x27|\x27above\x27|\x27below\x27
          readMoreType: \x27link\x27, // \x27link\x27|\x27button\x27
          readMoreText: \x27Buy now\x27,
          readMoreButton: {
            text: \x27Buy now\x27,
            url: \x27[postLink]\x27,
            context: \x27dynamicProducts.readMoreButton\x27,
            styles: {
              block: {
                backgroundColor: \x27#2ea1cd\x27,
                borderColor: \x27#0074a2\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27180px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#ffffff\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              }
            }
          },
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          dynamicProductsType: \x27selected\x27, // \x27cross-sell\x27|\x27order\x27|\x27selected\x27|\x27cart\x27
          divider: {
            context: \x27dynamicProducts.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        footer: {
          text: \x27<p><a href=\"[link:subscription_unsubscribe_url]\">";
        // line 1651
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Unsubscribe");
        yield "</a> | <a href=\"[link:subscription_manage_url]\">";
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Manage subscription");
        yield "</a><br />";
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Add your postal address here!");
        yield "</p>\x27,
          styles: {
            block: {
              backgroundColor: \x27transparent\x27,
            },
            text: {
              fontColor: \x27#222222\x27,
              fontFamily: \x27Arial\x27,
              fontSize: \x2712px\x27,
              textAlign: \x27center\x27,
            },
            link: {
              fontColor: \x27#6cb7d4\x27,
              textDecoration: \x27none\x27,
            },
          },
        },
        image: {
          link: \x27\x27,
          src: \x27\x27,
          alt: \x27";
        // line 1671
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("An image of..."), "js"), "html", null, true);
        yield "\x27,
          fullWidth: false,
          width: \x27281px\x27,
          height: \x27190px\x27,
          styles: {
            block: {
              textAlign: \x27center\x27,
            },
          },
        },
        posts: {
          amount: \x2710\x27,
          withLayout: true,
          contentType: \x27post\x27, // \x27post\x27|\x27page\x27|\x27mailpoet_page\x27
          postStatus: \x27publish\x27, // \x27draft\x27|\x27pending\x27|\x27private\x27|\x27publish\x27|\x27future\x27
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27|\x27ul\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: \x27alternate\x27, // \x27centered\x27|\x27left\x27|\x27right\x27|\x27alternate\x27|\x27none\x27,
          showAuthor: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          authorPrecededBy: \x27";
        // line 1694
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Author:"), "js"), "html", null, true);
        yield "\x27,
          showCategories: \x27no\x27, // \x27no\x27|\x27aboveText\x27|\x27belowText\x27
          categoriesPrecededBy: \x27";
        // line 1696
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Categories:"), "js"), "html", null, true);
        yield "\x27,
          readMoreType: \x27link\x27, // \x27link\x27|\x27button\x27
          readMoreText: \x27";
        // line 1698
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
          readMoreButton: {
            text: \x27";
        // line 1700
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Read more"), "js"), "html", null, true);
        yield "\x27,
            url: \x27[postLink]\x27,
            context: \x27posts.readMoreButton\x27,
            styles: {
              block: {
                backgroundColor: \x27#2ea1cd\x27,
                borderColor: \x27#0074a2\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27180px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#ffffff\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              },
            },
          },
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          divider: {
            context: \x27posts.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        products: {
          amount: \x2710\x27,
          withLayout: true,
          contentType: \x27product\x27,
          postStatus: \x27publish\x27, // \x27draft\x27|\x27pending\x27|\x27publish\x27
          inclusionType: \x27include\x27, // \x27include\x27|\x27exclude\x27
          displayType: \x27excerpt\x27, // \x27excerpt\x27|\x27full\x27|\x27titleOnly\x27
          titleFormat: \x27h1\x27, // \x27h1\x27|\x27h2\x27|\x27h3\x27
          titleAlignment: \x27left\x27, // \x27left\x27|\x27center\x27|\x27right\x27
          titleIsLink: false, // false|true
          imageFullWidth: false, // true|false
          featuredImagePosition: \x27alternate\x27, // \x27centered\x27|\x27left\x27|\x27right\x27|\x27alternate\x27|\x27none\x27,
          pricePosition: \x27below\x27, // \x27hidden\x27|\x27above\x27|\x27below\x27
          readMoreType: \x27link\x27, // \x27link\x27|\x27button\x27
          readMoreText: \x27";
        // line 1751
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translateWithContext("Buy now", "Text of a button which links to an ecommerce product page"), "js"), "html", null, true);
        yield "\x27,
          readMoreButton: {
            text: \x27";
        // line 1753
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translateWithContext("Buy now", "Text of a button which links to an ecommerce product page"), "js"), "html", null, true);
        yield "\x27,
            url: \x27[postLink]\x27,
            context: \x27posts.readMoreButton\x27,
            styles: {
              block: {
                backgroundColor: \x27#2ea1cd\x27,
                borderColor: \x27#0074a2\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27180px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#ffffff\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              },
            },
          },
          sortBy: \x27newest\x27, // \x27newest\x27|\x27oldest\x27,
          showDivider: true, // true|false
          divider: {
            context: \x27posts.divider\x27,
            styles: {
              block: {
                backgroundColor: \x27transparent\x27,
                padding: \x2713px\x27,
                borderStyle: \x27solid\x27,
                borderWidth: \x273px\x27,
                borderColor: \x27#aaaaaa\x27,
              },
            },
          },
          backgroundColor: \x27#ffffff\x27,
          backgroundColorAlternate: \x27#eeeeee\x27,
        },
        social: {
          iconSet: \x27default\x27,
          styles: {
            block: {
              textAlign: \x27center\x27
            }
          },
          icons: [
          {
            type: \x27socialIcon\x27,
            iconType: \x27facebook\x27,
            link: \x27http://www.facebook.com\x27,
            image: \x27";
        // line 1802
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Facebook.png");
        // line 1804
        yield "\x27,
            height: \x2732px\x27,
            width: \x2732px\x27,
            text: \x27";
        // line 1807
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Facebook"), "js"), "html", null, true);
        yield "\x27,
          },
          {
            type: \x27socialIcon\x27,
            iconType: \x27twitter\x27,
            link: \x27http://www.twitter.com\x27,
            image: \x27";
        // line 1813
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/social-icons/01-social/Twitter.png");
        // line 1815
        yield "\x27,
            height: \x2732px\x27,
            width: \x2732px\x27,
            text: \x27";
        // line 1818
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Twitter"), "js"), "html", null, true);
        yield "\x27,
          },
          ],
        },
        spacer: {
          styles: {
            block: {
              backgroundColor: \x27transparent\x27,
              height: \x2740px\x27,
            },
          },
        },
        text: {
          text: \x27";
        // line 1831
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->extensions['MailPoet\Twig\I18n']->translate("Edit this to insert text."), "js"), "html", null, true);
        yield "\x27,
        },
        header: {
          text: \x27<a href=\"[link:newsletter_view_in_browser_url]\">";
        // line 1834
        yield $this->extensions['MailPoet\Twig\I18n']->translate("View this in your browser.");
        yield "</a>\x27,
          styles: {
            block: {
              backgroundColor: \x27transparent\x27,
            },
            text: {
              fontColor: \x27#222222\x27,
              fontFamily: \x27Arial\x27,
              fontSize: \x2712px\x27,
              textAlign: \x27center\x27,
            },
            link: {
              fontColor: \x27#6cb7d4\x27,
              textDecoration: \x27underline\x27,
            },
          },
        },
        woocommerceHeading: {
          contents: ";
        // line 1852
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "email_headings", [], "any", false, false, false, 1852));
        yield ",
        },
        coupon: {
            type: \x27coupon\x27,
            discountType: ";
        // line 1856
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1856), "defaults", [], "any", false, false, false, 1856), "discountType", [], "any", false, false, false, 1856));
        yield ",
            code: ";
        // line 1857
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1857), "defaults", [], "any", false, false, false, 1857), "code", [], "any", false, false, false, 1857));
        yield ",
            amountMax: ";
        // line 1858
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1858), "defaults", [], "any", false, false, false, 1858), "amountMax", [], "any", false, false, false, 1858));
        yield ",
            amount: 10,
            expiryDay: 10,
            styles: {
              block: {
                backgroundColor: \x27#ffffff\x27,
                borderColor: \x27#000000\x27,
                borderWidth: \x271px\x27,
                borderRadius: \x275px\x27,
                borderStyle: \x27solid\x27,
                width: \x27200px\x27,
                lineHeight: \x2740px\x27,
                fontColor: \x27#000000\x27,
                fontFamily: \x27Verdana\x27,
                fontSize: \x2718px\x27,
                fontWeight: \x27normal\x27,
                textAlign: \x27center\x27,
              },
          },
        },
      },
      shortcodes: ";
        // line 1879
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["shortcodes"] ?? null));
        yield ",
      sidepanelWidth: \x27331px\x27,
      newsletterPreview: {
        width: \x271024px\x27,
        height: \x27768px\x27,
        previewTypeLocalStorageKey: \x27newsletter_editor.preview_type\x27
      },
      validation: {
        validateUnsubscribeLinkPresent: ";
        // line 1887
        yield ((((($context["mss_active"] ?? null) && (($context["is_wc_transactional_email"] ?? null) != true)) && (($context["is_confirmation_email_type"] ?? null) != true))) ? ("true") : ("false"));
        yield ",
        validateReEngageLinkPresent: ";
        // line 1888
        yield ((((($context["mss_active"] ?? null) && (($context["is_wc_transactional_email"] ?? null) != true)) && (($context["is_confirmation_email_type"] ?? null) != true))) ? ("true") : ("false"));
        yield ",
        validateActivationLinkIsPresent: ";
        // line 1889
        yield ((($context["is_confirmation_email_type"] ?? null)) ? ("true") : ("false"));
        yield ",
        ";
        // line 1891
        yield "        validateTrackingOptOutLinkPresent: ";
        yield ((((($context["is_tracking_consent_ask_all"] ?? null) && (($context["is_wc_transactional_email"] ?? null) != true)) && (($context["is_confirmation_email_type"] ?? null) != true))) ? ("true") : ("false"));
        yield ",
      },
      urls: {
        send: \x27";
        // line 1894
        yield admin_url(("admin.php?page=mailpoet-newsletters#/send/" . intval($this->extensions['MailPoet\Twig\Functions']->params("id"))));
        yield "\x27,
        imageMissing: \x27";
        // line 1895
        yield $this->extensions['MailPoet\Twig\Assets']->generateImageUrl("newsletter_editor/image-missing.svg");
        // line 1897
        yield "\x27,
      },
      currentUserId: \x27";
        // line 1899
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["current_wp_user"] ?? null), "wp_user_id", [], "any", false, false, false, 1899), "html", null, true);
        yield "\x27,
      mtaMethod: \x27";
        // line 1900
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape((($__internal_compile_0 = (($__internal_compile_1 = ($context["settings"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["mta"] ?? null) : null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["method"] ?? null) : null), "html", null, true);
        yield "\x27,
      woocommerceCustomizerEnabled: ";
        // line 1901
        yield ((CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "customizer_enabled", [], "any", false, false, false, 1901)) ? ("true") : ("false"));
        yield ",
      confirmationEmailCustomizerEnabled: ";
        // line 1902
        yield ((($context["is_confirmation_email_customizer_enabled"] ?? null)) ? ("true") : ("false"));
        yield ",
      ";
        // line 1903
        if (($context["is_wc_transactional_email"] ?? null)) {
            // line 1904
            yield "      overrideGlobalStyles: {
        text: {
          fontColor: ";
            // line 1906
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "text_color", [], "any", false, false, false, 1906));
            yield ",
        },
        h1: {
          fontColor: ";
            // line 1909
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1909));
            yield ",
        },
        h2: {
          fontColor: ";
            // line 1912
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1912));
            yield ",
        },
        h3: {
          fontColor: ";
            // line 1915
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1915));
            yield ",
        },
        link: {
          fontColor: ";
            // line 1918
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "link_color", [], "any", false, false, false, 1918));
            yield ",
        },
        wrapper: {
          backgroundColor: ";
            // line 1921
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "body_background_color", [], "any", false, false, false, 1921));
            yield ",
        },
        body: {
          backgroundColor: ";
            // line 1924
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "background_color", [], "any", false, false, false, 1924));
            yield ",
        },
        woocommerce: {
          brandingColor: ";
            // line 1927
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_color", [], "any", false, false, false, 1927));
            yield ",
          headingFontColor: ";
            // line 1928
            yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "base_text_color", [], "any", false, false, false, 1928));
            yield ",
          isSavedWithUpdatedStyles: true,
        },
      },
      hiddenWidgets: [\x27automatedLatestContentLayout\x27, \x27header\x27, \x27footer\x27, \x27posts\x27, \x27products\x27],
      ";
        }
        // line 1934
        yield "      ";
        if (($context["is_confirmation_email_type"] ?? null)) {
            // line 1935
            yield "      hiddenWidgets: [\x27automatedLatestContentLayout\x27, \x27coupon\x27, \x27header\x27, \x27footer\x27, \x27posts\x27, \x27products\x27, \x27dynamicProducts\x27],
      ";
        }
        // line 1937
        yield "      coupon: ";
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["woocommerce"] ?? null), "coupon", [], "any", false, false, false, 1937), "config", [], "any", false, false, false, 1937));
        yield ",
    };

    wp.hooks.doAction(\x27mailpoet_newsletters_editor_initialize\x27, config);

  </script>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "newsletter/editor.html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  2609 => 1937,  2605 => 1935,  2602 => 1934,  2593 => 1928,  2589 => 1927,  2583 => 1924,  2577 => 1921,  2571 => 1918,  2565 => 1915,  2559 => 1912,  2553 => 1909,  2547 => 1906,  2543 => 1904,  2541 => 1903,  2537 => 1902,  2533 => 1901,  2529 => 1900,  2525 => 1899,  2521 => 1897,  2519 => 1895,  2515 => 1894,  2508 => 1891,  2504 => 1889,  2500 => 1888,  2496 => 1887,  2485 => 1879,  2461 => 1858,  2457 => 1857,  2453 => 1856,  2446 => 1852,  2425 => 1834,  2419 => 1831,  2403 => 1818,  2398 => 1815,  2396 => 1813,  2387 => 1807,  2382 => 1804,  2380 => 1802,  2328 => 1753,  2323 => 1751,  2269 => 1700,  2264 => 1698,  2259 => 1696,  2254 => 1694,  2228 => 1671,  2201 => 1651,  2100 => 1553,  2059 => 1515,  2054 => 1513,  2049 => 1511,  2044 => 1509,  1992 => 1460,  1987 => 1458,  1982 => 1456,  1977 => 1454,  1908 => 1388,  1833 => 1316,  1822 => 1308,  1818 => 1307,  1807 => 1299,  1782 => 1276,  1780 => 1274,  1777 => 1273,  1775 => 1271,  1772 => 1270,  1770 => 1268,  1767 => 1267,  1765 => 1265,  1762 => 1264,  1760 => 1262,  1757 => 1261,  1755 => 1259,  1752 => 1258,  1750 => 1256,  1747 => 1255,  1745 => 1253,  1742 => 1252,  1740 => 1250,  1737 => 1249,  1735 => 1247,  1730 => 1244,  1728 => 1242,  1725 => 1241,  1723 => 1239,  1720 => 1238,  1718 => 1236,  1715 => 1235,  1713 => 1233,  1710 => 1232,  1708 => 1230,  1705 => 1229,  1703 => 1227,  1700 => 1226,  1698 => 1224,  1695 => 1223,  1693 => 1221,  1690 => 1220,  1688 => 1218,  1685 => 1217,  1683 => 1215,  1678 => 1212,  1676 => 1210,  1673 => 1209,  1671 => 1207,  1668 => 1206,  1666 => 1204,  1663 => 1203,  1661 => 1201,  1658 => 1200,  1656 => 1198,  1653 => 1197,  1651 => 1195,  1648 => 1194,  1646 => 1192,  1643 => 1191,  1641 => 1189,  1638 => 1188,  1636 => 1186,  1633 => 1185,  1631 => 1183,  1626 => 1180,  1624 => 1178,  1621 => 1177,  1619 => 1175,  1616 => 1174,  1614 => 1172,  1611 => 1171,  1609 => 1169,  1606 => 1168,  1604 => 1166,  1601 => 1165,  1599 => 1163,  1596 => 1162,  1594 => 1160,  1591 => 1159,  1589 => 1157,  1586 => 1156,  1584 => 1154,  1581 => 1153,  1579 => 1151,  1574 => 1148,  1572 => 1146,  1569 => 1145,  1567 => 1143,  1564 => 1142,  1562 => 1140,  1559 => 1139,  1557 => 1137,  1554 => 1136,  1552 => 1134,  1549 => 1133,  1547 => 1131,  1544 => 1130,  1542 => 1128,  1539 => 1127,  1537 => 1125,  1534 => 1124,  1532 => 1122,  1529 => 1121,  1527 => 1119,  1522 => 1116,  1520 => 1114,  1517 => 1113,  1515 => 1111,  1512 => 1110,  1510 => 1108,  1507 => 1107,  1505 => 1105,  1502 => 1104,  1500 => 1102,  1497 => 1101,  1495 => 1099,  1492 => 1098,  1490 => 1096,  1487 => 1095,  1485 => 1093,  1482 => 1092,  1480 => 1090,  1477 => 1089,  1475 => 1087,  1470 => 1084,  1468 => 1082,  1465 => 1081,  1463 => 1079,  1460 => 1078,  1458 => 1076,  1455 => 1075,  1453 => 1073,  1450 => 1072,  1448 => 1070,  1445 => 1069,  1443 => 1067,  1440 => 1066,  1438 => 1064,  1435 => 1063,  1433 => 1061,  1430 => 1060,  1428 => 1058,  1425 => 1057,  1423 => 1055,  1418 => 1052,  1416 => 1050,  1413 => 1049,  1411 => 1047,  1408 => 1046,  1406 => 1044,  1403 => 1043,  1401 => 1041,  1398 => 1040,  1396 => 1038,  1393 => 1037,  1391 => 1035,  1388 => 1034,  1386 => 1032,  1383 => 1031,  1381 => 1029,  1378 => 1028,  1376 => 1026,  1373 => 1025,  1371 => 1023,  1366 => 1020,  1364 => 1018,  1361 => 1017,  1359 => 1015,  1356 => 1014,  1354 => 1012,  1351 => 1011,  1349 => 1009,  1346 => 1008,  1344 => 1006,  1341 => 1005,  1339 => 1003,  1336 => 1002,  1334 => 1000,  1331 => 999,  1329 => 997,  1326 => 996,  1324 => 994,  1321 => 993,  1319 => 991,  1314 => 988,  1312 => 986,  1309 => 985,  1307 => 983,  1304 => 982,  1302 => 980,  1299 => 979,  1297 => 977,  1294 => 976,  1292 => 974,  1289 => 973,  1287 => 971,  1284 => 970,  1282 => 968,  1279 => 967,  1277 => 965,  1274 => 964,  1272 => 962,  1269 => 961,  1267 => 959,  1262 => 956,  1260 => 954,  1257 => 953,  1255 => 951,  1252 => 950,  1250 => 948,  1247 => 947,  1245 => 945,  1242 => 944,  1240 => 942,  1237 => 941,  1235 => 939,  1232 => 938,  1230 => 936,  1227 => 935,  1225 => 933,  1222 => 932,  1220 => 930,  1217 => 929,  1215 => 927,  1212 => 926,  1210 => 924,  1207 => 923,  1205 => 921,  1202 => 920,  1200 => 918,  1197 => 917,  1195 => 915,  1192 => 914,  1190 => 912,  1187 => 911,  1185 => 909,  1182 => 908,  1180 => 906,  1177 => 905,  1175 => 903,  1172 => 902,  1170 => 900,  1167 => 899,  1165 => 897,  1162 => 896,  1160 => 894,  1157 => 893,  1155 => 891,  1152 => 890,  1150 => 888,  1147 => 887,  1145 => 885,  1142 => 884,  1140 => 882,  1137 => 881,  1135 => 879,  1132 => 878,  1130 => 876,  1127 => 875,  1125 => 873,  1122 => 872,  1120 => 870,  1115 => 867,  1113 => 865,  1110 => 864,  1108 => 862,  1105 => 861,  1103 => 859,  1100 => 858,  1098 => 856,  1095 => 855,  1093 => 853,  1090 => 852,  1088 => 850,  1085 => 849,  1083 => 847,  1080 => 846,  1078 => 844,  1075 => 843,  1073 => 841,  1070 => 840,  1068 => 838,  1065 => 837,  1063 => 835,  1060 => 834,  1058 => 832,  1055 => 831,  1053 => 829,  1050 => 828,  1048 => 826,  1045 => 825,  1043 => 823,  1040 => 822,  1038 => 820,  1035 => 819,  1033 => 817,  1030 => 816,  1028 => 814,  1025 => 813,  1023 => 811,  1020 => 810,  1018 => 808,  1015 => 807,  1013 => 805,  1010 => 804,  1008 => 802,  1005 => 801,  1003 => 799,  1000 => 798,  998 => 796,  995 => 795,  993 => 793,  990 => 792,  988 => 790,  985 => 789,  983 => 787,  980 => 786,  978 => 784,  975 => 783,  973 => 781,  967 => 777,  948 => 760,  946 => 759,  912 => 728,  908 => 727,  904 => 726,  900 => 725,  585 => 413,  582 => 412,  578 => 411,  572 => 408,  564 => 402,  562 => 401,  556 => 398,  552 => 397,  548 => 396,  544 => 395,  514 => 368,  511 => 367,  507 => 366,  501 => 363,  499 => 360,  496 => 359,  494 => 356,  491 => 355,  489 => 352,  486 => 351,  484 => 348,  481 => 347,  479 => 344,  476 => 343,  474 => 340,  471 => 339,  469 => 336,  466 => 335,  464 => 332,  461 => 331,  459 => 328,  456 => 327,  454 => 324,  451 => 323,  449 => 320,  446 => 319,  444 => 316,  441 => 315,  439 => 312,  436 => 311,  434 => 308,  431 => 307,  429 => 304,  426 => 303,  424 => 300,  421 => 299,  419 => 296,  416 => 295,  414 => 292,  411 => 291,  409 => 288,  406 => 287,  404 => 284,  401 => 283,  399 => 280,  396 => 279,  394 => 276,  391 => 275,  389 => 272,  386 => 271,  384 => 268,  381 => 267,  379 => 264,  376 => 263,  374 => 260,  371 => 259,  369 => 256,  366 => 255,  364 => 252,  361 => 251,  359 => 248,  356 => 247,  354 => 244,  351 => 243,  349 => 240,  346 => 239,  344 => 236,  341 => 235,  339 => 232,  336 => 231,  334 => 228,  331 => 227,  329 => 224,  326 => 223,  324 => 220,  321 => 219,  319 => 216,  316 => 215,  314 => 212,  311 => 211,  309 => 208,  306 => 207,  304 => 204,  301 => 203,  299 => 200,  296 => 199,  294 => 196,  291 => 195,  289 => 192,  286 => 191,  284 => 188,  281 => 187,  279 => 184,  276 => 183,  274 => 180,  271 => 179,  269 => 176,  266 => 175,  264 => 172,  261 => 171,  259 => 168,  256 => 167,  254 => 164,  251 => 163,  249 => 160,  246 => 159,  244 => 156,  241 => 155,  239 => 152,  236 => 151,  234 => 148,  231 => 147,  229 => 144,  226 => 143,  224 => 140,  221 => 139,  219 => 136,  216 => 135,  214 => 132,  211 => 131,  209 => 128,  206 => 127,  204 => 124,  201 => 123,  199 => 120,  196 => 119,  194 => 116,  191 => 115,  189 => 112,  186 => 111,  184 => 108,  181 => 107,  179 => 104,  176 => 103,  174 => 100,  171 => 99,  169 => 96,  166 => 95,  164 => 92,  161 => 91,  159 => 88,  156 => 87,  154 => 84,  151 => 83,  149 => 80,  146 => 79,  144 => 76,  141 => 75,  139 => 72,  136 => 71,  134 => 68,  131 => 67,  129 => 64,  126 => 63,  124 => 60,  121 => 59,  119 => 56,  116 => 55,  114 => 52,  111 => 51,  109 => 48,  106 => 47,  104 => 44,  101 => 43,  99 => 40,  96 => 39,  94 => 36,  91 => 35,  89 => 32,  86 => 31,  84 => 28,  81 => 27,  79 => 24,  76 => 23,  74 => 20,  71 => 19,  69 => 16,  66 => 15,  64 => 12,  61 => 11,  59 => 8,  56 => 7,  53 => 4,  49 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "newsletter/editor.html", "/home/circleci/mailpoet/mailpoet/views/newsletter/editor.html");
    }
}
