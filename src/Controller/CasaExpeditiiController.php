<?php

namespace App\Controller;

use App\Entity\CasaExpeditii;
use App\Form\CasaExpeditiiType;
use App\Repository\CasaExpeditiiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/casa-expeditii')]
class CasaExpeditiiController extends AbstractController
{
    private function getDocumentDirectory(): string
    {
        return $this->getParameter('kernel.project_dir') . '/public/documents/';
    }

    #[Route('/', name: 'app_casa_expeditii_index', methods: ['GET'])]
    public function index(
        Request $request,
        CasaExpeditiiRepository $casaExpeditiiRepository,
        PaginatorInterface $paginator // Injectăm PaginatorInterface
    ): Response {
        $query = $casaExpeditiiRepository->createQueryBuilder('ce')
            ->orderBy('ce.id', 'DESC')
            ->getQuery();

        // Paginare cu 10 elemente pe pagină
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Numărul paginii curente, implicit 1
            15 // Limita de 10 elemente pe pagină
        );

        return $this->render('casa_expeditii/index.html.twig', [
            'expeditii' => $pagination, // Transmitem obiectul de paginare către șablon
        ]);
    }

    #[Route('/new', name: 'app_casa_expeditii_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $casaExpeditii = new CasaExpeditii();
        $form = $this->createForm(CasaExpeditiiType::class, $casaExpeditii);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($casaExpeditii);
            $entityManager->flush();

            return $this->redirectToRoute('app_casa_expeditii_index');
        }

        return $this->render('casa_expeditii/new.html.twig', [
            'casa_expeditii' => $casaExpeditii,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_casa_expeditii_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CasaExpeditiiType::class, $casaExpeditii);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_casa_expeditii_index');
        }

        return $this->render('casa_expeditii/edit.html.twig', [
            'casa_expeditii' => $casaExpeditii,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_casa_expeditii_delete', methods: ['POST'])]
    public function delete(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$casaExpeditii->getId(), $request->request->get('_token'))) {
            $entityManager->remove($casaExpeditii);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_casa_expeditii_index');
    }

    #[Route('/{id}/document/edit', name: 'app_casa_expeditii_edit_document', methods: ['GET', 'POST'])]
    public function editDocument(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        $filesystem = new Filesystem();
        $documentPath = $casaExpeditii->getContractPath();

        if ($documentPath && $filesystem->exists($this->getDocumentDirectory() . $documentPath)) {
            $contractHtml = file_get_contents($this->getDocumentDirectory() . $documentPath);
        } else {
            $contractHtml = $this->renderView('casa_expeditii/base_contract.html.twig', [
                'casaExpeditii' => $casaExpeditii
            ]);
        }

        if ($request->isMethod('POST')) {
            $contractHtml = $request->request->get('contractHtml');
            $fileName = $casaExpeditii->getId() . '_' . uniqid() . '.html';
            $filesystem->dumpFile($this->getDocumentDirectory() . $fileName, $contractHtml);
            $casaExpeditii->setContractPath($fileName);
            $entityManager->flush();

            return $this->redirectToRoute('app_casa_expeditii_index');
        }

        return $this->render('casa_expeditii/edit_document.html.twig', [
            'casaExpeditii' => $casaExpeditii,
            'contractHtml' => $contractHtml,
        ]);
    }

    #[Route('/{id}/document/view', name: 'app_casa_expeditii_view_document', methods: ['GET'])]
    public function viewDocument(CasaExpeditii $casaExpeditii): Response
    {
        $filesystem = new Filesystem();
        $documentPath = $casaExpeditii->getContractPath();
        $fullPath = $this->getDocumentDirectory() . $documentPath;
    
        if ($documentPath && $filesystem->exists($fullPath)) {
            $contractHtml = file_get_contents($fullPath);
    
            // Configurează Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultPaperSize', 'A4');
    
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($contractHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
    
            return new Response(
                $dompdf->output(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $casaExpeditii->getNrComandaTransportator() . '_' . $casaExpeditii->getNumeTransportator() . '.pdf"'
                ]
            );
        } else {
            return new Response('<p>Documentul nu este disponibil.</p>', Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}/document/delete', name: 'app_casa_expeditii_delete_document', methods: ['POST'])]
    public function deleteDocument(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_document'.$casaExpeditii->getId(), $request->request->get('_token'))) {
            $filesystem = new Filesystem();
            $documentPath = $casaExpeditii->getContractPath();

            if ($documentPath && $filesystem->exists($this->getDocumentDirectory() . $documentPath)) {
                $filesystem->remove($this->getDocumentDirectory() . $documentPath);
            }

            $casaExpeditii->setContractPath(null);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_casa_expeditii_index');
    }

    #[Route('/{id}/document/download/pdf', name: 'app_casa_expeditii_download_pdf', methods: ['GET'])]
    public function downloadPdf(CasaExpeditii $casaExpeditii): Response
    {
        $filesystem = new Filesystem();
        $documentPath = $casaExpeditii->getContractPath();
        $contractHtml = $documentPath && $filesystem->exists($this->getDocumentDirectory() . $documentPath)
            ? file_get_contents($this->getDocumentDirectory() . $documentPath)
            : $this->renderView('casa_expeditii/base_contract.html.twig', [
                'casaExpeditii' => $casaExpeditii
            ]);
    
        // Configurează opțiunile Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); 
    
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($contractHtml, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $casaExpeditii->getNrComandaTransportator() . '_' . $casaExpeditii->getNumeTransportator() . '.pdf"',            ]
        );
    }

}