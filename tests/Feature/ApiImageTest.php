<?php


namespace App\Tests\Feature;


use App\Services\ImageService;
use AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiImageTest extends WebTestCase
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function setUp()
    {
        parent::setUp();
    }

    public function test_it_can_store_files()
    {
        $client = static::createClient();
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
                        100
                    ),
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg',
                        'dummy3.jpeg',
                        'image/jpeg',
                        100
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
        $_ENV['MAX_IMAGE_SIZE'] = 10;
        $client = static::createClient();
        copy($client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg', $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy2.jpeg');

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
                        10*1024*1000
                    ),
                ]
            ]
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function test_it_can_store_base64_strings_as_files()
    {
        $client = static::createClient();

        $base64 = file_get_contents($client->getContainer()->get('kernel')->getProjectDir().'/tests/base64_example.txt');
        $client->request('POST', $client->getContainer()->get('router')->generate(
            'api.store-from-base64'
        ), [
            'base64_form' => [
                'files' => [
                    $base64
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent());

        $this->assertCount(1, $content->items);
        $this->assertCount(0, $content->errors);
    }

    public function test_it_should_not_store_incorrect_base64_strings()
    {
        $client = static::createClient();
        $client->request('POST', $client->getContainer()->get('router')->generate(
            'api.store-from-base64'
        ), [
            'base64_form' => [
                'files' => [
                    'wrong base 64 string'
                ]
            ]
        ]);

        $content = json_decode($client->getResponse()->getContent());

        $this->assertCount(0, $content->items);
        $this->assertCount(1, $content->errors);
    }

    private function saveFiles()
    {
        $client = static::createClient();
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
                        100
                    ),
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy3.jpeg',
                        'dummy3.jpeg',
                        'image/jpeg',
                        100
                    )
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $content = json_decode($client->getResponse()->getContent());

        return $content->items;
    }
}