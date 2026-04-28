<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\CsvUploadFormType;
use App\Repository\GameRepository;
use App\Service\ImportParserService;
use App\Trait\DebuggerTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{
    use DebuggerTrait;

    public function __construct(
        private readonly ImportParserService $importParserService,
        private readonly GameRepository      $gameRepository,
    )
    {
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

            //TODO move this logic into the service (parse, process, update database)
            $importGames = $this->importParserService->parseCsvContents($importContent);
            $updatedGames = $this->importParserService->getUpdatedGames($importGames, $this->gameRepository->findAll());

            try {
                $updatedCount = $this->gameRepository->updateGames($updatedGames['updated']);
                $insertedCount = $this->gameRepository->insertGames($updatedGames['new']);
                $deletedCount = $this->gameRepository->deleteGames($updatedGames['deleted']);

                $result = new UploadResult(
                    true,
                    sprintf(
                        '%d games updated, %d games added, %d games deleted',
                        $updatedCount,
                        $insertedCount,
                        $deletedCount,
                    )
                );

            } catch (Exception $e) {
                $result = new UploadResult(false, $e->getMessage());
            }

            if ($result->success) {
                $this->addFlash('success', sprintf('Upload was successful: %s.', $result->message));
            } else {
                $this->addFlash('error', sprintf('Upload failed: %s', $result->message));
            }
        }

        return $this->redirectToRoute('index');
    }
}