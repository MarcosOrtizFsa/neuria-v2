<?php

namespace Neuria\Connector;

use Neuria\Connector\Contracts\ContentRepository;
use Neuria\Connector\Contracts\CursorStore;
use Neuria\Connector\Contracts\NeuriaClient;

final class ContentSynchronizer
{
    public function __construct(private NeuriaClient $client, private ContentRepository $repository, private CursorStore $cursors) {}

    /** @return array{imported:int,changes:int} */
    public function run(int $limit = 50): array
    {
        $imported = 0;
        do {
            $contents = $this->client->contents($this->cursors->get('contents'), $limit);
            foreach ($contents['data'] as $content) {
                if (! $this->repository->exists($content['id'])) {
                    $this->repository->importDraft($content);
                    $imported++;
                }
            }
            if (isset($contents['meta']['next_cursor'])) {
                $this->cursors->put('contents', $contents['meta']['next_cursor']);
            }
        } while ($contents['meta']['has_more'] ?? false);

        $changed = 0;
        do {
            $changes = $this->client->changes($this->cursors->get('changes'), $limit);
            foreach ($changes['data'] as $change) {
                $this->repository->recordChange($change);
                $changed++;
            }
            if (isset($changes['meta']['next_cursor'])) {
                $this->cursors->put('changes', $changes['meta']['next_cursor']);
            }
        } while ($changes['meta']['has_more'] ?? false);

        return ['imported' => $imported, 'changes' => $changed];
    }
}
