<?php

namespace App\Services\Automation;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class AutomationSchedule
{
    public function nextOccurrence(array $configuration, DateTimeInterface $after): CarbonImmutable
    {
        $timezone = $configuration['timezone'];
        $afterUtc = CarbonImmutable::instance($after)->utc();
        $localCursor = $afterUtc->setTimezone($timezone)->startOfDay();
        [$hour, $minute] = array_map('intval', explode(':', $configuration['time']));
        $allowedDays = $this->allowedDays($configuration);

        for ($offset = 0; $offset <= 14; $offset++) {
            $candidate = $localCursor->addDays($offset)->setTime($hour, $minute);
            if (in_array($candidate->dayOfWeekIso, $allowedDays, true) && $candidate->utc()->greaterThan($afterUtc)) {
                return $candidate->utc();
            }
        }

        throw new \DomainException('No se pudo calcular la próxima ejecución con la frecuencia indicada.');
    }

    public function projectedOccurrences(array $configuration, DateTimeInterface $from, int $days = 30): int
    {
        $cursor = CarbonImmutable::instance($from)->utc();
        $until = $cursor->addDays($days);
        $count = 0;

        while (true) {
            $cursor = $this->nextOccurrence($configuration, $cursor);
            if ($cursor->greaterThan($until)) {
                return $count;
            }
            $count++;
        }
    }

    /** @return list<int> */
    private function allowedDays(array $configuration): array
    {
        if ($configuration['frequency'] === 'DAILY') {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        return array_values(array_unique(array_map('intval', $configuration['days'])));
    }
}
