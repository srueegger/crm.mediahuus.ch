<?php
declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Hauptnavigation (Sidebar und Dashboard-Kacheln) an einer Stelle.
 */
class NavigationExtension extends AbstractExtension
{
    private const GROUPS = [
        [
            'label' => 'Erfassen',
            'action' => 'Erstellen',
            'items' => [
                ['key' => 'estimates', 'label' => 'Kostenvoranschlag', 'url' => '/estimates/create', 'prefix' => '/estimates', 'icon' => 'estimate'],
                ['key' => 'repairs', 'label' => 'Reparatur', 'url' => '/repairs/create', 'prefix' => '/repairs/create', 'icon' => 'repair'],
                ['key' => 'receipts', 'label' => 'Quittung', 'url' => '/receipts/create', 'prefix' => '/receipts', 'icon' => 'receipt'],
                ['key' => 'invoices', 'label' => 'Rechnung', 'url' => '/invoices/create', 'prefix' => '/invoices', 'icon' => 'invoice'],
                ['key' => 'purchases', 'label' => 'Ankauf', 'url' => '/purchases/create', 'prefix' => '/purchases', 'icon' => 'purchase'],
                ['key' => 'insurance', 'label' => 'Gutachten', 'url' => '/insurance/create', 'prefix' => '/insurance', 'icon' => 'insurance'],
            ],
        ],
        [
            'label' => 'Verwalten',
            'action' => 'Verwalten',
            'items' => [
                ['key' => 'repairs_open', 'label' => 'Offene Reparaturen', 'url' => '/repairs', 'prefix' => '/repairs', 'icon' => 'repair'],
                ['key' => 'customers', 'label' => 'Kunden', 'url' => '/customers', 'prefix' => '/customers', 'icon' => 'customers'],
                ['key' => 'users', 'label' => 'Benutzer', 'url' => '/users', 'prefix' => '/users', 'icon' => 'users'],
            ],
        ],
    ];

    private string $path;

    public function __construct(?string $path = null)
    {
        $path ??= (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->path = $path !== '' ? $path : '/';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nav_active', [$this, 'isActive']),
            new TwigFunction('nav_groups', [$this, 'groups']),
        ];
    }

    /**
     * '/' ist nur auf dem Dashboard aktiv, sonst zählt der Pfad-Abschnitt samt Unterseiten.
     */
    public function isActive(string $prefix): bool
    {
        if ($prefix === '/') {
            return $this->path === '/';
        }

        return $this->path === $prefix || str_starts_with($this->path, $prefix . '/');
    }

    /**
     * @return list<array{label: string, action: string, items: list<array<string, string>>}>
     */
    public function groups(): array
    {
        return self::GROUPS;
    }
}
