<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\CsvUploadFormType;
use App\Repository\GameRepository;
use App\Service\ImportParserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{
    public function __construct(
        private readonly ImportParserService $importParserService,
        private readonly GameRepository      $gameRepository,
    ) {
    }

    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): RedirectResponse
    {
        $form = $this->createForm(CsvUploadFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csv */
            $csv = $form->get('upload')->getData();
            $importContent = $csv->getContent();

            $importGames = $this->importParserService->parseCsvContents($importContent);
            $updatedGames = $this->importParserService->getUpdatedGames($importGames, $this->gameRepository->findAll());
            echo '<pre>';
            print_r($updatedGames);
            die();
            $result = $this->gameRepository->upsertAll($updatedGames);

            if ($result->isSuccess()) {
                $this->addFlash('success', sprintf('Upload was successful, %d games imported.', $result->count));
            } else {
                $this->addFlash('error', sprintf('Upload failed: %s', $result->errorMsg));
            }
        }

        return $this->redirectToRoute('index');
    }
}