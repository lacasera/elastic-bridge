<?php

namespace Lacasera\ElasticBridge\Tests\Commands;

use Lacasera\ElasticBridge\Commands\ElasticBridgeCommand;
use Lacasera\ElasticBridge\Tests\TestCase;

class MakeBridgeCommandTest extends TestCase
{

    /**
     * @return void
     * @test
     */
    public function it_should_create_file_in_default_name_namespace(): void
    {
        $this->artisan(ElasticBridgeCommand::class, [
            'name' => 'hotel rooms'
        ]);

        $this->assertFileExists(app_path("Bridges". DIRECTORY_SEPARATOR ."HotelRoom.php"));

        $expected = file_get_contents((app_path("Bridges". DIRECTORY_SEPARATOR ."HotelRoom.php")));

        $this->assertEquals($expected, $this->getFileContent("App\\Bridges", 'HotelRoom'));
    }

    /**
     * @return void
     * @test
     */
    public function it_should_create_create_file_in_a_given_namespace(): void
    {
        $namespace = "App\\Elastic\\Models";

        config()->set('elasticbridge.namespace', $namespace);

        $this->artisan(ElasticBridgeCommand::class, [
            'name' => 'hotel rooms'
        ]);

        $path = app_path("Elastic". DIRECTORY_SEPARATOR . "Models". DIRECTORY_SEPARATOR ."HotelRoom.php");
        $this->assertFileExists($path);
        $expected = file_get_contents($path);

        $this->assertEquals($expected, $this->getFileContent($namespace, 'HotelRoom'));
    }

    /**
     * @return void
     * @test
     */
    public function it_should_throw_exception_when_file_already_exists(): void
    {
        $path = app_path("Bridges" . DIRECTORY_SEPARATOR. "Log.php");

        file_put_contents($path, '');

        $this->artisan(ElasticBridgeCommand::class, [
            'name' => 'log'
        ])
            ->expectsOutput('bridge with the name Log already exists')
            ->assertFailed();
    }

    /**
     * @param $namespace
     * @param $classname
     * @return string
     */
    protected function getFileContent($namespace, $classname): string
    {
        return sprintf("<?php

namespace %s;

use Lacasera\ElasticBridge\ElasticBridge;

class %s extends ElasticBridge
{

}
", $namespace, $classname);
    }
}
