<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CustomerRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class CustomerController extends BaseController
{
    private const SEARCH_MIN_LENGTH = 2;
    private const SEARCH_LIMIT = 10;

    public function __construct(Environment $twig, private CustomerRepository $customers)
    {
        parent::__construct($twig);
    }

    /**
     * Kundensuche für den Picker in den Erfassungsformularen (JSON).
     */
    public function search(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';

        $result = [];
        if (mb_strlen($query) >= self::SEARCH_MIN_LENGTH) {
            foreach ($this->customers->search($query, self::SEARCH_LIMIT) as $customer) {
                $result[] = $customer->toArray();
            }
        }

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
