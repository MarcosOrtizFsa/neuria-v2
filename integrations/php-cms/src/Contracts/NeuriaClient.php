<?php

namespace Neuria\Connector\Contracts;

interface NeuriaClient
{
    /** @return array{data: array, meta: array} */
    public function contents(?string $cursor = null, int $limit = 50): array;

    /** @return array{data: array, meta: array} */
    public function changes(?string $cursor = null, int $limit = 50): array;
}
