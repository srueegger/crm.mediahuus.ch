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

/* users/edit.html.twig */
class __TwigTemplate_1bef4715803eb9702ed0a1e90ae0a2d7 extends Template
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
        yield "Benutzer bearbeiten - ";
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
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Benutzer bearbeiten</h2>
            <p class=\"text-gray-600\">Bearbeiten Sie die Daten für ";
        // line 11
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "name", [], "any", false, false, false, 11), "html", null, true);
        yield "</p>
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
            <form method=\"POST\" action=\"";
        // line 23
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.update", ["id" => CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "id", [], "any", false, false, false, 23)]), "html", null, true);
        yield "\" class=\"space-y-6 p-6\">
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
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "name", [], "any", false, false, false, 32), "html", null, true);
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
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "email", [], "any", false, false, false, 49), "html", null, true);
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
                        Neues Passwort
                    </label>
                    <input type=\"password\" 
                           name=\"password\" 
                           id=\"password\" 
                           minlength=\"6\"
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ";
        // line 67
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 67)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("border-red-300") : (""));
        yield "\" 
                           placeholder=\"Leer lassen, um Passwort beizubehalten\">
                    ";
        // line 69
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 69)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 70
            yield "                        <p class=\"mt-1 text-sm text-red-600\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["errors"] ?? null), "password", [], "any", false, false, false, 70), "html", null, true);
            yield "</p>
                    ";
        }
        // line 72
        yield "                    <p class=\"mt-1 text-sm text-gray-500\">Lassen Sie das Feld leer, um das aktuelle Passwort beizubehalten.</p>
                </div>

                <!-- Active Status -->
                <div class=\"flex items-center\">
                    <input type=\"checkbox\" 
                           name=\"is_active\" 
                           id=\"is_active\" 
                           value=\"1\"
                           ";
        // line 81
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "is_active", [], "any", false, false, false, 81)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "checked";
        }
        // line 82
        yield "                           class=\"h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded\">
                    <label for=\"is_active\" class=\"ml-2 block text-sm text-gray-900\">
                        Benutzer ist aktiv
                    </label>
                </div>
                <p class=\"text-sm text-gray-500\">Nur aktive Benutzer können sich anmelden.</p>

                <!-- User Info -->
                <div class=\"bg-gray-50 p-4 rounded-lg\">
                    <h4 class=\"text-sm font-medium text-gray-900 mb-2\">Benutzer-Informationen</h4>
                    <div class=\"text-sm text-gray-600 space-y-1\">
                        <p><span class=\"font-medium\">Erstellt:</span> ";
        // line 93
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "created_at", [], "any", false, false, false, 93), "d.m.Y H:i"), "html", null, true);
        yield "</p>
                        <p><span class=\"font-medium\">Zuletzt geändert:</span> ";
        // line 94
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["editUser"] ?? null), "updated_at", [], "any", false, false, false, 94), "d.m.Y H:i"), "html", null, true);
        yield "</p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"";
        // line 100
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.index"), "html", null, true);
        yield "\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Änderungen speichern
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
        return "users/edit.html.twig";
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
        return array (  228 => 100,  219 => 94,  215 => 93,  202 => 82,  198 => 81,  187 => 72,  181 => 70,  179 => 69,  174 => 67,  161 => 56,  155 => 54,  153 => 53,  148 => 51,  143 => 49,  131 => 39,  125 => 37,  123 => 36,  118 => 34,  113 => 32,  101 => 23,  96 => 20,  90 => 17,  87 => 16,  85 => 15,  78 => 11,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Benutzer bearbeiten - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-2xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8\">
            <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Benutzer bearbeiten</h2>
            <p class=\"text-gray-600\">Bearbeiten Sie die Daten für {{ editUser.name }}</p>
        </div>

        <!-- Error Message -->
        {% if error %}
            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                {{ error }}
            </div>
        {% endif %}

        <!-- Form -->
        <div class=\"bg-white shadow rounded-lg\">
            <form method=\"POST\" action=\"{{ url_for('users.update', {id: editUser.id}) }}\" class=\"space-y-6 p-6\">
                <!-- Name -->
                <div>
                    <label for=\"name\" class=\"block text-sm font-medium text-gray-700\">
                        Name *
                    </label>
                    <input type=\"text\" 
                           name=\"name\" 
                           id=\"name\" 
                           value=\"{{ editUser.name }}\"
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
                           value=\"{{ editUser.email }}\"
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
                        Neues Passwort
                    </label>
                    <input type=\"password\" 
                           name=\"password\" 
                           id=\"password\" 
                           minlength=\"6\"
                           class=\"mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ errors.password ? 'border-red-300' : '' }}\" 
                           placeholder=\"Leer lassen, um Passwort beizubehalten\">
                    {% if errors.password %}
                        <p class=\"mt-1 text-sm text-red-600\">{{ errors.password }}</p>
                    {% endif %}
                    <p class=\"mt-1 text-sm text-gray-500\">Lassen Sie das Feld leer, um das aktuelle Passwort beizubehalten.</p>
                </div>

                <!-- Active Status -->
                <div class=\"flex items-center\">
                    <input type=\"checkbox\" 
                           name=\"is_active\" 
                           id=\"is_active\" 
                           value=\"1\"
                           {% if editUser.is_active %}checked{% endif %}
                           class=\"h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded\">
                    <label for=\"is_active\" class=\"ml-2 block text-sm text-gray-900\">
                        Benutzer ist aktiv
                    </label>
                </div>
                <p class=\"text-sm text-gray-500\">Nur aktive Benutzer können sich anmelden.</p>

                <!-- User Info -->
                <div class=\"bg-gray-50 p-4 rounded-lg\">
                    <h4 class=\"text-sm font-medium text-gray-900 mb-2\">Benutzer-Informationen</h4>
                    <div class=\"text-sm text-gray-600 space-y-1\">
                        <p><span class=\"font-medium\">Erstellt:</span> {{ editUser.created_at|date('d.m.Y H:i') }}</p>
                        <p><span class=\"font-medium\">Zuletzt geändert:</span> {{ editUser.updated_at|date('d.m.Y H:i') }}</p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class=\"flex justify-end space-x-3 pt-6 border-t\">
                    <a href=\"{{ url_for('users.index') }}\" 
                       class=\"bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Abbrechen
                    </a>
                    <button type=\"submit\" 
                            class=\"bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">
                        Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{% endblock %}", "users/edit.html.twig", "/var/www/html/templates/users/edit.html.twig");
    }
}
