<?php

namespace Neuria\Connector\Contracts;

interface CursorStore
{
    public function get(string $stream): ?string;

    public function put(string $stream, ?string $cursor): void;
}
