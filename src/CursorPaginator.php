<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator;

/**
 * Paginador baseado em cursor para APIs e infinite scroll
 * PHP 8.5: tipagem estrita, readonly properties
 *
 * Vantagens sobre paginação OFFSET/LIMIT:
 * - Performance constante em grandes volumes (sem COUNT(*))
 * - Consistência: não duplica/perde registros durante paginação
 * - Ideal para feeds, timelines, listas ordenadas por timestamp/ID
 *
 * Uso típico:
 * SELECT * FROM posts WHERE id < :cursor ORDER BY id DESC LIMIT 20
 */
class CursorPaginator
{
 private int $limit;
 private ?string $cursor = null;
 private ?string $previousCursor = null;
 private ?string $nextCursor = null;
 private string $cursorColumn;
 private string $direction;
 private bool $hasMore = false;

 /** @var array<int, object> */
 private array $results = [];

 /**
 * @param int $limit Quantidade de itens por página
 * @param string $cursorColumn Coluna usada como cursor (ex: 'id', 'created_at')
 * @param string $direction Direção: 'desc' (mais recentes primeiro) ou 'asc'
 */
 public function __construct(
 int $limit = 20,
 string $cursorColumn = 'id',
 string $direction = 'desc',
 ) {
 $this->limit = max(1, $limit);
 $this->cursorColumn = $cursorColumn;
 $this->direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
 }

 /**
 * Processa resultados da query e extrai cursores
 *
 * @param array<int, object> $results Resultados da query (já com LIMIT + 1)
 * @return static
 */
 public function process(array $results): static
 {
 // Se retornou mais que o limite, há próxima página
 if (count($results) > $this->limit) {
 $this->hasMore = true;
 array_pop($results); // Remove o item extra
 } else {
 $this->hasMore = false;
 }

 $this->results = $results;

 // Calcula cursores
 if ($this->results !== []) {
 $last = end($this->results);
 $first = reset($this->results);

 $cursorValue = $last->{$this->cursorColumn} ?? null;
 $this->nextCursor = $cursorValue !== null ? (string)$cursorValue : null;

 $firstValue = $first->{$this->cursorColumn} ?? null;
 $this->previousCursor = $firstValue !== null ? (string)$firstValue : null;
 } else {
 $this->nextCursor = null;
 $this->previousCursor = null;
 }

 return $this;
 }

 /**
 * Retorna resultados processados
 *
 * @return array<int, object>
 */
 public function results(): array
 {
 return $this->results;
 }

 /**
 * Retorna cursor para próxima página (null se não houver)
 */
 public function nextCursor(): ?string
 {
 return $this->hasMore ? $this->nextCursor : null;
 }

 /**
 * Retorna cursor para página anterior (null se for primeira página)
 */
 public function previousCursor(): ?string
 {
 return $this->previousCursor;
 }

 /**
 * Verifica se existe próxima página
 */
 public function hasMore(): bool
 {
 return $this->hasMore;
 }

 /**
 * Verifica se existe página anterior
 * Nota: depende do contexto; retorna true se previousCursor foi definido
 */
 public function hasPrevious(): bool
 {
 return $this->previousCursor !== null && $this->cursor !== null;
 }

 /**
 * Define cursor atual (para detecção de hasPrevious)
 */
 public function setCurrentCursor(?string $cursor): static
 {
 $this->cursor = $cursor;
 return $this;
 }

 /**
 * Gera cláusula WHERE SQL para paginação
 *
 * @param string|null $cursor Valor do cursor da requisição
 * @return string Cláusula WHERE (sem WHERE keyword, só a condição)
 */
 public function whereClause(?string $cursor): string
 {
 if ($cursor === null || $cursor === '') {
 return '';
 }

 $operator = $this->direction === 'desc' ? '<' : '>';
 return "{$this->cursorColumn} {$operator} :cursor";
 }

 /**
 * Gera cláusula ORDER BY SQL
 */
 public function orderByClause(): string
 {
 $dir = strtoupper($this->direction);
 return "{$this->cursorColumn} {$dir}";
 }

 /**
 * Retorna o limite configurado
 */
 public function limit(): int
 {
 return $this->limit;
 }

 /**
 * Retorna nome da coluna do cursor
 */
 public function cursorColumn(): string
 {
 return $this->cursorColumn;
 }

 /**
 * Retorna a direção configurada
 */
 public function direction(): string
 {
 return $this->direction;
 }

 /**
 * Retorna metadados da paginação para API JSON
 *
 * @return array{
 * data: array<int, object>,
 * meta: array{
 * limit: int,
 * cursor_column: string,
 * direction: string,
 * has_more: bool,
 * next_cursor: string|null,
 * previous_cursor: string|null,
 * count: int
 * }
 * }
 */
 public function toArray(): array
 {
 return [
 'data' => $this->results,
 'meta' => [
 'limit' => $this->limit,
 'cursor_column' => $this->cursorColumn,
 'direction' => $this->direction,
 'has_more' => $this->hasMore,
 'next_cursor' => $this->nextCursor(),
 'previous_cursor' => $this->previousCursor(),
 'count' => count($this->results),
 ],
 ];
 }

 /**
 * Converte para JSON response pronto para API
 */
 public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): string
 {
 return json_encode($this->toArray(), $flags) ?: '{}';
 }
}