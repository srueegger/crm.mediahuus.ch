<?php
declare(strict_types=1);

namespace App\Controllers;

use Twig\Environment;
use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController
{
    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Im Formular gewählte Kunden-ID (nur reine Ziffern), sonst null.
     */
    protected function selectedCustomerId(mixed $data): ?int
    {
        $value = is_array($data) ? ($data['customer_id'] ?? null) : null;
        if (!is_string($value) || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        return (int) $value ?: null;
    }

    protected function render(Response $response, string $template, array $data = []): Response
    {
        $body = $this->twig->render($template, $data);
        $response->getBody()->write($body);
        
        return $response;
    }
}