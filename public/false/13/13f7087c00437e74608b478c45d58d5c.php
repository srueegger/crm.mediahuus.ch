<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* auth/login.html.twig */
class __TwigTemplate_b48d9e1faf0ae8d4ef282feea09a0fa6 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'title' => [$this, 'block_title'],
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return "base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $this->parent = $this->load("base.html.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_title(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield "Anmelden - ";
        yield from $this->yieldParentBlock("title", $context, $blocks);
        yield from [];
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 6
        yield "<div class=\"min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8\">
    <div class=\"max-w-md w-full space-y-8\">
        <div>
            <div class=\"flex justify-center mb-4\">
                <img src=\"/assets/logo.png\" alt=\"";
        // line 10
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_name"] ?? null), "html", null, true);
        yield "\" class=\"h-12 w-auto\">
            </div>
            <h2 class=\"mt-6 text-center text-3xl font-extrabold text-gray-900\">
                Bei ";
        // line 13
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_name"] ?? null), "html", null, true);
        yield " anmelden
            </h2>
            <p class=\"mt-2 text-center text-sm text-gray-600\">
                Internes CRM-System
            </p>
        </div>
        
        ";
        // line 20
        if ((($tmp = ($context["error"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 21
            yield "        <div class=\"bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative\">
            ";
            // line 22
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["error"] ?? null), "html", null, true);
            yield "
        </div>
        ";
        }
        // line 25
        yield "        
        <form class=\"mt-8 space-y-6\" action=\"/login\" method=\"POST\">
            <div class=\"rounded-md shadow-sm -space-y-px\">
                <div>
                    <label for=\"email\" class=\"sr-only\">E-Mail-Adresse</label>
                    <input id=\"email\" name=\"email\" type=\"email\" autocomplete=\"email\" required 
                           value=\"";
        // line 31
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((array_key_exists("email", $context)) ? (Twig\Extension\CoreExtension::default(($context["email"] ?? null), "")) : ("")), "html", null, true);
        yield "\"
                           class=\"appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm\" 
                           placeholder=\"E-Mail-Adresse\">
                </div>
                <div>
                    <label for=\"password\" class=\"sr-only\">Passwort</label>
                    <input id=\"password\" name=\"password\" type=\"password\" autocomplete=\"current-password\" required 
                           class=\"appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm\" 
                           placeholder=\"Passwort\">
                </div>
            </div>

            <div>
                <button type=\"submit\" 
                        class=\"group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                    <span class=\"absolute left-0 inset-y-0 flex items-center pl-3\">
                        <svg class=\"h-5 w-5 text-blue-500 group-hover:text-blue-400\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                            <path fill-rule=\"evenodd\" d=\"M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z\" clip-rule=\"evenodd\" />
                        </svg>
                    </span>
                    Anmelden
                </button>
            </div>
            
            <div class=\"text-center text-sm text-gray-500\">
                Demo-Zugangsdaten: admin@mediahuus.ch / admin123
            </div>
        </form>
    </div>
</div>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "auth/login.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  112 => 31,  104 => 25,  98 => 22,  95 => 21,  93 => 20,  83 => 13,  77 => 10,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Anmelden - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8\">
    <div class=\"max-w-md w-full space-y-8\">
        <div>
            <div class=\"flex justify-center mb-4\">
                <img src=\"/assets/logo.png\" alt=\"{{ app_name }}\" class=\"h-12 w-auto\">
            </div>
            <h2 class=\"mt-6 text-center text-3xl font-extrabold text-gray-900\">
                Bei {{ app_name }} anmelden
            </h2>
            <p class=\"mt-2 text-center text-sm text-gray-600\">
                Internes CRM-System
            </p>
        </div>
        
        {% if error %}
        <div class=\"bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative\">
            {{ error }}
        </div>
        {% endif %}
        
        <form class=\"mt-8 space-y-6\" action=\"/login\" method=\"POST\">
            <div class=\"rounded-md shadow-sm -space-y-px\">
                <div>
                    <label for=\"email\" class=\"sr-only\">E-Mail-Adresse</label>
                    <input id=\"email\" name=\"email\" type=\"email\" autocomplete=\"email\" required 
                           value=\"{{ email|default('') }}\"
                           class=\"appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm\" 
                           placeholder=\"E-Mail-Adresse\">
                </div>
                <div>
                    <label for=\"password\" class=\"sr-only\">Passwort</label>
                    <input id=\"password\" name=\"password\" type=\"password\" autocomplete=\"current-password\" required 
                           class=\"appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm\" 
                           placeholder=\"Passwort\">
                </div>
            </div>

            <div>
                <button type=\"submit\" 
                        class=\"group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                    <span class=\"absolute left-0 inset-y-0 flex items-center pl-3\">
                        <svg class=\"h-5 w-5 text-blue-500 group-hover:text-blue-400\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                            <path fill-rule=\"evenodd\" d=\"M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z\" clip-rule=\"evenodd\" />
                        </svg>
                    </span>
                    Anmelden
                </button>
            </div>
            
            <div class=\"text-center text-sm text-gray-500\">
                Demo-Zugangsdaten: admin@mediahuus.ch / admin123
            </div>
        </form>
    </div>
</div>
{% endblock %}", "auth/login.html.twig", "/var/www/html/templates/auth/login.html.twig");
    }
}
