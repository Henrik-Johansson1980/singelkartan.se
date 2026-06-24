<?php

declare(strict_types=1);

namespace App\Data\Scb;

/** Top-level JSON-stat2 dataset response from SCB PxWebApi 2. */
readonly class JsonStatDataset
{
    /**
     * @param  list<string>  $id  Dimension ids in order
     * @param  list<int>  $size  Number of categories per dimension
     * @param  array<string, JsonStatDimension>  $dimensions  Map of id → dimension
     * @param  list<int|float|null>  $values  Flat array of observation values
     */
    public function __construct(
        public array $id,
        public array $size,
        public array $dimensions,
        public array $values,
    ) {}

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $id = is_array($raw['id']) ? $raw['id'] : [];
        $size = is_array($raw['size']) ? $raw['size'] : [];
        $rawDimensions = is_array($raw['dimension']) ? $raw['dimension'] : [];

        $dimensions = [];
        foreach ($rawDimensions as $dimId => $dimData) {
            if (is_array($dimData)) {
                $dimensions[(string) $dimId] = JsonStatDimension::fromArray($dimData);
            }
        }

        $values = is_array($raw['value']) ? array_values($raw['value']) : [];

        return new self(
            id: array_values($id),
            size: array_values($size),
            dimensions: $dimensions,
            values: $values,
        );
    }

    public function dimensionIndex(string $dimensionId): int
    {
        $index = array_search($dimensionId, $this->id, true);

        return $index !== false ? (int) $index : -1;
    }
}
