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

/* estimates/show.html.twig */
class __TwigTemplate_b45a910ffc9a20304bb8f2dfc798a1de extends Template
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
        yield "Kostenvoranschlag ";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "doc_number", [], "any", false, false, false, 3), "html", null, true);
        yield " - ";
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
        yield "<div class=\"max-w-4xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8 flex justify-between items-start\">
            <div>
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Kostenvoranschlag ";
        // line 11
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "doc_number", [], "any", false, false, false, 11), "html", null, true);
        yield "</h2>
                <p class=\"text-gray-600\">";
        // line 12
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "name", [], "any", false, false, false, 12), "html", null, true);
        yield "</p>
            </div>
            <div class=\"flex space-x-3\">
                <a href=\"";
        // line 15
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.index"), "html", null, true);
        yield "\" 
                   class=\"bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-200 transition-colors\">
                    ← Zurück zur Liste
                </a>
                <a href=\"";
        // line 19
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.pdf", ["id" => CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "id", [], "any", false, false, false, 19)]), "html", null, true);
        yield "\" 
                   class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 transition-colors\">
                    PDF herunterladen
                </a>
            </div>
        </div>

        <!-- Document Details -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-lg\">
            <div class=\"px-4 py-5 sm:px-6 border-b border-gray-200\">
                <h3 class=\"text-lg leading-6 font-medium text-gray-900\">Dokumentdetails</h3>
                <p class=\"mt-1 max-w-2xl text-sm text-gray-500\">Informationen zu diesem Kostenvoranschlag</p>
            </div>
            <div class=\"border-t border-gray-200\">
                <dl>
                    <!-- Document Number -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Dokumentnummer</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">";
        // line 37
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "doc_number", [], "any", false, false, false, 37), "html", null, true);
        yield "</dd>
                    </div>
                    
                    <!-- Branch -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Filiale</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div>
                                <p class=\"font-medium\">";
        // line 45
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "name", [], "any", false, false, false, 45), "html", null, true);
        yield "</p>
                                <p class=\"text-gray-600\">";
        // line 46
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "full_address", [], "any", false, false, false, 46), "html", null, true);
        yield "</p>
                                ";
        // line 47
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "phone", [], "any", false, false, false, 47)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p class=\"text-gray-600\">Tel: ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "phone", [], "any", false, false, false, 47), "html", null, true);
            yield "</p>";
        }
        // line 48
        yield "                                ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "email", [], "any", false, false, false, 48)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p class=\"text-gray-600\">E-Mail: ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["branch"] ?? null), "email", [], "any", false, false, false, 48), "html", null, true);
            yield "</p>";
        }
        // line 49
        yield "                            </div>
                        </dd>
                    </div>
                    
                    <!-- Customer -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Kunde</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div>
                                <p class=\"font-medium\">";
        // line 58
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "customer_name", [], "any", false, false, false, 58), "html", null, true);
        yield "</p>
                                ";
        // line 59
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "customer_phone", [], "any", false, false, false, 59)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p class=\"text-gray-600\">Tel: ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "customer_phone", [], "any", false, false, false, 59), "html", null, true);
            yield "</p>";
        }
        // line 60
        yield "                                ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "customer_email", [], "any", false, false, false, 60)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p class=\"text-gray-600\">E-Mail: ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "customer_email", [], "any", false, false, false, 60), "html", null, true);
            yield "</p>";
        }
        // line 61
        yield "                            </div>
                        </dd>
                    </div>
                    
                    <!-- Issue Description -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Schadens-/Fehlerbeschreibung</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div class=\"bg-gray-50 p-4 rounded-md\">
                                <p class=\"whitespace-pre-line\">";
        // line 70
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["estimate"] ?? null), "issue_text", [], "any", false, false, false, 70), "html", null, true);
        yield "</p>
                            </div>
                        </dd>
                    </div>
                    
                    <!-- Price -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Voraussichtliche Kosten</dt>
                        <dd class=\"mt-1 text-lg font-bold text-gray-900 sm:mt-0 sm:col-span-2\">
                            ";
        // line 79
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["estimate"] ?? null), "formatted_price", [], "any", false, false, false, 79), "html", null, true);
        yield "
                        </dd>
                    </div>
                    
                    <!-- Created -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Erstellt</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            ";
        // line 87
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "created_at", [], "any", false, false, false, 87), "d.m.Y H:i"), "html", null, true);
        yield "
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class=\"mt-8 flex justify-end space-x-3\">
            <button class=\"bg-yellow-100 text-yellow-800 px-4 py-2 rounded-md text-sm hover:bg-yellow-200 transition-colors opacity-50 cursor-not-allowed\">
                Bearbeiten
            </button>
            <button class=\"bg-green-100 text-green-800 px-4 py-2 rounded-md text-sm hover:bg-green-200 transition-colors opacity-50 cursor-not-allowed\">
                E-Mail senden
            </button>
            <a href=\"";
        // line 102
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.pdf", ["id" => CoreExtension::getAttribute($this->env, $this->source, ($context["document"] ?? null), "id", [], "any", false, false, false, 102)]), "html", null, true);
        yield "\" 
               class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 transition-colors\">
                PDF herunterladen
            </a>
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
        return "estimates/show.html.twig";
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
        return array (  230 => 102,  212 => 87,  201 => 79,  189 => 70,  178 => 61,  171 => 60,  165 => 59,  161 => 58,  150 => 49,  143 => 48,  137 => 47,  133 => 46,  129 => 45,  118 => 37,  97 => 19,  90 => 15,  84 => 12,  80 => 11,  73 => 6,  66 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Kostenvoranschlag {{ document.doc_number }} - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-4xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8 flex justify-between items-start\">
            <div>
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Kostenvoranschlag {{ document.doc_number }}</h2>
                <p class=\"text-gray-600\">{{ branch.name }}</p>
            </div>
            <div class=\"flex space-x-3\">
                <a href=\"{{ url_for('estimates.index') }}\" 
                   class=\"bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-200 transition-colors\">
                    ← Zurück zur Liste
                </a>
                <a href=\"{{ url_for('estimates.pdf', {id: document.id}) }}\" 
                   class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 transition-colors\">
                    PDF herunterladen
                </a>
            </div>
        </div>

        <!-- Document Details -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-lg\">
            <div class=\"px-4 py-5 sm:px-6 border-b border-gray-200\">
                <h3 class=\"text-lg leading-6 font-medium text-gray-900\">Dokumentdetails</h3>
                <p class=\"mt-1 max-w-2xl text-sm text-gray-500\">Informationen zu diesem Kostenvoranschlag</p>
            </div>
            <div class=\"border-t border-gray-200\">
                <dl>
                    <!-- Document Number -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Dokumentnummer</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">{{ document.doc_number }}</dd>
                    </div>
                    
                    <!-- Branch -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Filiale</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div>
                                <p class=\"font-medium\">{{ branch.name }}</p>
                                <p class=\"text-gray-600\">{{ branch.full_address }}</p>
                                {% if branch.phone %}<p class=\"text-gray-600\">Tel: {{ branch.phone }}</p>{% endif %}
                                {% if branch.email %}<p class=\"text-gray-600\">E-Mail: {{ branch.email }}</p>{% endif %}
                            </div>
                        </dd>
                    </div>
                    
                    <!-- Customer -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Kunde</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div>
                                <p class=\"font-medium\">{{ document.customer_name }}</p>
                                {% if document.customer_phone %}<p class=\"text-gray-600\">Tel: {{ document.customer_phone }}</p>{% endif %}
                                {% if document.customer_email %}<p class=\"text-gray-600\">E-Mail: {{ document.customer_email }}</p>{% endif %}
                            </div>
                        </dd>
                    </div>
                    
                    <!-- Issue Description -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Schadens-/Fehlerbeschreibung</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            <div class=\"bg-gray-50 p-4 rounded-md\">
                                <p class=\"whitespace-pre-line\">{{ estimate.issue_text }}</p>
                            </div>
                        </dd>
                    </div>
                    
                    <!-- Price -->
                    <div class=\"bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Voraussichtliche Kosten</dt>
                        <dd class=\"mt-1 text-lg font-bold text-gray-900 sm:mt-0 sm:col-span-2\">
                            {{ estimate.formatted_price }}
                        </dd>
                    </div>
                    
                    <!-- Created -->
                    <div class=\"bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6\">
                        <dt class=\"text-sm font-medium text-gray-500\">Erstellt</dt>
                        <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">
                            {{ document.created_at|date('d.m.Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class=\"mt-8 flex justify-end space-x-3\">
            <button class=\"bg-yellow-100 text-yellow-800 px-4 py-2 rounded-md text-sm hover:bg-yellow-200 transition-colors opacity-50 cursor-not-allowed\">
                Bearbeiten
            </button>
            <button class=\"bg-green-100 text-green-800 px-4 py-2 rounded-md text-sm hover:bg-green-200 transition-colors opacity-50 cursor-not-allowed\">
                E-Mail senden
            </button>
            <a href=\"{{ url_for('estimates.pdf', {id: document.id}) }}\" 
               class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 transition-colors\">
                PDF herunterladen
            </a>
        </div>
    </div>
</div>
{% endblock %}", "estimates/show.html.twig", "/var/www/html/templates/estimates/show.html.twig");
    }
}
