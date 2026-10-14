<?php

declare(strict_types=1);

namespace Tests\Twig;

use App\Twig\NavigationExtension;
use PHPUnit\Framework\TestCase;

class NavigationExtensionTest extends TestCase
{
    public function testDashboardIsOnlyActiveOnRoot(): void
    {
        $this->assertTrue((new NavigationExtension('/'))->isActive('/'));
        $this->assertFalse((new NavigationExtension('/customers'))->isActive('/'));
    }

    public function testPrefixMatchesSectionAndSubpages(): void
    {
        $nav = new NavigationExtension('/customers/12/edit');
        $this->assertTrue($nav->isActive('/customers'));
        $this->assertFalse($nav->isActive('/users'));
    }

    public function testPrefixDoesNotMatchLongerSegment(): void
    {
        $this->assertFalse((new NavigationExtension('/customersx'))->isActive('/customers'));
    }

    public function testCreatePageActivatesItsSection(): void
    {
        $this->assertTrue((new NavigationExtension('/estimates/create'))->isActive('/estimates'));
    }

    public function testGroupsContainAllSectionsInOrder(): void
    {
        $groups = (new NavigationExtension('/'))->groups();

        $this->assertSame(['Erfassen', 'Verwalten'], array_column($groups, 'label'));
        $this->assertSame(
            ['estimates', 'repairs', 'receipts', 'invoices', 'purchases', 'insurance'],
            array_column($groups[0]['items'], 'key')
        );
        $this->assertSame(['repairs_open', 'customers', 'users'], array_column($groups[1]['items'], 'key'));
        $this->assertSame('/estimates/create', $groups[0]['items'][0]['url']);
        $this->assertSame('/estimates', $groups[0]['items'][0]['prefix']);
        $this->assertSame('/repairs', $groups[1]['items'][0]['url']);
        $this->assertSame('/customers', $groups[1]['items'][1]['url']);
    }

    public function testRepairDetailActivatesOpenRepairs(): void
    {
        $nav = new NavigationExtension('/repairs/12');
        $this->assertTrue($nav->isActive('/repairs'));
        $this->assertFalse($nav->isActive('/repairs/create'));
    }
}
