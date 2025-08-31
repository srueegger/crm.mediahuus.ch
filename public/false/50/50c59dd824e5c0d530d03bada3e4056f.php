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
        <div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8\">
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

            <!-- Benutzerverwaltung Card -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-indigo-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Benutzerverwaltung</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Benutzer verwalten</p>
                            <a href=\"";
        // line 87
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.index"), "html", null, true);
        yield "\" class=\"bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition-colors\">
                                Verwalten
                            </a>
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
            ";
        // line 102
        if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["recent_documents"] ?? null)) > 0)) {
            // line 103
            yield "                <ul class=\"divide-y divide-gray-200\">
                    ";
            // line 104
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["recent_documents"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 105
                yield "                        <li class=\"px-6 py-4 hover:bg-gray-50\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center\">
                                            <svg class=\"h-4 w-4 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                ";
                // line 118
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 118), "doc_number", [], "any", false, false, false, 118), "html", null, true);
                yield "
                                            </p>
                                            ";
                // line 120
                if ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 120), "doc_type", [], "any", false, false, false, 120) == "estimate")) {
                    // line 121
                    yield "                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800\">
                                                    Kostenvoranschlag
                                                </span>
                                            ";
                }
                // line 125
                yield "                                        </div>
                                        <p class=\"text-sm text-gray-900 font-medium\">
                                            ";
                // line 127
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 127), "customer_name", [], "any", false, false, false, 127), "html", null, true);
                yield "
                                        </p>
                                        <div class=\"text-xs text-gray-500 space-y-1\">
                                            <p>";
                // line 130
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "branch", [], "any", false, false, false, 130), "name", [], "any", false, false, false, 130), "html", null, true);
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 130)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield " • ";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 130), "formatted_price", [], "any", false, false, false, 130), "html", null, true);
                }
                yield "</p>
                                            <p>";
                // line 131
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 131), "created_at", [], "any", false, false, false, 131), "d.m.Y H:i"), "html", null, true);
                yield "</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    ";
                // line 137
                if ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 137), "doc_type", [], "any", false, false, false, 137) == "estimate")) {
                    // line 138
                    yield "                                        <a href=\"";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.show", ["id" => CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 138), "id", [], "any", false, false, false, 138)]), "html", null, true);
                    yield "\" 
                                           class=\"bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs hover:bg-gray-200 transition-colors\">
                                            Anzeigen
                                        </a>
                                        <a href=\"";
                    // line 142
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.pdf", ["id" => CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 142), "id", [], "any", false, false, false, 142)]), "html", null, true);
                    yield "\" 
                                           class=\"bg-red-100 text-red-700 px-2 py-1 rounded text-xs hover:bg-red-200 transition-colors\">
                                            PDF
                                        </a>
                                    ";
                }
                // line 147
                yield "                                </div>
                            </div>
                        </li>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 151
            yield "                </ul>
                <div class=\"px-6 py-3 bg-gray-50 border-t border-gray-200\">
                    <div class=\"flex justify-center\">
                        <a href=\"";
            // line 154
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.index"), "html", null, true);
            yield "\" 
                           class=\"text-sm text-blue-600 hover:text-blue-500\">
                            Alle Dokumente anzeigen →
                        </a>
                    </div>
                </div>
            ";
        } else {
            // line 161
            yield "                <div class=\"px-4 py-5 sm:p-6\">
                    <div class=\"text-center text-gray-500\">
                        <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                        </svg>
                        <p class=\"mt-2\">Noch keine Dokumente erstellt</p>
                    </div>
                </div>
            ";
        }
        // line 170
        yield "        </div>
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
        return array (  292 => 170,  281 => 161,  271 => 154,  266 => 151,  257 => 147,  249 => 142,  241 => 138,  239 => 137,  230 => 131,  222 => 130,  216 => 127,  212 => 125,  206 => 121,  204 => 120,  199 => 118,  184 => 105,  180 => 104,  177 => 103,  175 => 102,  157 => 87,  94 => 27,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
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
        <div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8\">
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

            <!-- Benutzerverwaltung Card -->
            <div class=\"bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow\">
                <div class=\"p-6\">
                    <div class=\"flex items-center\">
                        <div class=\"flex-shrink-0\">
                            <svg class=\"h-8 w-8 text-indigo-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z\" />
                            </svg>
                        </div>
                        <div class=\"ml-4 flex-1\">
                            <h3 class=\"text-lg font-medium text-gray-900\">Benutzerverwaltung</h3>
                            <p class=\"text-sm text-gray-500 mb-4\">Benutzer verwalten</p>
                            <a href=\"{{ url_for('users.index') }}\" class=\"bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 transition-colors\">
                                Verwalten
                            </a>
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
            {% if recent_documents|length > 0 %}
                <ul class=\"divide-y divide-gray-200\">
                    {% for item in recent_documents %}
                        <li class=\"px-6 py-4 hover:bg-gray-50\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center\">
                                            <svg class=\"h-4 w-4 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                {{ item.document.doc_number }}
                                            </p>
                                            {% if item.document.doc_type == 'estimate' %}
                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800\">
                                                    Kostenvoranschlag
                                                </span>
                                            {% endif %}
                                        </div>
                                        <p class=\"text-sm text-gray-900 font-medium\">
                                            {{ item.document.customer_name }}
                                        </p>
                                        <div class=\"text-xs text-gray-500 space-y-1\">
                                            <p>{{ item.branch.name }}{% if item.estimate %} • {{ item.estimate.formatted_price }}{% endif %}</p>
                                            <p>{{ item.document.created_at|date('d.m.Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    {% if item.document.doc_type == 'estimate' %}
                                        <a href=\"{{ url_for('estimates.show', {id: item.document.id}) }}\" 
                                           class=\"bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs hover:bg-gray-200 transition-colors\">
                                            Anzeigen
                                        </a>
                                        <a href=\"{{ url_for('estimates.pdf', {id: item.document.id}) }}\" 
                                           class=\"bg-red-100 text-red-700 px-2 py-1 rounded text-xs hover:bg-red-200 transition-colors\">
                                            PDF
                                        </a>
                                    {% endif %}
                                </div>
                            </div>
                        </li>
                    {% endfor %}
                </ul>
                <div class=\"px-6 py-3 bg-gray-50 border-t border-gray-200\">
                    <div class=\"flex justify-center\">
                        <a href=\"{{ url_for('estimates.index') }}\" 
                           class=\"text-sm text-blue-600 hover:text-blue-500\">
                            Alle Dokumente anzeigen →
                        </a>
                    </div>
                </div>
            {% else %}
                <div class=\"px-4 py-5 sm:p-6\">
                    <div class=\"text-center text-gray-500\">
                        <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                        </svg>
                        <p class=\"mt-2\">Noch keine Dokumente erstellt</p>
                    </div>
                </div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}", "dashboard.html.twig", "/var/www/html/templates/dashboard.html.twig");
    }
}
