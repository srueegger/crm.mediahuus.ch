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

/* dashboard.html.twig */
class __TwigTemplate_0f4f6e09fdd54733c93dfe7aea24a2ba extends Template
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
        yield "Dashboard - ";
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
        yield "<div class=\"max-w-7xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Dashboard</h2>
            <p class=\"text-gray-600\">Willkommen im Mediahuus CRM-System</p>
        </div>

        <!-- Action Cards -->
        <div class=\"grid grid-cols-1 md:grid-cols-3 gap-6 mb-8\">
            <!-- Kostenvoranschlag Card -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Kostenvoranschlag</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Neuen Kostenvoranschlag erstellen</p>
                            <a href=\"";
        // line 27
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimate.new"), "html", null, true);
        yield "\" class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                                Erstellen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ankauf Card (Disabled) -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg opacity-50 cursor-not-allowed\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-green-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Ankauf</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Ankaufsbeleg erstellen</p>
                            <span class=\"bg-gray-300 text-gray-500 px-4 py-2 rounded-md text-sm cursor-not-allowed\">
                                Bald verfügbar
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Versicherungsgutachten Card (Disabled) -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg opacity-50 cursor-not-allowed\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-purple-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Versicherungsgutachten</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Gutachten für Versicherung</p>
                            <span class=\"bg-gray-300 text-gray-500 px-4 py-2 rounded-md text-sm cursor-not-allowed\">
                                Bald verfügbar
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Documents Section -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            <div class=\"px-4 py-5 sm:px-6\">
                <h3 class=\"text-lg leading-6 font-medium text-gray-900\">Zuletzt erstellt</h3>
                <p class=\"mt-1 max-w-2xl text-sm text-gray-500\">Ihre neuesten Dokumente</p>
            </div>
            <div class=\"px-4 py-5 sm:p-6\">
                <div class=\"text-center text-gray-500\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                    </svg>
                    <p class=\"mt-2\">Noch keine Dokumente erstellt</p>
                </div>
            </div>
        </div>
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
        return "dashboard.html.twig";
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
        return array (  94 => 27,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Dashboard - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-7xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Dashboard</h2>
            <p class=\"text-gray-600\">Willkommen im Mediahuus CRM-System</p>
        </div>

        <!-- Action Cards -->
        <div class=\"grid grid-cols-1 md:grid-cols-3 gap-6 mb-8\">
            <!-- Kostenvoranschlag Card -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Kostenvoranschlag</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Neuen Kostenvoranschlag erstellen</p>
                            <a href=\"{{ url_for('estimate.new') }}\" class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                                Erstellen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ankauf Card (Disabled) -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg opacity-50 cursor-not-allowed\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-green-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Ankauf</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Ankaufsbeleg erstellen</p>
                            <span class=\"bg-gray-300 text-gray-500 px-4 py-2 rounded-md text-sm cursor-not-allowed\">
                                Bald verfügbar
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Versicherungsgutachten Card (Disabled) -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg opacity-50 cursor-not-allowed\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-purple-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Versicherungsgutachten</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Gutachten für Versicherung</p>
                            <span class=\"bg-gray-300 text-gray-500 px-4 py-2 rounded-md text-sm cursor-not-allowed\">
                                Bald verfügbar
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Documents Section -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            <div class=\"px-4 py-5 sm:px-6\">
                <h3 class=\"text-lg leading-6 font-medium text-gray-900\">Zuletzt erstellt</h3>
                <p class=\"mt-1 max-w-2xl text-sm text-gray-500\">Ihre neuesten Dokumente</p>
            </div>
            <div class=\"px-4 py-5 sm:p-6\">
                <div class=\"text-center text-gray-500\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                    </svg>
                    <p class=\"mt-2\">Noch keine Dokumente erstellt</p>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}", "dashboard.html.twig", "/var/www/html/templates/dashboard.html.twig");
    }
}
