<?php


namespace App\Tests\Feature;


use App\Services\ImageService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ApiImageTest extends WebTestCase
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $kernel =  $this->client->getContainer()->get('kernel');
        $application = new Application($kernel);

        $application->setAutoExit(false);


        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
        ]);
        $output = new NullOutput();
        $application->run($input, $output);

    }

    public function test_it_can_store_files()
    {
        $client = $this->client;
        copy($client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg');
        copy($client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg');


        $client->request('POST', $client->getContainer()->get('router')->generate(
            'api.store-files'
        ), [

        ],[
            'image_form' => [
                'files' => [
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg',
                        'dummy2.jpeg',
                        'image/jpeg',
                    ),
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg',
                        'dummy3.jpeg',
                        'image/jpeg',
                    )
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $content = json_decode($client->getResponse()->getContent());
        $this->assertCount(2, $content->items);
        $this->assertCount(0, $content->errors);
    }

    public function test_it_should_not_store_files_with_extra_size()
    {
        $oldEnvValue = $_ENV['MAX_IMAGE_SIZE'];
        $_ENV['MAX_IMAGE_SIZE'] = 10;
        copy($this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg');

        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.store-files'
        ), [

        ],[
            'image_form' => [
                'files' => [
                    new UploadedFile(
                        $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg',
                        'dummy2.jpeg',
                        'image/jpeg',
                    ),
                ]
            ]
        ]);

        $_ENV['MAX_IMAGE_SIZE'] = $oldEnvValue;
        $this->assertEquals(422, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_can_store_base64_strings_as_files()
    {
        $base64 = file_get_contents($this->client->getContainer()->get('kernel')->getProjectDir().'/tests/base64_example.txt');
        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.store-from-base64'
        ), [
            'base64_form' => [
                'files' => [
                    $base64
                ]
            ]
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, $content->items);
        $this->assertCount(0, $content->errors);
    }

    public function test_it_should_not_store_incorrect_base64_strings()
    {
        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.store-from-base64'
        ), [
            'base64_form' => [
                'files' => [
                    'wrong base 64 string'
                ]
            ]
        ]);

        $content = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(0, $content->items);
        $this->assertCount(1, $content->errors);
    }

    public function test_it_can_create_resize_for_specific_image()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]->id;
        $imageName = $savedFiles[0]->name;

        $width = 120;
        $height = 120;

        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.create-resize'
        ), [
            'image_id' => $imageId,
            'width' => $width,
            'height' => $height,
        ]);

        $resizeName = self::$container->get(ImageService::class)->getResizeImageName($imageName, $width, $height);

        $this->assertFileExists($this->client->getContainer()->get('kernel')->getProjectDir().'/public/upload/resize/'.$resizeName);
    }

    public function test_it_can_delete_all_resizes()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]->id;
        $imageName = $savedFiles[0]->name;

        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.create-resize'
        ), [
            'image_id' => $imageId,
            'width' => 120,
            'height' => 120,
        ]);

        $this->client->request('DELETE', $this->client->getContainer()->get('router')->generate(
            'api.delete-all-resizes'
        ), [
            'image_id' => $imageId,
        ]);


        $imageService = self::$container->get(ImageService::class);
        $resizeName1 = $imageService->getResizeImageName($imageName, 100, 100);
        $resizeName2 = $imageService->getResizeImageName($imageName, 120, 120);

        $this->assertFileNotExists($this->client->getContainer()->get('kernel')->getProjectDir().'/public/upload/resize/'.$resizeName1);
        $this->assertFileNotExists($this->client->getContainer()->get('kernel')->getProjectDir().'/public/upload/resize/'.$resizeName2);
    }

    public function test_it_delete_default_resize()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]->id;
        $imageName = $savedFiles[0]->name;

        $this->client->request('DELETE', $this->client->getContainer()->get('router')->generate(
            'api.delete-resize'
        ), [
            'image_id' => $imageId,
            'width' => 100,
            'height' => 100,
        ]);

        $imageService = self::$container->get(ImageService::class);
        $resizeName = $imageService->getResizeImageName($imageName, 100, 100);

        $this->assertFileNotExists($this->client->getContainer()->get('kernel')->getProjectDir().'/public/upload/resize/'.$resizeName);
    }

    public function test_it_can_get_list_of_resizes()
    {
        $savedFiles = $this->saveFiles();
        $imageId = $savedFiles[0]->id;
        $imageName = $savedFiles[0]->name;

        $imageService = self::$container->get(ImageService::class);
        $resizeName = $imageService->getResizeImageName($imageName, 100, 100);

        $this->client->request('GET', $this->client->getContainer()->get('router')->generate(
            'api.get-image-resizes'
        ), [
            'image_id' => $imageId,
        ]);

        $content = json_decode($this->client->getResponse()->getContent());


        $this->assertEquals($_ENV['APP_HOST'].'upload/resize/'.$resizeName, $content[0]->url);
    }

    private function saveFiles()
    {
        copy($this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg');
        copy($this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg');


        $this->client->request('POST', $this->client->getContainer()->get('router')->generate(
            'api.store-files'
        ), [

        ],[
            'image_form' => [
                'files' => [
                    new UploadedFile(
                        $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg',
                        'dummy2.jpeg',
                        'image/jpeg',
                    ),
                    new UploadedFile(
                        $this->client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg',
                        'dummy3.jpeg',
                        'image/jpeg',
                    )
                ]
            ]
        ]);

        $content = json_decode($this->client->getResponse()->getContent());

        return $content->items;
    }
}