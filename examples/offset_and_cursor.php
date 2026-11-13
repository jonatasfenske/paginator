<?php

declare(strict_types=1);

/**
 * Exemplo de paginação offset-based e cursor-based com Paginator
 * Execute: php examples/offset_and_cursor.php
 */

require __DIR__ . '/../vendor/autoload.php';

use JonatasFenske\Paginator\Paginator;
use JonatasFenske\Paginator\PaginatorTemplate;
use JonatasFenske\Paginator\CursorPaginator;

// =============================================
// 1. PAGINAÇÃO OFFSET (HTML tradicional)
// =============================================
echo "=== PAGINAÇÃO OFFSET ===\n\n";

$pager = new Paginator();
$pager->pager(
 rows: 150,
 limit: 10,
 page: 3,
 range: 3,
 hash: 'lista',
 params: ['category' => 'tech'],
);

echo "Páginas totais: {$pager->pages()}\n";
echo "Página atual: {$pager->page()}\n";
echo "Offset SQL: {$pager->offset()}\n";
echo "Limite: {$pager->limit()}\n\n";

// Template Bootstrap 5
echo "--- Bootstrap 5 ---\n";
echo PaginatorTemplate::render($pager, 'bootstrap') . "\n\n";

// Template Tailwind CSS
echo "--- Tailwind CSS ---\n";
echo PaginatorTemplate::render($pager, 'tailwind') . "\n\n";

// Template Minimalista
echo "--- Minimalista ---\n";
echo PaginatorTemplate::render($pager, 'minimal') . "\n\n";

// =============================================
// 2. PAGINAÇÃO CURSOR-BASED (API / Infinite Scroll)
// =============================================
echo "=== PAGINAÇÃO CURSOR-BASED ===\n\n";

$cursorPaginator = new CursorPaginator(
 limit: 5,
 cursorColumn: 'id',
 direction: 'desc',
);

// Simular resultados de query (LIMIT + 1 para detectar hasMore)
$mockResults = [
 (object)['id' => 100, 'title' => 'Post 100'],
 (object)['id' => 99, 'title' => 'Post 99'],
 (object)['id' => 98, 'title' => 'Post 98'],
 (object)['id' => 97, 'title' => 'Post 97'],
 (object)['id' => 96, 'title' => 'Post 96'],
 (object)['id' => 95, 'title' => 'Post 95'], // extra item → hasMore = true
];

$cursorPaginator->setCurrentCursor(null)->process($mockResults);

echo "Resultados: " . count($cursorPaginator->results()) . "\n";
echo "Has more: " . ($cursorPaginator->hasMore() ? 'Sim' : 'Não') . "\n";
echo "Next cursor: " . ($cursorPaginator->nextCursor() ?? 'null') . "\n\n";

// Cláusulas SQL geradas automaticamente
echo "WHERE clause: " . $cursorPaginator->whereClause('96') . "\n";
echo "ORDER BY: " . $cursorPaginator->orderByClause() . "\n\n";

// Resposta JSON para API
echo "--- Resposta JSON API ---\n";
echo $cursorPaginator->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

// Segunda página (com cursor)
$secondPageResults = [
 (object)['id' => 95, 'title' => 'Post 95'],
 (object)['id' => 94, 'title' => 'Post 94'],
 (object)['id' => 93, 'title' => 'Post 93'],
];

$cursorPaginator->setCurrentCursor('96')->process($secondPageResults);
echo "Segunda página - Has more: " . ($cursorPaginator->hasMore() ? 'Sim' : 'Não') . "\n";
echo "Segunda página - Has previous: " . ($cursorPaginator->hasPrevious() ? 'Sim' : 'Não') . "\n";

echo "\n✅ Exemplo concluído!\n";