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

/* users/create.html.twig */
class __TwigTemplate_9c3efa9311c8ff1fa93e78dd867620e2 extends Template
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
        yield "Neuer Benutzer - ";
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
        yield "<div class=\"max-w-2xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Neuer Benutzer</h2>
            <p class=\"text-gray-600\">Erstellen Sie einen neuen Benutzer für das CRM-System</p>
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
            <form method=\"POST\" action=\"/users\" class=\"space-y-6 p-6\">
                <!-- Name -->
                <div>
                    <label for=\"name\" class=\"block text-sm font-medium text-gray-700\">
                        Name *
                    </label>
                    <input type=\"text\" 
                           name=\"name\" 
                           id=\"name\" 
                           value=\"";
        // line 32
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "name", [], "any", true, true, false, 32)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "name", [], "any", false, false, false, 32), "")) : ("")), "html", null, true);
        yield "\"
                           required
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 34
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "name", [], "any", false, false, false, 34)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\" 
                           placeholder=\"Vollständiger Name\">
                    ";
        // line 36
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "name", [], "any", false, false, false, 36)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 37
            yield "                        <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "name", [], "any", false, false, false, 37), "html", null, true);
            yield "</p>
                    ";
        }
        // line 39
        yield "                </div>

                <!-- Email -->
                <div>
                    <label for=\"email\" class=\"block text-sm font-medium text-gray-700\">
                        E-Mail-Adresse *
                    </label>
                    <input type=\"email\" 
                           name=\"email\" 
                           id=\"email\" 
                           value=\"";
        // line 49
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "email", [], "any", true, true, false, 49)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "email", [], "any", false, false, false, 49), "")) : ("")), "html", null, true);
        yield "\"
                           required
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 51
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "email", [], "any", false, false, false, 51)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\" 
                           placeholder=\"benutzer@mediahuus.ch\">
                    ";
        // line 53
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "email", [], "any", false, false, false, 53)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 54
            yield "                        <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "email", [], "any", false, false, false, 54), "html", null, true);
            yield "</p>
                    ";
        }
        // line 56
        yield "                </div>

                <!-- Password -->
                <div>
                    <label for=\"password\" class=\"block text-sm font-medium text-gray-700\">
                        Passwort *
                    </label>
                    <input type=\"password\" 
                           name=\"password\" 
                           id=\"password\" 
                           required
                           minlength=\"6\"
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 68
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 68)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\" 
                           placeholder=\"Mindestens 6 Zeichen\">
                    ";
        // line 70
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 70)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 71
            yield "                        <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 71), "html", null, true);
            yield "</p>
                    ";
        }
        // line 73
        yield "                    <p class=\"mt-1 text-sm text-gray-500\">Das Passwort muss mindestens 6 Zeichen lang sein.</p>
                </div>

                <!-- Active Status -->
                <div class=\"flex items-center\">
                    <input type=\"checkbox\" 
                           name=\"is_active\" 
                           id=\"is_active\" 
                           value=\"1\"
                           ";
        // line 82
        if ((($tmp = ((CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "is_active", [], "any", true, true, false, 82)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["formData"] ?? null), "is_active", [], "any", false, false, false, 82), true)) : (true))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "checked";
        }
        // line 83
        yield "                           class=\"h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded\">
                    <label for=\"is_active\" class=\"ml-2 block text-sm text-gray-900\">
                        Benutzer ist aktiv
                    </label>
                </div>
                <p class=\"text-sm text-gray-500\">Nur aktive Benutzer können sich anmelden.</p>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"";
        // line 92
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.index"), "html", null, true);
        yield "\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Benutzer erstellen
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
        return "users/create.html.twig";
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
        return array (  208 => 92,  197 => 83,  193 => 82,  182 => 73,  176 => 71,  174 => 70,  169 => 68,  155 => 56,  149 => 54,  147 => 53,  142 => 51,  137 => 49,  125 => 39,  119 => 37,  117 => 36,  112 => 34,  107 => 32,  93 => 20,  87 => 17,  84 => 16,  82 => 15,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Neuer Benutzer - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-2xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Neuer Benutzer</h2>
            <p class=\"text-gray-600\">Erstellen Sie einen neuen Benutzer für das CRM-System</p>
        </div>

        <!-- Error Message -->
        {% if error %}
            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                {{ error }}
            </div>
        {% endif %}

        <!-- Form -->
        <div class=\"bg-white shadow rounded-lg\">
            <form method=\"POST\" action=\"/users\" class=\"space-y-6 p-6\">
                <!-- Name -->
                <div>
                    <label for=\"name\" class=\"block text-sm font-medium text-gray-700\">
                        Name *
                    </label>
                    <input type=\"text\" 
                           name=\"name\" 
                           id=\"name\" 
                           value=\"{{ formData.name|default('') }}\"
                           required
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.name ? 'border-red-300' : '' }}\" 
                           placeholder=\"Vollständiger Name\">
                    {% if errors.name %}
                        <p class=\"mt-1 text-sm text-red-600\">{{ errors.name }}</p>
                    {% endif %}
                </div>

                <!-- Email -->
                <div>
                    <label for=\"email\" class=\"block text-sm font-medium text-gray-700\">
                        E-Mail-Adresse *
                    </label>
                    <input type=\"email\" 
                           name=\"email\" 
                           id=\"email\" 
                           value=\"{{ formData.email|default('') }}\"
                           required
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.email ? 'border-red-300' : '' }}\" 
                           placeholder=\"benutzer@mediahuus.ch\">
                    {% if errors.email %}
                        <p class=\"mt-1 text-sm text-red-600\">{{ errors.email }}</p>
                    {% endif %}
                </div>

                <!-- Password -->
                <div>
                    <label for=\"password\" class=\"block text-sm font-medium text-gray-700\">
                        Passwort *
                    </label>
                    <input type=\"password\" 
                           name=\"password\" 
                           id=\"password\" 
                           required
                           minlength=\"6\"
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.password ? 'border-red-300' : '' }}\" 
                           placeholder=\"Mindestens 6 Zeichen\">
                    {% if errors.password %}
                        <p class=\"mt-1 text-sm text-red-600\">{{ errors.password }}</p>
                    {% endif %}
                    <p class=\"mt-1 text-sm text-gray-500\">Das Passwort muss mindestens 6 Zeichen lang sein.</p>
                </div>

                <!-- Active Status -->
                <div class=\"flex items-center\">
                    <input type=\"checkbox\" 
                           name=\"is_active\" 
                           id=\"is_active\" 
                           value=\"1\"
                           {% if formData.is_active|default(true) %}checked{% endif %}
                           class=\"h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded\">
                    <label for=\"is_active\" class=\"ml-2 block text-sm text-gray-900\">
                        Benutzer ist aktiv
                    </label>
                </div>
                <p class=\"text-sm text-gray-500\">Nur aktive Benutzer können sich anmelden.</p>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"{{ url_for('users.index') }}\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Benutzer erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{% endblock %}", "users/create.html.twig", "/var/www/html/templates/users/create.html.twig");
    }
}
