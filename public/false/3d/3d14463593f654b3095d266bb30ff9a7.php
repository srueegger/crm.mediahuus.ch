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

/* users/index.html.twig */
class __TwigTemplate_27118744adc0d4fabe4ee47bc556ba6d extends Template
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
        yield "Benutzerverwaltung - ";
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
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Benutzerverwaltung</h2>
                <p class=\"text-gray-600\">Verwalten Sie Benutzerkonten für das CRM-System</p>
            </div>
            <a href=\"";
        // line 14
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.create"), "html", null, true);
        yield "\" 
               class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                Neuer Benutzer
            </a>
        </div>

        <!-- Success/Error Messages -->
        ";
        // line 22
        yield "        
        ";
        // line 23
        if ((($tmp = ($context["success"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 24
            yield "            <div class=\"mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded\">
                ";
            // line 25
            if ((($context["success"] ?? null) == "created")) {
                // line 26
                yield "                    Benutzer erfolgreich erstellt.
                ";
            } elseif ((            // line 27
($context["success"] ?? null) == "updated")) {
                // line 28
                yield "                    Benutzer erfolgreich aktualisiert.
                ";
            } elseif ((            // line 29
($context["success"] ?? null) == "activated")) {
                // line 30
                yield "                    Benutzer erfolgreich aktiviert.
                ";
            } elseif ((            // line 31
($context["success"] ?? null) == "deactivated")) {
                // line 32
                yield "                    Benutzer erfolgreich deaktiviert.
                ";
            }
            // line 34
            yield "            </div>
        ";
        }
        // line 36
        yield "
        ";
        // line 37
        if ((($tmp = ($context["error"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 38
            yield "            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                ";
            // line 39
            if ((($context["error"] ?? null) == "notfound")) {
                // line 40
                yield "                    Benutzer nicht gefunden.
                ";
            } elseif ((            // line 41
($context["error"] ?? null) == "fetch")) {
                // line 42
                yield "                    Fehler beim Laden des Benutzers.
                ";
            } elseif ((            // line 43
($context["error"] ?? null) == "update")) {
                // line 44
                yield "                    Fehler beim Aktualisieren des Benutzers.
                ";
            } elseif ((            // line 45
($context["error"] ?? null) == "status_change")) {
                // line 46
                yield "                    Fehler beim Ändern des Benutzerstatus.
                ";
            } elseif ((            // line 47
($context["error"] ?? null) == "self_action")) {
                // line 48
                yield "                    Sie können sich nicht selbst deaktivieren.
                ";
            } else {
                // line 50
                yield "                    Ein Fehler ist aufgetreten.
                ";
            }
            // line 52
            yield "            </div>
        ";
        }
        // line 54
        yield "
        <!-- Users Table -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            ";
        // line 57
        if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), ($context["users"] ?? null)) > 0)) {
            // line 58
            yield "                <ul class=\"divide-y divide-gray-200\">
                    ";
            // line 59
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["users"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["userItem"]) {
                // line 60
                yield "                        <li class=\"px-6 py-4\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center\">
                                            <span class=\"text-sm font-medium text-gray-700\">
                                                ";
                // line 66
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(Twig\Extension\CoreExtension::upper($this->env->getCharset(), Twig\Extension\CoreExtension::slice($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "name", [], "any", false, false, false, 66), 0, 1)), "html", null, true);
                yield "
                                            </span>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                ";
                // line 73
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "name", [], "any", false, false, false, 73), "html", null, true);
                yield "
                                            </p>
                                            ";
                // line 75
                if ((($tmp =  !CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "is_active", [], "any", false, false, false, 75)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 76
                    yield "                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800\">
                                                    Inaktiv
                                                </span>
                                            ";
                } else {
                    // line 80
                    yield "                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800\">
                                                    Aktiv
                                                </span>
                                            ";
                }
                // line 84
                yield "                                        </div>
                                        <p class=\"text-sm text-gray-500\">
                                            ";
                // line 86
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "email", [], "any", false, false, false, 86), "html", null, true);
                yield "
                                        </p>
                                        <p class=\"text-xs text-gray-400\">
                                            Erstellt: ";
                // line 89
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "created_at", [], "any", false, false, false, 89), "d.m.Y H:i"), "html", null, true);
                yield "
                                        </p>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    <!-- Edit Button -->
                                    <a href=\"";
                // line 96
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.edit", ["id" => CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "id", [], "any", false, false, false, 96)]), "html", null, true);
                yield "\" 
                                       class=\"bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200 transition-colors\">
                                        Bearbeiten
                                    </a>
                                    
                                    <!-- Status Toggle Button -->
                                    ";
                // line 102
                if ((CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "id", [], "any", false, false, false, 102) != CoreExtension::getAttribute($this->env, $this->source, ($context["user"] ?? null), "id", [], "any", false, false, false, 102))) {
                    // line 103
                    yield "                                        <form method=\"POST\" action=\"";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.toggle", ["id" => CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "id", [], "any", false, false, false, 103)]), "html", null, true);
                    yield "\" class=\"inline\">
                                            ";
                    // line 104
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["userItem"], "is_active", [], "any", false, false, false, 104)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 105
                        yield "                                                <input type=\"hidden\" name=\"action\" value=\"deactivate\">
                                                <button type=\"submit\" 
                                                        class=\"bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition-colors\"
                                                        onclick=\"return confirm('Benutzer deaktivieren?')\">
                                                    Deaktivieren
                                                </button>
                                            ";
                    } else {
                        // line 112
                        yield "                                                <input type=\"hidden\" name=\"action\" value=\"activate\">
                                                <button type=\"submit\" 
                                                        class=\"bg-green-100 text-green-700 px-3 py-1 rounded text-sm hover:bg-green-200 transition-colors\">
                                                    Aktivieren
                                                </button>
                                            ";
                    }
                    // line 118
                    yield "                                        </form>
                                    ";
                } else {
                    // line 120
                    yield "                                        <span class=\"text-xs text-gray-400\">(Sie)</span>
                                    ";
                }
                // line 122
                yield "                                </div>
                            </div>
                        </li>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['userItem'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 126
            yield "                </ul>
            ";
        } else {
            // line 128
            yield "                <div class=\"text-center py-12\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z\" />
                    </svg>
                    <h3 class=\"mt-2 text-sm font-medium text-gray-900\">Keine Benutzer</h3>
                    <p class=\"mt-1 text-sm text-gray-500\">Erstellen Sie den ersten Benutzer.</p>
                    <div class=\"mt-6\">
                        <a href=\"";
            // line 135
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getFunction('url_for')->getCallable()("users.create"), "html", null, true);
            yield "\" 
                           class=\"inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700\">
                            Neuer Benutzer
                        </a>
                    </div>
                </div>
            ";
        }
        // line 142
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
        return "users/index.html.twig";
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
        return array (  312 => 142,  302 => 135,  293 => 128,  289 => 126,  280 => 122,  276 => 120,  272 => 118,  264 => 112,  255 => 105,  253 => 104,  248 => 103,  246 => 102,  237 => 96,  227 => 89,  221 => 86,  217 => 84,  211 => 80,  205 => 76,  203 => 75,  198 => 73,  188 => 66,  180 => 60,  176 => 59,  173 => 58,  171 => 57,  166 => 54,  162 => 52,  158 => 50,  154 => 48,  152 => 47,  149 => 46,  147 => 45,  144 => 44,  142 => 43,  139 => 42,  137 => 41,  134 => 40,  132 => 39,  129 => 38,  127 => 37,  124 => 36,  120 => 34,  116 => 32,  114 => 31,  111 => 30,  109 => 29,  106 => 28,  104 => 27,  101 => 26,  99 => 25,  96 => 24,  94 => 23,  91 => 22,  81 => 14,  71 => 6,  64 => 5,  52 => 3,  41 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"base.html.twig\" %}

{% block title %}Benutzerverwaltung - {{ parent() }}{% endblock %}

{% block content %}
<div class=\"max-w-7xl mx-auto py-6 sm:px-6 lg:px-8\">
    <div class=\"px-4 py-6 sm:px-0\">
        <!-- Header -->
        <div class=\"mb-8 flex justify-between items-center\">
            <div>
                <h2 class=\"text-2xl font-bold text-gray-900 mb-2\">Benutzerverwaltung</h2>
                <p class=\"text-gray-600\">Verwalten Sie Benutzerkonten für das CRM-System</p>
            </div>
            <a href=\"{{ url_for('users.create') }}\" 
               class=\"bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors\">
                Neuer Benutzer
            </a>
        </div>

        <!-- Success/Error Messages -->
        {# Note: These would normally come from query parameters, simplified for now #}
        
        {% if success %}
            <div class=\"mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded\">
                {% if success == 'created' %}
                    Benutzer erfolgreich erstellt.
                {% elseif success == 'updated' %}
                    Benutzer erfolgreich aktualisiert.
                {% elseif success == 'activated' %}
                    Benutzer erfolgreich aktiviert.
                {% elseif success == 'deactivated' %}
                    Benutzer erfolgreich deaktiviert.
                {% endif %}
            </div>
        {% endif %}

        {% if error %}
            <div class=\"mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded\">
                {% if error == 'notfound' %}
                    Benutzer nicht gefunden.
                {% elseif error == 'fetch' %}
                    Fehler beim Laden des Benutzers.
                {% elseif error == 'update' %}
                    Fehler beim Aktualisieren des Benutzers.
                {% elseif error == 'status_change' %}
                    Fehler beim Ändern des Benutzerstatus.
                {% elseif error == 'self_action' %}
                    Sie können sich nicht selbst deaktivieren.
                {% else %}
                    Ein Fehler ist aufgetreten.
                {% endif %}
            </div>
        {% endif %}

        <!-- Users Table -->
        <div class=\"bg-white shadow overflow-hidden sm:rounded-md\">
            {% if users|length > 0 %}
                <ul class=\"divide-y divide-gray-200\">
                    {% for userItem in users %}
                        <li class=\"px-6 py-4\">
                            <div class=\"flex items-center justify-between\">
                                <div class=\"flex items-center space-x-4\">
                                    <div class=\"flex-shrink-0\">
                                        <div class=\"h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center\">
                                            <span class=\"text-sm font-medium text-gray-700\">
                                                {{ userItem.name|slice(0,1)|upper }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class=\"flex-1 min-w-0\">
                                        <div class=\"flex items-center space-x-3\">
                                            <p class=\"text-sm font-medium text-gray-900\">
                                                {{ userItem.name }}
                                            </p>
                                            {% if not userItem.is_active %}
                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800\">
                                                    Inaktiv
                                                </span>
                                            {% else %}
                                                <span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800\">
                                                    Aktiv
                                                </span>
                                            {% endif %}
                                        </div>
                                        <p class=\"text-sm text-gray-500\">
                                            {{ userItem.email }}
                                        </p>
                                        <p class=\"text-xs text-gray-400\">
                                            Erstellt: {{ userItem.created_at|date('d.m.Y H:i') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class=\"flex items-center space-x-2\">
                                    <!-- Edit Button -->
                                    <a href=\"{{ url_for('users.edit', {id: userItem.id}) }}\" 
                                       class=\"bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200 transition-colors\">
                                        Bearbeiten
                                    </a>
                                    
                                    <!-- Status Toggle Button -->
                                    {% if userItem.id != user.id %}
                                        <form method=\"POST\" action=\"{{ url_for('users.toggle', {id: userItem.id}) }}\" class=\"inline\">
                                            {% if userItem.is_active %}
                                                <input type=\"hidden\" name=\"action\" value=\"deactivate\">
                                                <button type=\"submit\" 
                                                        class=\"bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition-colors\"
                                                        onclick=\"return confirm('Benutzer deaktivieren?')\">
                                                    Deaktivieren
                                                </button>
                                            {% else %}
                                                <input type=\"hidden\" name=\"action\" value=\"activate\">
                                                <button type=\"submit\" 
                                                        class=\"bg-green-100 text-green-700 px-3 py-1 rounded text-sm hover:bg-green-200 transition-colors\">
                                                    Aktivieren
                                                </button>
                                            {% endif %}
                                        </form>
                                    {% else %}
                                        <span class=\"text-xs text-gray-400\">(Sie)</span>
                                    {% endif %}
                                </div>
                            </div>
                        </li>
                    {% endfor %}
                </ul>
            {% else %}
                <div class=\"text-center py-12\">
                    <svg class=\"mx-auto h-12 w-12 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z\" />
                    </svg>
                    <h3 class=\"mt-2 text-sm font-medium text-gray-900\">Keine Benutzer</h3>
                    <p class=\"mt-1 text-sm text-gray-500\">Erstellen Sie den ersten Benutzer.</p>
                    <div class=\"mt-6\">
                        <a href=\"{{ url_for('users.create') }}\" 
                           class=\"inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700\">
                            Neuer Benutzer
                        </a>
                    </div>
                </div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}", "users/index.html.twig", "/var/www/html/templates/users/index.html.twig");
    }
}
