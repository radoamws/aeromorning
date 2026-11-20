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

/* deactivationPoll/embedded-poll.html */
class __TwigTemplate_52b5c9ea631c938881f989f0726722fe169a98767c0b2a4158d04455a1e4bd0c extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        yield "<div class=\"mailpoet-deactivate-survey-modal\" id=\"mailpoet-deactivate-survey-modal\">
  <div class=\"mailpoet-deactivate-survey-wrap\">
    <div class=\"mailpoet-deactivate-survey\">
      <script type=\"text/javascript\">
        window.addEventListener(\x27load\x27, function() {
          var deactivateLink = document.querySelector(\x27#the-list [data-slug=\"mailpoet\"] span.deactivate a\x27);
          var overlay = document.querySelector(\x27#mailpoet-deactivate-survey-modal\x27);
          var closeButton = document.querySelector(\x27#mailpoet-deactivate-survey-close\x27);
          var formOpen = false;

          if (!deactivateLink || !overlay || !closeButton) {
            return;
          }

          deactivateLink.addEventListener(\x27click\x27, function (event) {
            event.preventDefault();
            overlay.style.display = \x27table\x27;
            formOpen = true;
          });

          closeButton.addEventListener(\x27click\x27, function (event) {
            event.preventDefault();
            overlay.style.display = \x27none\x27;
            formOpen = false;
            location.href = deactivateLink.getAttribute(\x27href\x27);
          });

          document.addEventListener(\x27keyup\x27, function (event) {
            if ((event.keyCode === 27) && formOpen) {
              location.href = deactivateLink.getAttribute(\x27href\x27);
            }
          });
        });

        // This callback is by docs, and guarantees that the modal window is closed and deactivated only after the vote has been submitted
        var pd_callback = function(json) {
          var obj = JSON.parse(json);
          var deactivateLink = document.querySelector(\x27#the-list [data-slug=\"mailpoet\"] span.deactivate a\x27);
          var overlay = document.querySelector(\x27#mailpoet-deactivate-survey-modal\x27);
          if (obj.result === \x27already-registered\x27 || obj.result === \x27registered\x27) {
            overlay.style.display = \x27none\x27;
            location.href = deactivateLink.getAttribute(\x27href\x27);
          }
        };
      </script>
      <script type=\"text/javascript\" charset=\"utf-8\" src=\"https://secure.polldaddy.com/p/11161195.js\"></script>

      <noscript><a href=\"https://poll.fm/11161195\">";
        // line 48
        yield $this->extensions['MailPoet\Twig\I18n']->translate("We\x27re sorry to see you leave. Could you tell us more why are you deactivating MailPoet?");
        yield "</a></noscript>

      <a class=\"button\" id=\"mailpoet-deactivate-survey-close\">";
        // line 50
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Skip survey and deactivate MailPoet");
        yield " &rarr;</a>
    </div>
  </div>
</div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "deactivationPoll/embedded-poll.html";
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
        return array (  92 => 50,  87 => 48,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "deactivationPoll/embedded-poll.html", "/home/circleci/mailpoet/mailpoet/views/deactivationPoll/embedded-poll.html");
    }
}
