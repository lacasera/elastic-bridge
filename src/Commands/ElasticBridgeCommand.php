<?php

namespace Lacasera\ElasticBridge\Commands;

use Illuminate\Console\Command;

class ElasticBridgeCommand extends Command
{
    public $signature = 'elastic-bridge';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
