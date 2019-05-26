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

    /**
     * ApiImageTest constructor.
     * @param UrlGeneratorInterface $router
     */
//    public function __construct(UrlGeneratorInterface $router)
//    {
//        $this->router = $router;
//    }

    public function setUp()
    {
        parent::setUp();
    }

    public function test_it_can_store_files()
    {
        $client = static::createClient();

        $client->request('POST', $client->getContainer()->get('router')->generate(
            'api.store-files'
        ), [

        ],[
            'image_form' => [
                'files' => [
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg',
                        'dummy.jpeg',
                        'image/jpeg',
                        null
                    ),
                    new UploadedFile(
                        $client->getContainer()->get('kernel')->getProjectDir().'/tests/dummy.jpeg',
                        'dummy.jpeg',
                        'image/jpeg',
                        null
                    )
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $content = json_decode($client->getResponse()->getContent());
        // TODO: FIX CSRF_TOKEN USAGE
        $this->assertCount(2, $content->items);
        $this->assertCount(0, $content->errors);
    }
}