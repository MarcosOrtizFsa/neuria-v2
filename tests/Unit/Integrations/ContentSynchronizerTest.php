<?php

namespace Tests\Unit\Integrations;

use Neuria\Connector\ContentSynchronizer;
use Neuria\Connector\Contracts\ContentRepository;
use Neuria\Connector\Contracts\CursorStore;
use Neuria\Connector\Contracts\NeuriaClient;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../integrations/php-cms/src/Contracts/NeuriaClient.php';
require_once __DIR__.'/../../../integrations/php-cms/src/Contracts/ContentRepository.php';
require_once __DIR__.'/../../../integrations/php-cms/src/Contracts/CursorStore.php';
require_once __DIR__.'/../../../integrations/php-cms/src/ContentSynchronizer.php';

class ContentSynchronizerTest extends TestCase
{
    public function test_it_imports_only_new_content_and_persists_both_cursors(): void
    {
        $client = new class implements NeuriaClient
        {
            public function contents(?string $cursor = null, int $limit = 50): array
            {
                return ['data' => [['id' => 'existing'], ['id' => 'new']], 'meta' => ['next_cursor' => 'content-cursor']];
            }

            public function changes(?string $cursor = null, int $limit = 50): array
            {
                return ['data' => [['id' => 'change-1']], 'meta' => ['next_cursor' => 'change-cursor']];
            }
        };
        $repository = new class implements ContentRepository
        {
            public array $imported = [];

            public array $changes = [];

            public function exists(string $neuriaId): bool
            {
                return $neuriaId === 'existing';
            }

            public function importDraft(array $content): void
            {
                $this->imported[] = $content['id'];
            }

            public function recordChange(array $change): void
            {
                $this->changes[] = $change['id'];
            }
        };
        $cursors = new class implements CursorStore
        {
            public array $values = [];

            public function get(string $stream): ?string
            {
                return $this->values[$stream] ?? null;
            }

            public function put(string $stream, ?string $cursor): void
            {
                $this->values[$stream] = $cursor;
            }
        };

        $result = (new ContentSynchronizer($client, $repository, $cursors))->run();

        self::assertSame(['imported' => 1, 'changes' => 1], $result);
        self::assertSame(['new'], $repository->imported);
        self::assertSame(['change-1'], $repository->changes);
        self::assertSame(['contents' => 'content-cursor', 'changes' => 'change-cursor'], $cursors->values);
    }
}
