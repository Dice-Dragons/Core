<?php

namespace cv6\Core\Icon\Provider;

interface IconProviderInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getPrefix(): string;

    public function getBrandIcon(): string;

    public function getBrandColor(): string;

    /**
     * Array of available style abbreviations and their human-readable labels.
     * e.g. ['fas' => 'Solid', 'far' => 'Regular', ...] or ['gmo' => 'Outlined', ...]
     *
     * @return array<string, string>
     */
    public function getStyles(): array;

    /**
     * Render an icon to HTML (SVG or icon element).
     *
     * @param string $name Raw name without prefix or with prefix
     * @param string|null $style Style variant (e.g. 'fas', 'gmo')
     * @param array $options Additional options (class, title, attributes, inline)
     * @return string
     */
    public function render(string $name, ?string $style = null, array $options = []): string;

    /**
     * Return CSS mask URL (e.g. url('data:image/svg+xml;utf8,...') or url('path/to.svg'))
     * for this icon to be used in LESS / CSS masks (.m-faIcon).
     *
     * @param string $name
     * @param string|null $style
     * @return string|null
     */
    public function getMaskUrl(string $name, ?string $style = null): ?string;

    /**
     * Search icons by query string. Supports names and keywords/tags.
     * Returns an array of search results matching the autocomplete format:
     * [
     *     [
     *         'id' => 'fa-cog',
     *         'text' => 'fa-cog',
     *         'icon' => 'fa-cog',
     *         'name' => 'cog',
     *         'variant' => 'solid',
     *         'provider' => 'fa'
     *     ], ...
     * ]
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search(string $query, int $limit = 30): array;

    /**
     * Check if a given icon name is valid for this provider.
     *
     * @param string $name
     * @return bool
     */
    public function isValidIcon(string $name): bool;

    /**
     * Mapping from/to other icon providers (e.g. ['fa-cog' => 'gm-settings']).
     *
     * @return array<string, string>
     */
    public function getMigrationMap(): array;

    /**
     * Style mappings to a target provider (e.g. ['far' => 'gmo', 'fas' => 'gmf']).
     *
     * @param string $targetProviderId
     * @return array<string, string>
     */
    public function getStyleMap(string $targetProviderId): array;

    /**
     * Incoming style mappings from a source provider (e.g. converting FA styles into this provider's styles).
     *
     * @param string $sourceProviderId
     * @return array<string, string>
     */
    public function getIncomingStyleMap(string $sourceProviderId): array;
}
