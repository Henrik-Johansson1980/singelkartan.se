<?php

declare(strict_types=1);

namespace App\Data\Scb;

/** Represents one dimension in a JSON-stat2 dataset (e.g. "region", "age", "sex"). */
readonly class JsonStatDimension
{
    /**
     * @param  array<string, string>  $categoryLabels  Map of code → label
     * @param  list<string>  $categoryIds  Ordered list of codes
     */
    public function __construct(
        public string $label,
        public array $categoryLabels,
        public array $categoryIds,
    ) {}

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $labels = [];
        $ids = [];

        if (isset($raw['category']['label']) && is_array($raw['category']['label'])) {
            $labels = $raw['category']['label'];
        }

        if (isset($raw['category']['index']) && is_array($raw['category']['index'])) {
            /** @var array<string, int> $indexMap */
            $indexMap = $raw['category']['index'];
            // PHP casts numeric JSON keys to int; normalise back to strings.
            $ids = array_map('strval', array_keys($indexMap));
            usort($ids, fn (string $a, string $b): int => $indexMap[$a] <=> $indexMap[$b]);
        } else {
            $ids = array_keys($labels);
        }

        return new self(
            label: (string) ($raw['label'] ?? ''),
            categoryLabels: $labels,
            categoryIds: $ids,
        );
    }

    public function labelForCode(string $code): string
    {
        return $this->categoryLabels[$code] ?? $code;
    }
}
