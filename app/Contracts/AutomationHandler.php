<?php

namespace App\Contracts;

use App\Models\AutomationRun;

interface AutomationHandler
{
    public function type(): string;

    /** @return array{status:string,title:?string,body:?string,warnings:list<string>,sources:list<array<string,mixed>>,provider:array<string,mixed>,usage:list<array<string,mixed>>} */
    public function execute(AutomationRun $run): array;
}
