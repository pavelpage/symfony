<?php


namespace App\Controller;

use App\Entity\Image;
use App\Form\ExternalUrlsType;
use App\Form\ImageFormType;
use App\Services\ImageService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiImageController extends AbstractController
{
//
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * ApiImageController constructor.
     * @param ImageService $imageService
     */
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function testUpload()
    {
        $image = new Image();
        $form = $this->createForm(ImageFormType::class, $image);
        return $this->render('new.html.twig', [
                'form' => $form->createView(),
             ]);
    }

    public function storeFile(Request $request)
    {
        $image = new Image();
        $form = $this->createForm(ImageFormType::class, $image);
        $form->handleRequest($request);

//        var_dump($form->isSubmitted(), $form->isValid(), $form->getData());exit;
        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get('image_form')['files'];
            $savedFiles = $this->imageService->saveFilesAndRetrieveItems($files);
        }
        else {
            return $this->json([
                'items' => [],
                'errors' => $form->getErrors(),
            ], $status = 422);
        }

        return $this->json([
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ]);
    }

    public function saveFileFromUrl(Request $request)
    {
        $image = new Image();
        $form = $this->createForm(ExternalUrlsType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->get('image_form')['urls'];
            $savedFiles = $this->imageService->uploadFromUrls($data);
        }
        else {
            return $this->json([
                'items' => $form->getData(),
                'errors' => $form->getErrors(),
            ]);
        }

        return [
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ];
    }

    public function saveFileFromBase64()
    {

    }

    public function createResize()
    {

    }

    public function getImageResizes()
    {

    }

    public function deleteImageResize()
    {

    }

    public function deleteAllImageResizes()
    {

    }
}