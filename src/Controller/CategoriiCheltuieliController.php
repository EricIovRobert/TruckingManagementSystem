<?php

namespace App\Controller;

use App\Entity\CategoriiCheltuieli;
use App\Entity\SubcategoriiCheltuieli;
use App\Form\CategoriiCheltuieliType;
use App\Form\SubcategoriiCheltuieliType;
use App\Repository\CategoriiCheltuieliRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categorii/cheltuieli')]
class CategoriiCheltuieliController extends AbstractController
{
    #[Route('/', name: 'app_categorii_cheltuieli_index', methods: ['GET'])]
    public function index(CategoriiCheltuieliRepository $categoriiCheltuieliRepository): Response
    {
        $categorii = $categoriiCheltuieliRepository->findAll();
        return $this->render('categorii_cheltuieli/index.html.twig', [
            'categorii' => $categorii,
        ]);
    }

    #[Route('/new', name: 'app_categorii_cheltuieli_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new CategoriiCheltuieli();
        $form = $this->createForm(CategoriiCheltuieliType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();
            return $this->redirectToRoute('app_categorii_cheltuieli_index');
        }

        return $this->render('categorii_cheltuieli/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_categorii_cheltuieli_show', methods: ['GET'])]
    public function show(CategoriiCheltuieli $categorie): Response
    {
        if (strtolower($categorie->getNume()) === 'consumabile') {
            return $this->redirectToRoute('app_consumabile_index');
        }

        return $this->render('categorii_cheltuieli/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorii_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategoriiCheltuieli $categorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoriiCheltuieliType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_categorii_cheltuieli_index');
        }

        return $this->render('categorii_cheltuieli/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_categorii_cheltuieli_delete', methods: ['POST'])]
public function delete(Request $request, CategoriiCheltuieli $categorie, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->request->get('_token'))) {
        // Verificăm dacă categoria are cheltuieli asociate
        if (!$categorie->getCheltuielis()->isEmpty()) {
            $this->addFlash('danger', 'Categoria "' . $categorie->getNume() . '" nu poate fi ștearsă deoarece este utilizată în cheltuieli existente.');
        } else {
            // Dacă nu are cheltuieli, permitem ștergerea
            $hasSubcategorii = !$categorie->getSubcategoriiCheltuielis()->isEmpty();
            $hasConsumabile = !$categorie->getConsumabiles()->isEmpty();

            if ($hasSubcategorii || $hasConsumabile) {
                $message = 'Categoria "' . $categorie->getNume() . '" a fost ștearsă împreună cu ';
                if ($hasSubcategorii && $hasConsumabile) {
                    $message .= 'toate subcategoriile și consumabilele asociate.';
                } elseif ($hasSubcategorii) {
                    $message .= 'toate subcategoriile asociate.';
                } else {
                    $message .= 'toate consumabilele asociate.';
                }
                $this->addFlash('warning', $message);
            } else {
                $this->addFlash('success', 'Categoria "' . $categorie->getNume() . '" a fost ștearsă.');
            }

            $entityManager->remove($categorie);
            $entityManager->flush();
        }
    }
    return $this->redirectToRoute('app_categorii_cheltuieli_index');
}

    #[Route('/{categorieId}/subcategorie/new', name: 'app_subcategorii_cheltuieli_new', methods: ['GET', 'POST'])]
    public function newSubcategorie(Request $request, int $categorieId, EntityManagerInterface $entityManager, CategoriiCheltuieliRepository $categoriiCheltuieliRepository): Response
    {
        $categorie = $categoriiCheltuieliRepository->find($categorieId);
        if (!$categorie) {
            throw $this->createNotFoundException('Categoria nu a fost găsită.');
        }

        $subcategorie = new SubcategoriiCheltuieli();
        $subcategorie->setCategorie($categorie);
        $form = $this->createForm(SubcategoriiCheltuieliType::class, $subcategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subcategorie);
            $entityManager->flush();
            return $this->redirectToRoute('app_categorii_cheltuieli_show', ['id' => $categorieId]);
        }

        return $this->render('categorii_cheltuieli/subcategorie_new.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{categorieId}/subcategorie/{id}/edit', name: 'app_subcategorii_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function editSubcategorie(Request $request, int $categorieId, SubcategoriiCheltuieli $subcategorie, EntityManagerInterface $entityManager, CategoriiCheltuieliRepository $categoriiCheltuieliRepository): Response
    {
        $categorie = $categoriiCheltuieliRepository->find($categorieId);
        if (!$categorie || $subcategorie->getCategorie() !== $categorie) {
            throw $this->createNotFoundException('Categoria sau subcategoria nu a fost găsită.');
        }

        $form = $this->createForm(SubcategoriiCheltuieliType::class, $subcategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_categorii_cheltuieli_show', ['id' => $categorieId]);
        }

        return $this->render('categorii_cheltuieli/subcategorie_edit.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }

   #[Route('/{categorieId}/subcategorie/{id}/delete', name: 'app_subcategorii_cheltuieli_delete', methods: ['POST'])]
public function deleteSubcategorie(Request $request, int $categorieId, SubcategoriiCheltuieli $subcategorie, EntityManagerInterface $entityManager, CategoriiCheltuieliRepository $categoriiCheltuieliRepository): Response
{
    $categorie = $categoriiCheltuieliRepository->find($categorieId);
    if (!$categorie || $subcategorie->getCategorie() !== $categorie) {
        throw $this->createNotFoundException('Categoria sau subcategoria nu a fost găsită.');
    }

    if ($this->isCsrfTokenValid('delete'.$subcategorie->getId(), $request->request->get('_token'))) {
        // Verificăm dacă subcategoria are cheltuieli asociate
        if (!$subcategorie->getCheltuielis()->isEmpty()) {
            $this->addFlash('danger', 'Subcategoria "' . $subcategorie->getNume() . '" nu poate fi ștearsă deoarece este utilizată în cheltuieli existente.');
        } else {
            // Dacă nu are cheltuieli, permitem ștergerea
            $entityManager->remove($subcategorie);
            $entityManager->flush();
            $this->addFlash('success', 'Subcategoria "' . $subcategorie->getNume() . '" a fost ștearsă.');
        }
    }
    return $this->redirectToRoute('app_categorii_cheltuieli_show', ['id' => $categorieId]);
}
}