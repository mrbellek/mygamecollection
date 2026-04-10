<?php

namespace App\Controller;

use App\Form\CsvUploadFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): RedirectResponse
    {
        $form = $this->createForm(CsvUploadFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csv */
            $csv = $form->get('upload')->getData();
            die(var_dump($csv->getContent()));
        }

        return $this->redirectToRoute('index');
    }
}