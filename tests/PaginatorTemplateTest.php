<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator\Tests;

use JonatasFenske\Paginator\Paginator;
use JonatasFenske\Paginator\PaginatorTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do PaginatorTemplate para PHP 8.5
 */
final class PaginatorTemplateTest extends TestCase
{
    private function createConfiguredPaginator(int $rows = 100, int $limit = 10, int $page = 3): Paginator
    {
        $paginator = new Paginator();
        $paginator->pager(rows: $rows, limit: $limit, page: $page);
        return $paginator;
    }

    public function testAvailableReturnsAllTemplates(): void
    {
        $available = PaginatorTemplate::available();

        self::assertContains('bootstrap', $available);
        self::assertContains('tailwind', $available);
        self::assertContains('minimal', $available);
    }

    public function testBootstrapTemplateGeneratesValidHtml(): void
    {
        $paginator = $this->createConfiguredPaginator();
        $html = PaginatorTemplate::render($paginator, 'bootstrap');

        self::assertNotNull($html);
        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('pagination', $html);
        self::assertStringContainsString('page-item', $html);
        self::assertStringContainsString('page-link', $html);
        self::assertStringContainsString('active', $html);
        self::assertStringContainsString('</nav>', $html);
    }

    public function testTailwindTemplateGeneratesValidHtml(): void
    {
        $paginator = $this->createConfiguredPaginator();
        $html = PaginatorTemplate::render($paginator, 'tailwind');

        self::assertNotNull($html);
        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('flex', $html);
        self::assertStringContainsString('rounded', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function testMinimalTemplateGeneratesHtml(): void
    {
        $paginator = $this->createConfiguredPaginator();
        $html = PaginatorTemplate::render($paginator, 'minimal');

        self::assertNotNull($html);
        self::assertStringContainsString('paginator', $html);
    }

    public function testRenderReturnsNullWhenNoPaginationNeeded(): void
    {
        $paginator = new Paginator();
        $paginator->pager(rows: 5, limit: 10, page: 1);

        $html = PaginatorTemplate::render($paginator, 'bootstrap');

        self::assertNull($html);
    }

    public function testInvalidTemplateThrowsException(): void
    {
        $paginator = $this->createConfiguredPaginator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template desconhecido');
        PaginatorTemplate::render($paginator, 'nonexistent');
    }

    public function testRegisterCustomTemplate(): void
    {
        PaginatorTemplate::register('custom', [
            'nav_class' => 'my-nav',
            'item_class' => 'my-item',
            'link_class' => 'my-link',
            'active_class' => 'my-active',
            'disabled_class' => 'my-disabled',
            'nav_tag' => 'div',
            'wrapper_tag' => 'div',
            'wrapper_attrs' => '',
        ]);

        self::assertContains('custom', PaginatorTemplate::available());
    }

    public function testBootstrapContainsActivePageNumber(): void
    {
        $paginator = $this->createConfiguredPaginator(rows: 100, limit: 10, page: 5);
        $html = PaginatorTemplate::render($paginator, 'bootstrap');

        self::assertNotNull($html);
        self::assertStringContainsString('>5</span>', $html);
    }

    public function testTailwindContainsActivePageNumber(): void
    {
        $paginator = $this->createConfiguredPaginator(rows: 100, limit: 10, page: 5);
        $html = PaginatorTemplate::render($paginator, 'tailwind');

        self::assertNotNull($html);
        self::assertStringContainsString('>5</span>', $html);
    }

    public function testBootstrapWithFirstAndLastPageButtons(): void
    {
        $paginator = $this->createConfiguredPaginator(rows: 200, limit: 10, page: 10);
        $html = PaginatorTemplate::render($paginator, 'bootstrap', fixedFirstAndLastPage: true);

        self::assertNotNull($html);
        self::assertStringContainsString('<<', $html);
        self::assertStringContainsString('>>', $html);
    }

    public function testDifferentPagesGenerateDifferentOutput(): void
    {
        $page3 = PaginatorTemplate::render($this->createConfiguredPaginator(page: 3), 'bootstrap');
        $page7 = PaginatorTemplate::render($this->createConfiguredPaginator(page: 7), 'bootstrap');

        self::assertNotSame($page3, $page7);
    }
}