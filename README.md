# JonatasFenske\Paginator

<p align="center">
 <strong>Paginação PHP 8.5 com suporte offset-based e cursor-based</strong>
</p>

<p align="center">
 <img src="https://img.shields.io/badge/PHP-8.5%2B-8892BF?logo=php" alt="PHP 8.5+">
 <img src="https://img.shields.io/badge/license-MIT-blue" alt="MIT License">
</p>

## Instalação

```bash
composer require jonatasfenske/paginator
```

## Recursos

- **Paginação offset-based** — clássica, ideal para sites tradicionais e SEO
- **Paginação cursor-based** — performance O(1) constante, ideal para APIs e infinite scroll
- **Templates CSS** — Bootstrap 5, Tailwind CSS e minimalista
- **Templates customizáveis** — via `PaginatorTemplate::register()`
- **Acessibilidade** — atributos ARIA automáticos (aria-label, aria-current)
- **Integração nativa** com `jonatasfenske/datalayer` via `paginate()`
- **Resposta JSON** completa com meta para APIs REST

## Uso Rápido

### Paginação Offset (HTML)

```php
use JonatasFenske\Paginator\Paginator;
use JonatasFenske\Paginator\PaginatorTemplate;

$pager = new Paginator();
$pager->pager(rows: 150, limit: 10, page: 3, range: 3);

// Template Bootstrap 5
echo PaginatorTemplate::render($pager, 'bootstrap');

// Template Tailwind CSS
echo PaginatorTemplate::render($pager, 'tailwind');
```

### Paginação Cursor-Based (API)

```php
use JonatasFenske\Paginator\CursorPaginator;

$paginator = new CursorPaginator(limit: 20, cursorColumn: 'id', direction: 'desc');

// Processar resultados da query (LIMIT + 1 para detectar hasMore)
$paginator->setCurrentCursor($_GET['cursor'] ?? null)->process($results);

header('Content-Type: application/json');
echo $paginator->toJson();
// {"data": [...], "meta": {"has_more": true, "next_cursor": "78", ...}}
```

### Integração com DataLayer

```php
$result = $postModel
 ->find('status = :s', 's=published')
 ->order('created_at DESC')
 ->paginate(page: 2, limit: 15);

foreach ($result['results'] as $post) { echo $post->title; }
echo $result['paginator']->render();
```

### Helpers SQL do CursorPaginator

```php
$where = $paginator->whereClause($cursor); // "id < :cursor"
$order = $paginator->orderByClause();      // "id DESC"
```

## Testes

```bash
composer test
```

## Licença

MIT