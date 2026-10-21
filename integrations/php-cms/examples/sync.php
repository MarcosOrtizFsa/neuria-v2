<?php

use Neuria\Connector\ContentSynchronizer;
use Neuria\Connector\Contracts\ContentRepository;
use Neuria\Connector\Contracts\CursorStore;
use Neuria\Connector\NeuriaApiClient;

require dirname(__DIR__, 3).'/vendor/autoload.php';
require dirname(__DIR__).'/src/Contracts/NeuriaClient.php';
require dirname(__DIR__).'/src/Contracts/ContentRepository.php';
require dirname(__DIR__).'/src/Contracts/CursorStore.php';
require dirname(__DIR__).'/src/NeuriaApiClient.php';
require dirname(__DIR__).'/src/ContentSynchronizer.php';

// Reemplazar estos adaptadores anónimos por repositorios del CMS real.
$stateFile = __DIR__.'/sync-state.json';
$state = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : ['cursors' => [], 'contents' => [], 'changes' => []];
$repository = new class($state) implements ContentRepository
{
    public function __construct(public array &$state) {}

    public function exists(string $neuriaId): bool
    {
        return isset($this->state['contents'][$neuriaId]);
    }

    public function importDraft(array $content): void
    {
        $this->state['contents'][$content['id']] = ['status' => 'draft', 'original' => $content];
    }

    public function recordChange(array $change): void
    {
        $this->state['changes'][$change['id']] = $change;
    }
};
$cursorStore = new class($state) implements CursorStore
{
    public function __construct(public array &$state) {}

    public function get(string $stream): ?string
    {
        return $this->state['cursors'][$stream] ?? null;
    }

    public function put(string $stream, ?string $cursor): void
    {
        $this->state['cursors'][$stream] = $cursor;
    }
};
$client = new NeuriaApiClient(getenv('NEURIA_API_URL') ?: '', getenv('NEURIA_API_TOKEN') ?: '');
$result = (new ContentSynchronizer($client, $repository, $cursorStore))->run();
file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($result, JSON_UNESCAPED_UNICODE).PHP_EOL;
