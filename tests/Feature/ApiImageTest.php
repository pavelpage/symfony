<?php


namespace App\Tests\Feature;


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
}