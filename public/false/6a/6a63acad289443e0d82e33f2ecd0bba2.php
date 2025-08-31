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

/* estimates/index.html.twig */
class __TwigTemplate_2e5b87360ba6c96ec861ec1c1dd24fa3 extends Template
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
        yield "Kostenvoranschläge - ";
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
        <!-- Header -->
        <div class=\"mb-8 flex justify-between items-center\">
            <div>
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Kostenvoranschläge</h2>
                <p class=\"text-gray-600\">Übersicht aller erstellten Kostenvoranschläge</p>
            </div>
            <a href=\"";
        // line 14
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.create"), "html", null, true);
        yield "\" 
               class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                Neuer Kostenvoranschlag
            </a>
        </div>

        <!-- Error Message -->
        ";
        // line 21
        if ((($tmp = ($context["error"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 22
            yield "            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                ";
            // line 23
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["error"] ?? null), "html", null, true);
            yield "
            </div>
        ";
        }
        // line 26
        yield "
        <!-- Estimates List -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            ";
        // line 29
        if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["estimates"] ?? null)) > 0)) {
            // line 30
            yield "                <ul class=\"divide-y divide-gray-200\">
                    ";
            // line 31
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["estimates"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 32
                yield "                        <li class=\"px-6 py-4 hover:bg-gray-50\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center\">
                                            <svg class=\"h-5 w-5 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                ";
                // line 45
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 45), "doc_number", [], "any", false, false, false, 45), "html", null, true);
                yield "
                                            </p>
                                            <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800\">
                                                Kostenvoranschlag
                                            </span>
                                        </div>
                                        <p class=\"text-sm text-gray-900 font-medium\">
                                            ";
                // line 52
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 52), "customer_name", [], "any", false, false, false, 52), "html", null, true);
                yield "
                                        </p>
                                        <div class=\"text-sm text-gray-500 space-y-1\">
                                            <p>";
                // line 55
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "branch", [], "any", false, false, false, 55), "name", [], "any", false, false, false, 55), "html", null, true);
                yield " • ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 55), "formatted_price", [], "any", false, false, false, 55), "html", null, true);
                yield "</p>
                                            <p>Erstellt: ";
                // line 56
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 56), "created_at", [], "any", false, false, false, 56), "d.m.Y H:i"), "html", null, true);
                yield "</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    <!-- View Button -->
                                    <a href=\"";
                // line 63
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.show", ["id" => CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "document", [], "any", false, false, false, 63), "id", [], "any", false, false, false, 63)]), "html", null, true);
                yield "\" 
                                       class=\"bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200 transition-colors\">
                                        Anzeigen
                                    </a>
                                    <!-- PDF Button (placeholder) -->
                                    <button class=\"bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition-colors opacity-50 cursor-not-allowed\">
                                        PDF
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Issue Preview -->
                            ";
                // line 75
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 75), "issue_text", [], "any", false, false, false, 75)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 76
                    yield "                                <div class=\"mt-3 pl-14\">
                                    <p class=\"text-sm text-gray-600 italic\">
                                        \"";
                    // line 78
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(Twig\Extension\CoreExtension::slice($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 78), "issue_text", [], "any", false, false, false, 78), 0, 150), "html", null, true);
                    if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "estimate", [], "any", false, false, false, 78), "issue_text", [], "any", false, false, false, 78)) > 150)) {
                        yield "...";
                    }
                    yield "\"
                                    </p>
                                </div>
                            ";
                }
                // line 82
                yield "                        </li>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 84
            yield "                </ul>
            ";
        } else {
            // line 86
            yield "                <div class=\"text-center py-12\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                    </svg>
                    <h3 class=\"mt-2 text-sm font-medium text-gray-900\">Keine Kostenvoranschläge</h3>
                    <p class=\"mt-1 text-sm text-gray-500\">Erstellen Sie Ihren ersten Kostenvoranschlag.</p>
                    <div class=\"mt-6\">
                        <a href=\"";
            // line 93
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.create"), "html", null, true);
            yield "\" 
                           class=\"inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700\">
                            Neuer Kostenvoranschlag
                        </a>
                    </div>
                </div>
            ";
        }
        // line 100
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
        return "estimates/index.html.twig";
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
        return array (  224 => 100,  214 => 93,  205 => 86,  201 => 84,  194 => 82,  184 => 78,  180 => 76,  178 => 75,  163 => 63,  153 => 56,  147 => 55,  141 => 52,  131 => 45,  116 => 32,  112 => 31,  109 => 30,  107 => 29,  102 => 26,  96 => 23,  93 => 22,  91 => 21,  81 => 14,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Kostenvoranschläge - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-7xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8 flex justify-between items-center\">
            <div>
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Kostenvoranschläge</h2>
                <p class=\"text-gray-600\">Übersicht aller erstellten Kostenvoranschläge</p>
            </div>
            <a href=\"{{ url_for('estimates.create') }}\" 
               class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                Neuer Kostenvoranschlag
            </a>
        </div>

        <!-- Error Message -->
        {% if error %}
            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                {{ error }}
            </div>
        {% endif %}

        <!-- Estimates List -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            {% if estimates|length > 0 %}
                <ul class=\"divide-y divide-gray-200\">
                    {% for item in estimates %}
                        <li class=\"px-6 py-4 hover:bg-gray-50\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center\">
                                            <svg class=\"h-5 w-5 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                {{ item.document.doc_number }}
                                            </p>
                                            <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800\">
                                                Kostenvoranschlag
                                            </span>
                                        </div>
                                        <p class=\"text-sm text-gray-900 font-medium\">
                                            {{ item.document.customer_name }}
                                        </p>
                                        <div class=\"text-sm text-gray-500 space-y-1\">
                                            <p>{{ item.branch.name }} • {{ item.estimate.formatted_price }}</p>
                                            <p>Erstellt: {{ item.document.created_at|date('d.m.Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    <!-- View Button -->
                                    <a href=\"{{ url_for('estimates.show', {id: item.document.id}) }}\" 
                                       class=\"bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200 transition-colors\">
                                        Anzeigen
                                    </a>
                                    <!-- PDF Button (placeholder) -->
                                    <button class=\"bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition-colors opacity-50 cursor-not-allowed\">
                                        PDF
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Issue Preview -->
                            {% if item.estimate.issue_text %}
                                <div class=\"mt-3 pl-14\">
                                    <p class=\"text-sm text-gray-600 italic\">
                                        \"{{ item.estimate.issue_text|slice(0, 150) }}{% if item.estimate.issue_text|length > 150 %}...{% endif %}\"
                                    </p>
                                </div>
                            {% endif %}
                        </li>
                    {% endfor %}
                </ul>
            {% else %}
                <div class=\"text-center py-12\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\" />
                    </svg>
                    <h3 class=\"mt-2 text-sm font-medium text-gray-900\">Keine Kostenvoranschläge</h3>
                    <p class=\"mt-1 text-sm text-gray-500\">Erstellen Sie Ihren ersten Kostenvoranschlag.</p>
                    <div class=\"mt-6\">
                        <a href=\"{{ url_for('estimates.create') }}\" 
                           class=\"inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700\">
                            Neuer Kostenvoranschlag
                        </a>
                    </div>
                </div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}", "estimates/index.html.twig", "/var/www/html/templates/estimates/index.html.twig");
    }
}
