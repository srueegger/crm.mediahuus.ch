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

/* estimates/create.html.twig */
class __TwigTemplate_cde074e60723647b725aec70f54fcfb7 extends Template
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
        yield "Neuer Kostenvoranschlag - ";
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
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Neuer Kostenvoranschlag</h2>
            <p class=\"text-gray-600\">Erstellen Sie einen Kostenvoranschlag für Reparatur oder Service</p>
        </div>

        <!-- Error Message -->
        ";
        // line 15
        if ((($tmp = ($context["error"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 16
            yield "            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                ";
            // line 17
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["error"] ?? null), "html", null, true);
            yield "
            </div>
        ";
        }
        // line 20
        yield "
        <!-- Form -->
        <div class=\"bg-white shadow rounded-lg\">
            <form method=\"POST\" action=\"/estimates\" class=\"p-6 space-y-8\">
                
                <!-- Filiale auswählen -->
                <div class=\"border-b border-gray-200 pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Filiale</h3>
                    <div>
                        <label for=\"branch_id\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                            Filiale auswählen *
                        </label>
                        <select name=\"branch_id\" id=\"branch_id\" required
                                class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 33
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "branch_id", [], "any", false, false, false, 33)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\">
                            <option value=\"\">-- Filiale auswählen --</option>
                            ";
        // line 35
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["branches"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["branch"]) {
            // line 36
            yield "                                <option value=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["branch"], "id", [], "any", false, false, false, 36), "html", null, true);
            yield "\" 
                                        ";
            // line 37
            if ((((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "branch_id", [], "any", true, true, false, 37)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "branch_id", [], "any", false, false, false, 37), "")) : ("")) == CoreExtension::getAttribute($this->env, $this->source, $context["branch"], "id", [], "any", false, false, false, 37))) {
                yield "selected";
            }
            yield ">
                                    ";
            // line 38
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["branch"], "name", [], "any", false, false, false, 38), "html", null, true);
            yield " - ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["branch"], "full_address", [], "any", false, false, false, 38), "html", null, true);
            yield "
                                </option>
                            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['branch'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 41
        yield "                        </select>
                        ";
        // line 42
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "branch_id", [], "any", false, false, false, 42)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 43
            yield "                            <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "branch_id", [], "any", false, false, false, 43), "html", null, true);
            yield "</p>
                        ";
        }
        // line 45
        yield "                    </div>
                </div>

                <!-- Kundendaten -->
                <div class=\"border-b border-gray-200 pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Kundendaten</h3>
                    <div class=\"grid grid-cols-1 md:grid-cols-2 gap-6\">
                        <!-- Kundenname -->
                        <div class=\"md:col-span-2\">
                            <label for=\"customer_name\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Name des Kunden *
                            </label>
                            <input type=\"text\" name=\"customer_name\" id=\"customer_name\" required
                                   value=\"";
        // line 58
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_name", [], "any", true, true, false, 58)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_name", [], "any", false, false, false, 58), "")) : ("")), "html", null, true);
        yield "\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 59
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_name", [], "any", false, false, false, 59)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\"
                                   placeholder=\"Max Mustermann\">
                            ";
        // line 61
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_name", [], "any", false, false, false, 61)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 62
            yield "                                <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_name", [], "any", false, false, false, 62), "html", null, true);
            yield "</p>
                            ";
        }
        // line 64
        yield "                        </div>

                        <!-- Telefon -->
                        <div>
                            <label for=\"customer_phone\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Telefon
                            </label>
                            <input type=\"tel\" name=\"customer_phone\" id=\"customer_phone\"
                                   value=\"";
        // line 72
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_phone", [], "any", true, true, false, 72)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_phone", [], "any", false, false, false, 72), "")) : ("")), "html", null, true);
        yield "\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 73
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_phone", [], "any", false, false, false, 73)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\"
                                   placeholder=\"+41 61 123 45 67\">
                            ";
        // line 75
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_phone", [], "any", false, false, false, 75)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 76
            yield "                                <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_phone", [], "any", false, false, false, 76), "html", null, true);
            yield "</p>
                            ";
        }
        // line 78
        yield "                        </div>

                        <!-- E-Mail -->
                        <div>
                            <label for=\"customer_email\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                E-Mail
                            </label>
                            <input type=\"email\" name=\"customer_email\" id=\"customer_email\"
                                   value=\"";
        // line 86
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_email", [], "any", true, true, false, 86)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "customer_email", [], "any", false, false, false, 86), "")) : ("")), "html", null, true);
        yield "\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 87
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_email", [], "any", false, false, false, 87)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\"
                                   placeholder=\"kunde@example.com\">
                            ";
        // line 89
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_email", [], "any", false, false, false, 89)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 90
            yield "                                <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "customer_email", [], "any", false, false, false, 90), "html", null, true);
            yield "</p>
                            ";
        }
        // line 92
        yield "                        </div>
                    </div>
                </div>

                <!-- Schadensbeschreibung und Preis -->
                <div class=\"pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Reparaturdetails</h3>
                    <div class=\"space-y-6\">
                        <!-- Schadensbeschreibung -->
                        <div>
                            <label for=\"issue_text\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Schaden/Fehlerbeschreibung *
                            </label>
                            <textarea name=\"issue_text\" id=\"issue_text\" rows=\"4\" required
                                      class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 106
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "issue_text", [], "any", false, false, false, 106)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\"
                                      placeholder=\"Beschreiben Sie den Schaden oder das Problem detailliert...\">";
        // line 107
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "issue_text", [], "any", true, true, false, 107)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "issue_text", [], "any", false, false, false, 107), "")) : ("")), "html", null, true);
        yield "</textarea>
                            ";
        // line 108
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "issue_text", [], "any", false, false, false, 108)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 109
            yield "                                <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "issue_text", [], "any", false, false, false, 109), "html", null, true);
            yield "</p>
                            ";
        }
        // line 111
        yield "                        </div>

                        <!-- Preis -->
                        <div>
                            <label for=\"price_chf\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Voraussichtliche Kosten (CHF) *
                            </label>
                            <div class=\"relative\">
                                <div class=\"absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none\">
                                    <span class=\"text-gray-500 sm:text-sm\">CHF</span>
                                </div>
                                <input type=\"number\" name=\"price_chf\" id=\"price_chf\" step=\"0.05\" min=\"0\" required
                                       value=\"";
        // line 123
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "price_chf", [], "any", true, true, false, 123)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "price_chf", [], "any", false, false, false, 123), "")) : ("")), "html", null, true);
        yield "\"
                                       class=\"block w-full pl-12 pr-3 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 124
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "price_chf", [], "any", false, false, false, 124)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\"
                                       placeholder=\"0.00\">
                            </div>
                            ";
        // line 127
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "price_chf", [], "any", false, false, false, 127)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 128
            yield "                                <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "price_chf", [], "any", false, false, false, 128), "html", null, true);
            yield "</p>
                            ";
        }
        // line 130
        yield "                            <p class=\"mt-1 text-sm text-gray-500\">Geben Sie den geschätzten Reparaturpreis in CHF ein.</p>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"";
        // line 137
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("estimates.index"), "html", null, true);
        yield "\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Kostenvoranschlag erstellen
                    </button>
                </div>
            </form>
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
        return "estimates/create.html.twig";
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
        return array (  309 => 137,  300 => 130,  294 => 128,  292 => 127,  286 => 124,  282 => 123,  268 => 111,  262 => 109,  260 => 108,  256 => 107,  252 => 106,  236 => 92,  230 => 90,  228 => 89,  223 => 87,  219 => 86,  209 => 78,  203 => 76,  201 => 75,  196 => 73,  192 => 72,  182 => 64,  176 => 62,  174 => 61,  169 => 59,  165 => 58,  150 => 45,  144 => 43,  142 => 42,  139 => 41,  128 => 38,  122 => 37,  117 => 36,  113 => 35,  108 => 33,  93 => 20,  87 => 17,  84 => 16,  82 => 15,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Neuer Kostenvoranschlag - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-4xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Neuer Kostenvoranschlag</h2>
            <p class=\"text-gray-600\">Erstellen Sie einen Kostenvoranschlag für Reparatur oder Service</p>
        </div>

        <!-- Error Message -->
        {% if error %}
            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                {{ error }}
            </div>
        {% endif %}

        <!-- Form -->
        <div class=\"bg-white shadow rounded-lg\">
            <form method=\"POST\" action=\"/estimates\" class=\"p-6 space-y-8\">
                
                <!-- Filiale auswählen -->
                <div class=\"border-b border-gray-200 pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Filiale</h3>
                    <div>
                        <label for=\"branch_id\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                            Filiale auswählen *
                        </label>
                        <select name=\"branch_id\" id=\"branch_id\" required
                                class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.branch_id ? 'border-red-300' : '' }}\">
                            <option value=\"\">-- Filiale auswählen --</option>
                            {% for branch in branches %}
                                <option value=\"{{ branch.id }}\" 
                                        {% if formData.branch_id|default('') == branch.id %}selected{% endif %}>
                                    {{ branch.name }} - {{ branch.full_address }}
                                </option>
                            {% endfor %}
                        </select>
                        {% if errors.branch_id %}
                            <p class=\"mt-1 text-sm text-red-600\">{{ errors.branch_id }}</p>
                        {% endif %}
                    </div>
                </div>

                <!-- Kundendaten -->
                <div class=\"border-b border-gray-200 pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Kundendaten</h3>
                    <div class=\"grid grid-cols-1 md:grid-cols-2 gap-6\">
                        <!-- Kundenname -->
                        <div class=\"md:col-span-2\">
                            <label for=\"customer_name\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Name des Kunden *
                            </label>
                            <input type=\"text\" name=\"customer_name\" id=\"customer_name\" required
                                   value=\"{{ formData.customer_name|default('') }}\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.customer_name ? 'border-red-300' : '' }}\"
                                   placeholder=\"Max Mustermann\">
                            {% if errors.customer_name %}
                                <p class=\"mt-1 text-sm text-red-600\">{{ errors.customer_name }}</p>
                            {% endif %}
                        </div>

                        <!-- Telefon -->
                        <div>
                            <label for=\"customer_phone\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Telefon
                            </label>
                            <input type=\"tel\" name=\"customer_phone\" id=\"customer_phone\"
                                   value=\"{{ formData.customer_phone|default('') }}\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.customer_phone ? 'border-red-300' : '' }}\"
                                   placeholder=\"+41 61 123 45 67\">
                            {% if errors.customer_phone %}
                                <p class=\"mt-1 text-sm text-red-600\">{{ errors.customer_phone }}</p>
                            {% endif %}
                        </div>

                        <!-- E-Mail -->
                        <div>
                            <label for=\"customer_email\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                E-Mail
                            </label>
                            <input type=\"email\" name=\"customer_email\" id=\"customer_email\"
                                   value=\"{{ formData.customer_email|default('') }}\"
                                   class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.customer_email ? 'border-red-300' : '' }}\"
                                   placeholder=\"kunde@example.com\">
                            {% if errors.customer_email %}
                                <p class=\"mt-1 text-sm text-red-600\">{{ errors.customer_email }}</p>
                            {% endif %}
                        </div>
                    </div>
                </div>

                <!-- Schadensbeschreibung und Preis -->
                <div class=\"pb-8\">
                    <h3 class=\"text-lg font-medium text-gray-900 mb-4\">Reparaturdetails</h3>
                    <div class=\"space-y-6\">
                        <!-- Schadensbeschreibung -->
                        <div>
                            <label for=\"issue_text\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Schaden/Fehlerbeschreibung *
                            </label>
                            <textarea name=\"issue_text\" id=\"issue_text\" rows=\"4\" required
                                      class=\"block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.issue_text ? 'border-red-300' : '' }}\"
                                      placeholder=\"Beschreiben Sie den Schaden oder das Problem detailliert...\">{{ formData.issue_text|default('') }}</textarea>
                            {% if errors.issue_text %}
                                <p class=\"mt-1 text-sm text-red-600\">{{ errors.issue_text }}</p>
                            {% endif %}
                        </div>

                        <!-- Preis -->
                        <div>
                            <label for=\"price_chf\" class=\"block text-sm font-medium text-gray-700 mb-2\">
                                Voraussichtliche Kosten (CHF) *
                            </label>
                            <div class=\"relative\">
                                <div class=\"absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none\">
                                    <span class=\"text-gray-500 sm:text-sm\">CHF</span>
                                </div>
                                <input type=\"number\" name=\"price_chf\" id=\"price_chf\" step=\"0.05\" min=\"0\" required
                                       value=\"{{ formData.price_chf|default('') }}\"
                                       class=\"block w-full pl-12 pr-3 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.price_chf ? 'border-red-300' : '' }}\"
                                       placeholder=\"0.00\">
                            </div>
                            {% if errors.price_chf %}
                                <p class=\"mt-1 text-sm text-red-600\">{{ errors.price_chf }}</p>
                            {% endif %}
                            <p class=\"mt-1 text-sm text-gray-500\">Geben Sie den geschätzten Reparaturpreis in CHF ein.</p>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"{{ url_for('estimates.index') }}\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Kostenvoranschlag erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{% endblock %}", "estimates/create.html.twig", "/var/www/html/templates/estimates/create.html.twig");
    }
}
