<?php

namespace App\Console\Commands;

use App\Services\Mqtt\Broker1PublisherService;
use Illuminate\Console\Command;
use Throwable;

class MqttBroker1PublishWorkerCommand extends Command
{
    protected $signature = 'mqtt:broker1:publish-worker {--sleep=1} {--limit=100}';
    protected $description = 'Publish pending messages from DB to broker 1 and delete them on success';

    private bool $running = true;

    public function __construct(
        private readonly Broker1PublisherService $publisherService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sleepSeconds = max(1, (int) $this->option('sleep'));
        $limit = max(1, (int) $this->option('limit'));

        $this->trapSignals();

        $this->info('Broker 1 publisher worker started.');

        while ($this->running) {
            try {
                $sent = $this->publisherService->flush($limit);

                if ($sent > 0) {
                    $this->line("Published {$sent} message(s) to broker 1.");
                } else {
                    sleep($sleepSeconds);
                }
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                sleep($sleepSeconds);
            }
        }

        $this->info('Broker 1 publisher worker stopped.');
        return self::SUCCESS;
    }

    private function trapSignals(): void
    {
        if (! function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () {
            $this->running = false;
        });

        pcntl_signal(SIGTERM, function () {
            $this->running = false;
        });
    }
}