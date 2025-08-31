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

    protected function render(Response $response, string $template, array $data = []): Response
    {
        $body = $this->twig->render($template, $data);
        $response->getBody()->write($body);
        
        return $response;
    }
}