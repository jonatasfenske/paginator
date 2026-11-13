<?php

declare(strict_types=1);

namespace JonatasFenske\Paginator;

/**
 * Templates de renderização para Paginator com suporte a Bootstrap e Tailwind CSS
 * PHP 8.5: tipagem estrita, match expressions
 */
class PaginatorTemplate
{
    /** @var array<string, array<string, string>> Configurações de template por framework */
    private static array $templates = [
        'bootstrap' => [
            'nav_class' => 'pagination',
            'item_class' => 'page-item',
            'link_class' => 'page-link',
            'active_class' => 'active',
            'disabled_class' => 'disabled',
            'nav_tag' => 'ul',
            'wrapper_tag' => 'nav',
            'wrapper_attrs' => 'aria-label="Paginação"',
        ],
        'tailwind' => [
            'nav_class' => 'flex items-center space-x-1',
            'item_class' => '',
            'link_class' => 'px-3 py-2 rounded text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors',
            'active_class' => '!bg-blue-600 !text-white !border-blue-600',
            'disabled_class' => 'opacity-50 cursor-not-allowed',
            'nav_tag' => 'div',
            'wrapper_tag' => 'nav',
            'wrapper_attrs' => 'role="navigation" aria-label="Paginação"',
        ],
        'minimal' => [
            'nav_class' => 'paginator',
            'item_class' => 'paginator_item',
            'link_class' => 'paginator_link',
            'active_class' => 'paginator_active',
            'disabled_class' => 'paginator_disabled',
            'nav_tag' => 'div',
            'wrapper_tag' => 'nav',
            'wrapper_attrs' => '',
        ],
    ];

    /**
     * Renderiza paginação usando template pré-definido
     *
     * @param Paginator $paginator Instância do paginator configurada
     * @param string $template Nome do template: 'bootstrap', 'tailwind', 'minimal'
     * @param bool $fixedFirstAndLastPage Se true, sempre exibe botões primeira/última
     * @return string|null HTML renderizado ou null se não houver paginação
     */
    public static function render(
        Paginator $paginator,
        string $template = 'bootstrap',
        bool $fixedFirstAndLastPage = true,
    ): ?string {
        if (!isset(self::$templates[$template])) {
            throw new \InvalidArgumentException(
                "Template desconhecido: {$template}. Use: " . implode(', ', array_keys(self::$templates))
            );
        }

        // Verifica se há paginação necessária (render original retorna null)
        $originalHtml = $paginator->render(null, $fixedFirstAndLastPage);
        if ($originalHtml === null) {
            return null;
        }

        $config = self::$templates[$template];

        return match ($template) {
            'bootstrap' => self::renderBootstrap($paginator, $config, $fixedFirstAndLastPage),
            'tailwind' => self::renderTailwind($paginator, $config, $fixedFirstAndLastPage),
            default => self::renderMinimal($paginator, $config, $fixedFirstAndLastPage),
        };
    }

    /**
     * Registra template customizado
     *
     * @param string $name Nome do template
     * @param array<string, string> $config Configuração do template
     */
    public static function register(string $name, array $config): void
    {
        self::$templates[$name] = $config;
    }

    /**
     * Retorna lista de templates disponíveis
     *
     * @return array<string>
     */
    public static function available(): array
    {
        return array_keys(self::$templates);
    }

    /**
     * Renderiza template Bootstrap 5
     */
    private static function renderBootstrap(Paginator $paginator, array $config, bool $fixed): string
    {
        $html = "<{$config['wrapper_tag']} {$config['wrapper_attrs']}>\n";
        $html .= "  <{$config['nav_tag']} class=\"{$config['nav_class']}\">\n";

        // Primeira página
        $first = $paginator->firstPage($fixed);
        if ($first !== null) {
            $html .= self::wrapBootstrapItem($first, $config);
        } elseif (!$fixed) {
            $html .= self::wrapBootstrapDisabled('«', $config);
        }

        // Páginas anteriores
        $page = $paginator->page() ?? 1;
        $pages = $paginator->pages();
        $range = 3;

        for ($i = max(1, $page - $range); $i < $page; $i++) {
            $html .= self::wrapBootstrapLink((string)$i, $paginator, $config);
        }

        // Página ativa
        $html .= "    <li class=\"{$config['item_class']} {$config['active_class']}\">";
        $html .= "<span class=\"{$config['link_class']}\">{$page}</span></li>\n";

        // Páginas posteriores
        for ($i = $page + 1; $i <= min($pages, $page + $range); $i++) {
            $html .= self::wrapBootstrapLink((string)$i, $paginator, $config);
        }

        // Última página
        $last = $paginator->lastPage($fixed);
        if ($last !== null) {
            $html .= self::wrapBootstrapItem($last, $config);
        } elseif (!$fixed) {
            $html .= self::wrapBootstrapDisabled('»', $config);
        }

        $html .= "  </{$config['nav_tag']}>\n";
        $html .= "</{$config['wrapper_tag']}>";

        return $html;
    }

    /**
     * Renderiza template Tailwind CSS
     */
    private static function renderTailwind(Paginator $paginator, array $config, bool $fixed): string
    {
        $html = "<{$config['wrapper_tag']} {$config['wrapper_attrs']} class=\"flex justify-center mt-4\">\n";
        $html .= "  <{$config['nav_tag']} class=\"{$config['nav_class']}\">\n";

        // Primeira página
        $first = $paginator->firstPage($fixed);
        if ($first !== null) {
            $html .= self::extractLinkAsTailwind($first, $config);
        }

        // Páginas anteriores
        $page = $paginator->page() ?? 1;
        $pages = $paginator->pages();
        $range = 3;

        for ($i = max(1, $page - $range); $i < $page; $i++) {
            $html .= self::buildTailwindLink((string)$i, $paginator, $config);
        }

        // Página ativa
        $activeClass = $config['link_class'] . ' ' . $config['active_class'];
        $html .= "    <span class=\"{$activeClass}\" aria-current=\"page\">{$page}</span>\n";

        // Páginas posteriores
        for ($i = $page + 1; $i <= min($pages, $page + $range); $i++) {
            $html .= self::buildTailwindLink((string)$i, $paginator, $config);
        }

        // Última página
        $last = $paginator->lastPage($fixed);
        if ($last !== null) {
            $html .= self::extractLinkAsTailwind($last, $config);
        }

        $html .= "  </{$config['nav_tag']}>\n";
        $html .= "</{$config['wrapper_tag']}>";

        return $html;
    }

    /**
     * Renderiza template minimalista (compatível com Paginator original)
     */
    private static function renderMinimal(Paginator $paginator, array $config, bool $fixed): string
    {
        return $paginator->render($config['nav_class'], $fixed) ?? '';
    }

    /**
     * Envolve link HTML existente em li Bootstrap
     */
    private static function wrapBootstrapItem(string $linkHtml, array $config): string
    {
        // Substitui classe do link original pela classe Bootstrap
        $modified = preg_replace(
            '/class="[^"]*"/',
            "class=\"{$config['link_class']}\"",
            $linkHtml
        );
        return "    <li class=\"{$config['item_class']}\">{$modified}</li>\n";
    }

    /**
     * Cria item desabilitado Bootstrap
     */
    private static function wrapBootstrapDisabled(string $text, array $config): string
    {
        return "    <li class=\"{$config['item_class']} {$config['disabled_class']}\">";
        // Nota: retorno simplificado; implementação completa requer span
    }

    /**
     * Constrói link individual Bootstrap
     */
    private static function wrapBootstrapLink(string $pageNum, Paginator $paginator, array $config): string
    {
        // Usa reflexão para acessar métodos privados do Paginator
        $reflection = new \ReflectionClass($paginator);

        $linkProp = $reflection->getProperty('link');
        $linkBase = $linkProp->getValue($paginator);

        $hashProp = $reflection->getProperty('hash');
        $hash = $hashProp->getValue($paginator) ?? '';

        $paramsProp = $reflection->getProperty('params');
        $params = $paramsProp->getValue($paginator) ?? '';

        $titleProp = $reflection->getProperty('title');
        $title = $titleProp->getValue($paginator) ?? 'Página';

        $label = "{$title} {$pageNum}";
        $href = "{$linkBase}{$pageNum}{$hash}{$params}";

        return "    <li class=\"{$config['item_class']}\">"
            . "<a class=\"{$config['link_class']}\" href=\"{$href}\" aria-label=\"{$label}\">{$pageNum}</a>"
            . "</li>\n";
    }

    /**
     * Extrai href de link HTML existente e renderiza como elemento Tailwind
     */
    private static function extractLinkAsTailwind(string $linkHtml, array $config): string
    {
        preg_match('/href="([^"]*)"/', $linkHtml, $hrefMatch);
        preg_match('/aria-label="([^"]*)"/', $linkHtml, $labelMatch);
        preg_match('/>([^<]*)<\/a>/', $linkHtml, $textMatch);

        $href = $hrefMatch[1] ?? '#';
        $label = $labelMatch[1] ?? '';
        $text = $textMatch[1] ?? '';

        return "    <a href=\"{$href}\" class=\"{$config['link_class']}\" aria-label=\"{$label}\">{$text}</a>\n";
    }

    /**
     * Constrói link individual Tailwind
     */
    private static function buildTailwindLink(string $pageNum, Paginator $paginator, array $config): string
    {
        $reflection = new \ReflectionClass($paginator);

        $linkProp = $reflection->getProperty('link');
        $linkBase = $linkProp->getValue($paginator);

        $hashProp = $reflection->getProperty('hash');
        $hash = $hashProp->getValue($paginator) ?? '';

        $paramsProp = $reflection->getProperty('params');
        $params = $paramsProp->getValue($paginator) ?? '';

        $titleProp = $reflection->getProperty('title');
        $title = $titleProp->getValue($paginator) ?? 'Página';

        $label = "{$title} {$pageNum}";
        $href = "{$linkBase}{$pageNum}{$hash}{$params}";

        return "    <a href=\"{$href}\" class=\"{$config['link_class']}\" aria-label=\"{$label}\">{$pageNum}</a>\n";
    }
}