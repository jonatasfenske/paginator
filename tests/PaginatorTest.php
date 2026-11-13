<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator\Tests;

use JonatasFenske\Paginator\Paginator;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do componente Paginator para PHP 8.5
 */
final class PaginatorTest extends TestCase
{
    private Paginator $paginator;

    protected function setUp(): void
    {
        $this->paginator = new Paginator();
    }

    public function testPagerCalculatesPagesCorrectly(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 1);

        self::assertSame(10, $this->paginator->pages());
        self::assertSame(1, $this->paginator->page());
        self::assertSame(10, $this->paginator->limit());
        self::assertSame(0, $this->paginator->offset());
    }

    public function testPagerCalculatesOffsetForMiddlePage(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 5);

        self::assertSame(5, $this->paginator->page());
        self::assertSame(40, $this->paginator->offset());
    }

    public function testPagerClampsPageToMaxWhenExceedsTotal(): void
    {
        $this->paginator->pager(rows: 50, limit: 10, page: 99);

        self::assertSame(5, $this->paginator->page());
        self::assertSame(5, $this->paginator->pages());
    }

    public function testPagerHandlesZeroRows(): void
    {
        $this->paginator->pager(rows: 0, limit: 10, page: 1);

        // toPositive() garante mínimo de 1 página mesmo com 0 registros
        self::assertSame(1, $this->paginator->pages());
        self::assertNull($this->paginator->render());
    }

    public function testRenderReturnsNullWhenNoPaginationNeeded(): void
    {
        $this->paginator->pager(rows: 5, limit: 10, page: 1);

        self::assertNull($this->paginator->render());
    }

    public function testRenderGeneratesNavHtml(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 3);

        $html = $this->paginator->render();

        self::assertNotNull($html);
        self::assertStringContainsString('<nav class="paginator">', $html);
        self::assertStringContainsString('paginator_active', $html);
        self::assertStringContainsString('>3</span>', $html);
        self::assertStringContainsString('</nav>', $html);
    }

    public function testRenderWithCustomCssClass(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 1);

        $html = $this->paginator->render(cssClass: 'my-pagination');

        self::assertNotNull($html);
        self::assertStringContainsString('class="my-pagination"', $html);
        self::assertStringContainsString('my-pagination_item', $html);
    }

    public function testFirstPageAlwaysShownWhenFixed(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 1);

        $html = $this->paginator->firstPage(fixed: true);

        self::assertNotNull($html);
        self::assertStringContainsString('<<', $html);
        self::assertStringContainsString('?page=1', $html);
    }

    public function testFirstPageHiddenWhenOnFirstAndNotFixed(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 1);

        $html = $this->paginator->firstPage(fixed: false);

        self::assertNull($html);
    }

    public function testLastPageAlwaysShownWhenFixed(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 10);

        $html = $this->paginator->lastPage(fixed: true);

        self::assertNotNull($html);
        self::assertStringContainsString('>>', $html);
    }

    public function testLastPageHiddenWhenOnLastAndNotFixed(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 10);

        $html = $this->paginator->lastPage(fixed: false);

        self::assertNull($html);
    }

    public function testCustomConstructorParameters(): void
    {
        $paginator = new Paginator(
            link: '/blog/page/',
            title: 'Página do blog',
            first: ['Início', '«'],
            last: ['Fim', '»'],
        );

        $paginator->pager(rows: 50, limit: 5, page: 3);
        $html = $paginator->render();

        self::assertNotNull($html);
        self::assertStringContainsString('/blog/page/1', $html);
        self::assertStringContainsString('«', $html);
        self::assertStringContainsString('»', $html);
        self::assertStringContainsString('Página do blog', $html);
    }

    public function testGetParamsAreAppendedToLinks(): void
    {
        $this->paginator->pager(
            rows: 100,
            limit: 10,
            page: 2,
            params: ['category' => 'tech', 'sort' => 'date']
        );

        $html = $this->paginator->render();

        self::assertNotNull($html);
        self::assertStringContainsString('category=tech', $html);
        self::assertStringContainsString('sort=date', $html);
    }

    public function testHashFragmentIsAppendedToLinks(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 2, hash: 'resultados');

        $html = $this->paginator->render();

        self::assertNotNull($html);
        self::assertStringContainsString('#resultados', $html);
    }

    public function testRangeControlsAdjacentPages(): void
    {
        $this->paginator->pager(rows: 200, limit: 10, page: 10, range: 2);

        $html = $this->paginator->render();

        self::assertNotNull($html);
        // Com range=2 e página 10, devem aparecer páginas 8,9 antes e 11,12 depois
        self::assertStringContainsString('>8</a>', $html);
        self::assertStringContainsString('>9</a>', $html);
        self::assertStringContainsString('>11</a>', $html);
        self::assertStringContainsString('>12</a>', $html);
        // Página 7 não deve aparecer (fora do range)
        self::assertStringNotContainsString('>7</a>', $html);
    }

    public function testAriaLabelsForAccessibility(): void
    {
        $this->paginator->pager(rows: 100, limit: 10, page: 3);

        $html = $this->paginator->render();

        self::assertNotNull($html);
        // Página ativa usa span, não link — verifica aria-label nos links adjacentes
        self::assertStringContainsString('aria-label="Página 1"', $html);
        self::assertStringContainsString('aria-label="Página 2"', $html);
        self::assertStringContainsString('aria-label="Primeira página"', $html);
        self::assertStringContainsString('aria-label="Última página"', $html);
    }
}