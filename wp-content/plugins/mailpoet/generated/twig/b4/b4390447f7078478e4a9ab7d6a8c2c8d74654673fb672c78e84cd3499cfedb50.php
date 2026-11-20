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

/* logs_data.html */
class __TwigTemplate_207089b3e015287bbc04bcee6c45e9a624ad67e2a80c754f48d54bb8da112b00 extends Template
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
        // line 2
        yield "  var mailpoet_logs_default_from = \x27";
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["logs_default_from"] ?? null), "js", null, true);
        yield "\x27;
  var mailpoet_logs_api = ";
        // line 3
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["api"] ?? null));
        yield ";
  var mailpoet_logs_download = ";
        // line 4
        yield $this->extensions['MailPoet\Twig\Functions']->jsonEncode(($context["download"] ?? null));
        yield ";
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "logs_data.html";
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
        return array (  47 => 4,  43 => 3,  38 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("", "logs_data.html", "/home/circleci/mailpoet/mailpoet/views/logs_data.html");
    }
}
