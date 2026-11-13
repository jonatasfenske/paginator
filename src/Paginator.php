<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator;

/**
 * Paginador de resultados modernizado para PHP 8.5
 * Gera navegação HTML com tipagem estrita e métodos fluentes
 */
class Paginator
{
 private ?int $page = null;
 private int $pages = 0;
 private int $rows = 0;
 private ?int $limit = null;
 private ?int $offset = null;
 private int $range = 3;
 private string $link;
 private string $title;
 private string $cssClass = 'paginator';
 private ?string $hash = null;

 /** @var array{0: string, 1: string} */
 private array $first;

 /** @var array{0: string, 1: string} */
 private array $last;

 private string $params = '';

 /**
 * @param string|null $link Prefixo do link de paginação
 * @param string|null $title Título acessível (aria-label)
 * @param array{0: string, 1: string}|null $first Texto e símbolo da primeira página
 * @param array{0: string, 1: string}|null $last Texto e símbolo da última página
 */
 public function __construct(
 ?string $link = null,
 ?string $title = null,
 ?array $first = null,
 ?array $last = null,
 ) {
 $this->link = $link ?? '?page=';
 $this->title = $title ?? 'Página';
 $this->first = $first ?? ['Primeira página', '<<'];
 $this->last = $last ?? ['Última página', '>>'];
 }

 /**
 * Configura a paginação com base nos dados
 *
 * @param int $rows Total de registros
 * @param int $limit Registros por página
 * @param int|null $page Página atual
 * @param int $range Quantidade de páginas adjacentes exibidas
 * @param string|null $hash Fragmento de URL (#ancora)
 * @param array<string, mixed> $params Parâmetros GET adicionais
 */
 public function pager(
 int $rows,
 int $limit = 10,
 ?int $page = null,
 int $range = 3,
 ?string $hash = null,
 array $params = [],
 ): void {
 $this->rows = $this->toPositive($rows);
 $this->limit = $this->toPositive($limit);
 $this->range = $this->toPositive($range);
 $this->pages = (int)ceil($this->rows / $this->limit);
 $this->page = ($page !== null && $page <= $this->pages)
 ? $this->toPositive($page)
 : $this->pages;

 $calculatedOffset = ($this->page * $this->limit) - $this->limit;
 $this->offset = $calculatedOffset >= 0 ? $calculatedOffset : 0;
 $this->hash = $hash !== null && $hash !== '' ? "#{$hash}" : null;

 $this->addGetParams($params);

 if ($this->rows > 0 && $this->offset >= $this->rows) {
 $lastPage = (int)ceil($this->rows / $this->limit);
 header("Location: {$this->link}{$lastPage}");
 exit;
 }
 }

 /**
 * Retorna o limite de registros por página
 */
 public function limit(): ?int
 {
 return $this->limit;
 }

 /**
 * Retorna o offset atual para consultas SQL
 */
 public function offset(): ?int
 {
 return $this->offset;
 }

 /**
 * Retorna a página atual
 */
 public function page(): ?int
 {
 return $this->page;
 }

 /**
 * Retorna o total de páginas
 */
 public function pages(): int
 {
 return $this->pages;
 }

 /**
 * Renderiza a navegação HTML da paginação
 *
 * @param string|null $cssClass Classe CSS personalizada
 * @param bool $fixedFirstAndLastPage Se true, sempre exibe botões de primeira/última
 * @return string|null HTML da navegação ou null se não houver paginação
 */
 public function render(?string $cssClass = null, bool $fixedFirstAndLastPage = true): ?string
 {
 if ($cssClass !== null) {
 $this->cssClass = $cssClass;
 }

 if ($this->rows <= $this->limit) {
 return null;
 }

 $html = "<nav class=\"{$this->cssClass}\">";
 $html .= $this->firstPage($fixedFirstAndLastPage);
 $html .= $this->beforePages();
 $html .= "<span class=\"{$this->cssClass}_item {$this->cssClass}_active\">{$this->page}</span>";
 $html .= $this->afterPages();
 $html .= $this->lastPage($fixedFirstAndLastPage);
 $html .= '</nav>';

 return $html;
 }

 /**
 * Gera links das páginas anteriores à atual
 */
 private function beforePages(): string
 {
 $html = '';
 $start = max(1, $this->page - $this->range);

 for ($i = $start; $i < $this->page; $i++) {
 $html .= $this->buildPageLink($i);
 }

 return $html;
 }

 /**
 * Gera links das páginas posteriores à atual
 */
 private function afterPages(): string
 {
 $html = '';
 $end = min($this->pages, $this->page + $this->range);

 for ($i = $this->page + 1; $i <= $end; $i++) {
 $html .= $this->buildPageLink($i);
 }

 return $html;
 }

 /**
 * Gera link individual de página
 */
 private function buildPageLink(int $pageNumber): string
 {
 $label = "{$this->title} {$pageNumber}";
 return "<a class=\"{$this->cssClass}_item\" aria-label=\"{$label}\" title=\"{$label}\" href=\"{$this->link}{$pageNumber}{$this->hash}{$this->params}\">{$pageNumber}</a>";
 }

 /**
 * Gera botão da primeira página
 *
 * @param bool $fixed Se true, sempre exibe; se false, oculta quando está na primeira página
 */
 public function firstPage(bool $fixed = true): ?string
 {
 if ($fixed || $this->page !== 1) {
 $label = $this->first[0];
 return "<a class=\"{$this->cssClass}_item\" aria-label=\"{$label}\" title=\"{$label}\" href=\"{$this->link}1{$this->hash}{$this->params}\">{$this->first[1]}</a>";
 }

 return null;
 }

 /**
 * Gera botão da última página
 *
 * @param bool $fixed Se true, sempre exibe; se false, oculta quando está na última página
 */
 public function lastPage(bool $fixed = true): ?string
 {
 if ($fixed || $this->page !== $this->pages) {
 $label = $this->last[0];
 return "<a class=\"{$this->cssClass}_item\" aria-label=\"{$label}\" title=\"{$label}\" href=\"{$this->link}{$this->pages}{$this->hash}{$this->params}\">{$this->last[1]}</a>";
 }

 return null;
 }

 /**
 * Garante que o número seja positivo (mínimo 1)
 */
 private function toPositive(int $number): int
 {
 return max(1, $number);
 }

 /**
 * Adiciona parâmetros GET extras aos links de paginação
 *
 * @param array<string, mixed> $params
 */
 private function addGetParams(array $params): void
 {
 $this->params = '';

 if ($params === []) {
 return;
 }

 unset($params['page']);

 if ($params !== []) {
 $this->params = '&' . http_build_query($params);
 }
 }
}