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

/* deactivationPoll/link-poll.html */
class __TwigTemplate_24e96a0e6a17ce2c93cf643ff67585ab8fe18e1a7ba8b35cfd7b474c10128b6c extends Template
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
          var participateButton = document.querySelector(\x27#mailpoet-deactivate-survey-participate\x27);
          var formOpen = false;

          if (!deactivateLink || !overlay || !closeButton || !participateButton) {
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

          participateButton.addEventListener(\x27click\x27, function (event) {
                setTimeout(function() {closeButton.click(); }, 50);
          });

          document.addEventListener(\x27keyup\x27, function (event) {
            if ((event.keyCode === 27) && formOpen) {
              location.href = deactivateLink.getAttribute(\x27href\x27);
            }
          });
        });
      </script>

      <p><strong>

        ";
        // line 44
        yield $this->extensions['MailPoet\Twig\I18n']->translate("We\x27re sorry to see you go. Would you be open to sharing how MailPoet didn\x27t work for you so we could improve it?");
        yield "
      </strong><br>";
        // line 45
        yield $this->extensions['MailPoet\Twig\I18n']->translate("It will take only a minute.");
        yield "</p>
      <a
        class=\"button button-primary\"
        id=\"mailpoet-deactivate-survey-participate\"
        href=\"https://poll.fm/11161195\"
        target=\"_blank\"
        rel=\"noopener nofollow\"
      >";
        // line 52
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Yes");
        yield " &rarr;</a>

      <a
        class=\"button\"
        id=\"mailpoet-deactivate-survey-close\"
      >
        ";
        // line 58
        yield $this->extensions['MailPoet\Twig\I18n']->translate("No");
        yield "
      </a>
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
        return "deactivationPoll/link-poll.html";
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
        return array (  106 => 58,  97 => 52,  87 => 45,  83 => 44,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "deactivationPoll/link-poll.html", "/home/circleci/mailpoet/mailpoet/views/deactivationPoll/link-poll.html");
    }
}
