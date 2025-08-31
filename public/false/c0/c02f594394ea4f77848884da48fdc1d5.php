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

/* base.html.twig */
class __TwigTemplate_77e6bc4a40a1b5f116f750bfa3790137 extends Template
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

        $this->parent = false;

        $this->blocks = [
            'title' => [$this, 'block_title'],
            'header' => [$this, 'block_header'],
            'content' => [$this, 'block_content'],
            'scripts' => [$this, 'block_scripts'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<!DOCTYPE html>
<html lang=\"de\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>";
        // line 6
        yield from $this->unwrap()->yieldBlock('title', $context, $blocks);
        yield "</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-50 min-h-screen\">
    ";
        // line 10
        yield from $this->unwrap()->yieldBlock('header', $context, $blocks);
        // line 33
        yield "
    <main>
        ";
        // line 35
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 36
        yield "    </main>

    ";
        // line 38
        yield from $this->unwrap()->yieldBlock('scripts', $context, $blocks);
        // line 39
        yield "</body>
</html>";
        yield from [];
    }

    // line 6
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_title(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_name"] ?? null), "html", null, true);
        yield from [];
    }

    // line 10
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_header(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 11
        yield "    ";
        if ((($tmp = ($context["user"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 12
            yield "    <nav class=\"bg-white shadow-sm border-b\">
        <div class=\"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8\">
            <div class=\"flex justify-between h-16\">
                <div class=\"flex items-center\">
                    <a href=\"/\" class=\"flex items-center hover:opacity-75 transition-opacity\">
                        <img src=\"/assets/logo.png\" alt=\"";
            // line 17
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["app_name"] ?? null), "html", null, true);
            yield "\" class=\"h-12 w-auto\">
                    </a>
                </div>
                <div class=\"flex items-center space-x-4\">
                    <span class=\"text-sm text-gray-700\">";
            // line 21
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, ($context["user"] ?? null), "name", [], "any", false, false, false, 21), "html", null, true);
            yield "</span>
                    <form method=\"POST\" action=\"/logout\" class=\"inline\">
                        <button type=\"submit\" class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700\">
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    ";
        }
        // line 32
        yield "    ";
        yield from [];
    }

    // line 35
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    // line 38
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_scripts(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "base.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  144 => 38,  134 => 35,  129 => 32,  115 => 21,  108 => 17,  101 => 12,  98 => 11,  91 => 10,  80 => 6,  74 => 39,  72 => 38,  68 => 36,  66 => 35,  62 => 33,  60 => 10,  53 => 6,  46 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<!DOCTYPE html>
<html lang=\"de\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{% block title %}{{ app_name }}{% endblock %}</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-50 min-h-screen\">
    {% block header %}
    {% if user %}
    <nav class=\"bg-white shadow-sm border-b\">
        <div class=\"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8\">
            <div class=\"flex justify-between h-16\">
                <div class=\"flex items-center\">
                    <a href=\"/\" class=\"flex items-center hover:opacity-75 transition-opacity\">
                        <img src=\"/assets/logo.png\" alt=\"{{ app_name }}\" class=\"h-12 w-auto\">
                    </a>
                </div>
                <div class=\"flex items-center space-x-4\">
                    <span class=\"text-sm text-gray-700\">{{ user.name }}</span>
                    <form method=\"POST\" action=\"/logout\" class=\"inline\">
                        <button type=\"submit\" class=\"bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700\">
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    {% endif %}
    {% endblock %}

    <main>
        {% block content %}{% endblock %}
    </main>

    {% block scripts %}{% endblock %}
</body>
</html>", "base.html.twig", "/var/www/html/templates/base.html.twig");
    }
}
