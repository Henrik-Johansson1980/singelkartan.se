<?php

declare(strict_types=1);

namespace App\Data\Scb;

/** Typed POST body for a PxWebApi 2 table query. */
readonly class ScbQueryPayload
{
    /**
     * @param  array<int, array{variableCode: string, valueCodes: list<string>}>  $selection
     */
    public function __construct(
        public string $responseLanguage = 'sv',
        public array $selection = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selection' => $this->selection,
            'response' => [
                'format' => 'JSON-stat2',
                'language' => $this->responseLanguage,
            ],
        ];
    }
}
