<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator\Tests;

use JonatasFenske\Paginator\CursorPaginator;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários do CursorPaginator para PHP 8.5
 */
final class CursorPaginatorTest extends TestCase
{
    public function testProcessWithMoreResultsThanLimit(): void
    {
        $paginator = new CursorPaginator(limit: 3, cursorColumn: 'id');

        // Simula query que retorna LIMIT + 1 (4 itens quando limite é 3)
        $results = [
            (object)['id' => 10, 'title' => 'Post 10'],
            (object)['id' => 9, 'title' => 'Post 9'],
            (object)['id' => 8, 'title' => 'Post 8'],
            (object)['id' => 7, 'title' => 'Post 7'], // extra item
        ];

        $paginator->process($results);

        self::assertCount(3, $paginator->results());
        self::assertTrue($paginator->hasMore());
        self::assertSame('8', $paginator->nextCursor());
    }

    public function testProcessWithExactLimitResults(): void
    {
        $paginator = new CursorPaginator(limit: 3, cursorColumn: 'id');

        $results = [
            (object)['id' => 10, 'title' => 'Post 10'],
            (object)['id' => 9, 'title' => 'Post 9'],
            (object)['id' => 8, 'title' => 'Post 8'],
        ];

        $paginator->process($results);

        self::assertCount(3, $paginator->results());
        self::assertFalse($paginator->hasMore());
        self::assertNull($paginator->nextCursor());
    }

    public function testProcessWithEmptyResults(): void
    {
        $paginator = new CursorPaginator(limit: 10);
        $paginator->process([]);

        self::assertSame([], $paginator->results());
        self::assertFalse($paginator->hasMore());
        self::assertNull($paginator->nextCursor());
        self::assertNull($paginator->previousCursor());
    }

    public function testNextCursorIsNullWhenNoMorePages(): void
    {
        $paginator = new CursorPaginator(limit: 5);
        $paginator->process([(object)['id' => 1]]);

        self::assertNull($paginator->nextCursor());
    }

    public function testPreviousCursorIsSetFromFirstResult(): void
    {
        $paginator = new CursorPaginator(limit: 2, cursorColumn: 'id');

        $results = [
            (object)['id' => 20],
            (object)['id' => 19],
            (object)['id' => 18], // extra
        ];

        $paginator->setCurrentCursor('21')->process($results);

        self::assertSame('20', $paginator->previousCursor());
    }

    public function testHasPreviousRequiresCurrentCursor(): void
    {
        $paginator = new CursorPaginator(limit: 2);

        $results = [
            (object)['id' => 20],
            (object)['id' => 19],
            (object)['id' => 18],
        ];

        // Sem setCurrentCursor → hasPrevious = false
        $paginator->process($results);
        self::assertFalse($paginator->hasPrevious());

        // Com setCurrentCursor → hasPrevious = true
        $paginator->setCurrentCursor('21')->process($results);
        self::assertTrue($paginator->hasPrevious());
    }

    public function testWhereClauseDescDirection(): void
    {
        $paginator = new CursorPaginator(cursorColumn: 'id', direction: 'desc');

        self::assertSame('id < :cursor', $paginator->whereClause('100'));
    }

    public function testWhereClauseAscDirection(): void
    {
        $paginator = new CursorPaginator(cursorColumn: 'created_at', direction: 'asc');

        self::assertSame('created_at > :cursor', $paginator->whereClause('2025-01-01'));
    }

    public function testWhereClauseReturnsEmptyForNullCursor(): void
    {
        $paginator = new CursorPaginator();

        self::assertSame('', $paginator->whereClause(null));
        self::assertSame('', $paginator->whereClause(''));
    }

    public function testOrderByClause(): void
    {
        $desc = new CursorPaginator(cursorColumn: 'id', direction: 'desc');
        $asc = new CursorPaginator(cursorColumn: 'created_at', direction: 'asc');

        self::assertSame('id DESC', $desc->orderByClause());
        self::assertSame('created_at ASC', $asc->orderByClause());
    }

    public function testLimitClampedToMinimumOne(): void
    {
        $paginator = new CursorPaginator(limit: 0);

        self::assertSame(1, $paginator->limit());
    }

    public function testToArrayReturnsCompleteMetadata(): void
    {
        $paginator = new CursorPaginator(limit: 2, cursorColumn: 'id', direction: 'desc');

        $results = [
            (object)['id' => 10, 'name' => 'A'],
            (object)['id' => 9, 'name' => 'B'],
            (object)['id' => 8, 'name' => 'C'],
        ];

        $paginator->process($results);
        $array = $paginator->toArray();

        self::assertArrayHasKey('data', $array);
        self::assertArrayHasKey('meta', $array);
        self::assertCount(2, $array['data']);
        self::assertSame(2, $array['meta']['limit']);
        self::assertSame('id', $array['meta']['cursor_column']);
        self::assertSame('desc', $array['meta']['direction']);
        self::assertTrue($array['meta']['has_more']);
        self::assertSame('9', $array['meta']['next_cursor']);
        self::assertSame(2, $array['meta']['count']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $paginator = new CursorPaginator(limit: 5);
        $paginator->process([(object)['id' => 1]]);

        $json = $paginator->toJson();
        $decoded = json_decode($json, true);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('data', $decoded);
        self::assertArrayHasKey('meta', $decoded);
    }

    public function testCustomCursorColumn(): void
    {
        $paginator = new CursorPaginator(cursorColumn: 'published_at');

        self::assertSame('published_at', $paginator->cursorColumn());
        self::assertStringContainsString('published_at', $paginator->orderByClause());
        self::assertStringContainsString('published_at', $paginator->whereClause('2025-06-15'));
    }

    public function testDirectionDefaultsToDesc(): void
    {
        $paginator = new CursorPaginator();

        self::assertSame('desc', $paginator->direction());
    }

    public function testInvalidDirectionDefaultsToDesc(): void
    {
        $paginator = new CursorPaginator(direction: 'invalid');

        self::assertSame('desc', $paginator->direction());
    }

    public function testFluentSetCurrentCursorReturnsSelf(): void
    {
        $paginator = new CursorPaginator();
        $result = $paginator->setCurrentCursor('abc');

        self::assertInstanceOf(CursorPaginator::class, $result);
    }

    public function testFluentProcessReturnsSelf(): void
    {
        $paginator = new CursorPaginator();
        $result = $paginator->process([]);

        self::assertInstanceOf(CursorPaginator::class, $result);
    }
}