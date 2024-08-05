<?php

namespace Lacasera\ElasticBridge\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class ElasticBridgeCommand extends Command
{
    /**
     * @var string
     */
    public $signature = 'make:bridge {name? : The elastic index you want to interact with }';

    /**
     * @var string
     */
    public $description = 'Create a new bridge  class to represent and elastic index';

    /**
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        if (! $name) {
            $name = text('What is the name of the bridge? (Elastic Index) ', 'Films');
        }

        $namespace = config('elasticbridge.namespace') ?? 'App\\Bridges';

        $path = "$namespace\\$name";

        $file = Str::of($namespace)
            ->replace('\\', DIRECTORY_SEPARATOR)
            ->append(DIRECTORY_SEPARATOR.$name.'.php')
            ->value();

        $folder = $this->getDirectoryFromNamespace($namespace);

        if (file_exists($file)) {
            $this->error("$path already exists");
        }

        $content = sprintf(
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'bridge.stub'),
            $namespace,
            $name
        );

        if (! is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        file_put_contents(str_replace('App'.DIRECTORY_SEPARATOR, '', app_path($file)), $content);

        return self::SUCCESS;
    }

    /**
     * @param string $namespace
     * @return string
     */
    public function getDirectoryFromNamespace(string $namespace): string
    {
        return app_path().DIRECTORY_SEPARATOR.str_replace('App'.DIRECTORY_SEPARATOR, '', $namespace);
    }
}
