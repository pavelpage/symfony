<?php


namespace App\Controller;

use App\Entity\Image;
use App\Form\Base64FormType;
use App\Form\ExternalUrlsType;
use App\Form\ImageFormType;
use App\Services\ImageService;
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
            $data = $request->get('external_urls')['urls'];
            $savedFiles = $this->imageService->uploadFromUrls($data);
        }
        else {
            return $this->json([
                'items' => $form->getData(),
                'errors' => $form->getErrors(),
            ]);
        }

        return $this->json([
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ]);
    }

    public function saveFileFromBase64(Request $request)
    {
        $image = new Image();
        $form = $this->createForm(Base64FormType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->get('base64_form')['files'];
            $savedFiles = $this->imageService->saveFilesFromBase64($data);
        }
        else {
            return $this->json([
                'items' => $form->getData(),
                'errors' => $form->getErrors(),
            ]);
        }

        return $this->json([
            'items' => $savedFiles->getSavedFiles(),
            'errors' => $savedFiles->getErrors(),
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createResize(Request $request)
    {
        $resizeUrl = $this->imageService->createResize(
            $request->get('image_id'), $request->get('width', 100), $request->get('height', 100)
        );

        return $this->json([
            'url' => $resizeUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getImageResizes(Request $request)
    {
        return $this->json(
            $this->imageService->getImageResizes($request->get('image_id'))
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteImageResize(Request $request)
    {
        $successDelete = $this->imageService->deleteImageResize(
            $request->get('image_id'), $request->get('width'), $request->get('height')
        );

        return $this->json([
            'deleted' => $successDelete,
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAllImageResizes(Request $request)
    {
        $successDelete = $this->imageService->deleteAllImageResizes(
            $request->get('image_id')
        );

        return $this->json([
            'deleted' => $successDelete,
        ]);
    }
}