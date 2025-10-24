<?php

declare(strict_types=1);

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

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! $name) {
            $name = text('What is the name of the bridge? (Elastic Index) ', 'Film');
        }

        $namespace = config('elasticbridge.namespace') ?? 'App\\Bridges';
        $filename = $this->parseFileName($name);
        $folder = $this->getDirectoryFromNamespace($namespace);

        $file = Str::of($folder)
            ->replace('\\', DIRECTORY_SEPARATOR)
            ->append(DIRECTORY_SEPARATOR.$filename.'.php')
            ->value();

        $stubPath = __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'bridge.stub';
        $stub = file_get_contents($stubPath) ?: "<?php\n\nnamespace %s;\n\nuse Lacasera\\ElasticBridge\\ElasticBridge;\n\nclass %s extends ElasticBridge\n{\n\n}\n";

        $content = sprintf($stub, $namespace, $filename);

        if (! str_contains($content, 'declare(strict_types=1);')) {
            $content = str_replace("<?php\n", "<?php\ndeclare(strict_types=1);\n\n", $content);
        }

        if (! is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        file_put_contents($file, $content);

        return self::SUCCESS;
    }

    protected function getDirectoryFromNamespace(string $namespace): string
    {
        $directoryPath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return app_path().DIRECTORY_SEPARATOR.substr($directoryPath, strpos($directoryPath, DIRECTORY_SEPARATOR) + 1);
    }

    protected function parseFileName($name): string
    {
        return str_replace(' ', '', Str::headline(Str::singular($name)));
    }
}
