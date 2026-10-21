<?php

namespace App\Contracts;

interface AutomationProvider
{
    /** @return array<string, mixed> */
    public function interpret(string $request): array;

    /** @return array{status:string,title:?string,body:?string,warnings:list<string>,sources:list<array<string,mixed>>,provider:array<string,mixed>,usage:list<array<string,mixed>>} */
    public function generate(array $configuration, \DateTimeInterface $windowFrom, \DateTimeInterface $windowTo): array;
}
