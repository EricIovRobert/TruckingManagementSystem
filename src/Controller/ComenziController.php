<?php

namespace App\Controller;

use App\Entity\Comenzi;
use App\Entity\Cheltuieli;
use App\Form\CheltuieliType;
use App\Form\ComenziType;
use App\Repository\ComenziRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\SubcategoriiCheltuieli;
use App\Entity\Consumabile;
use App\Entity\CategoriiCheltuieli;

#[Route('/comenzi')]
class ComenziController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_index')]
    public function index(ComenziRepository $comenziRepository): Response
    {
        $comenzi = $comenziRepository->findAll();

        return $this->render('comenzi/index.html.twig', [
            'comenzi' => $comenzi,
        ]);
    }

    #[Route('/new', name: 'app_comenzi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $comanda = new Comenzi();
        $form = $this->createForm(ComenziType::class, $comanda);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comanda);
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_index');
        }

        return $this->render('comenzi/new.html.twig', [
            'form' => $form->createView(),
            'parc_auto_list' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comenzi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $oldNumarKm = $comanda->getNumarKm();

        $form = $this->createForm(ComenziType::class, $comanda);
        $form->get('parcAutoNr')->setData($comanda->getParcAuto() ? $comanda->getParcAuto()->getNrAuto() : '');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Salvăm comanda
            $entityManager->flush();

            // Dacă se schimbă număr km, recalculăm cheltuieli de tip consumabil
            $newNumarKm = $comanda->getNumarKm();
            if ($oldNumarKm !== $newNumarKm) {
                foreach ($comanda->getCheltuielis() as $cheltuiala) {
                    if ($cheltuiala->getConsumabil()) {
                        $consumabil = $cheltuiala->getConsumabil();
                        $pretMaxim = $consumabil->getPretMaxim();
                        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

                        if ($newNumarKm !== null && $kmUtilizareMax !== null && $kmUtilizareMax > 0) {
                            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $newNumarKm;
                            $cheltuiala->setSuma($sumaProportionala);
                        } else {
                            $cheltuiala->setSuma($pretMaxim);
                        }
                    }
                }
                // Recalculăm profitul după schimbare
                $comanda->calculateAndSetProfit();
                $entityManager->flush();

                // Reîmprospătăm comanda
                $entityManager->refresh($comanda);
            }

            return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
        }

        return $this->render('comenzi/edit.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
            'parc_auto_list' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_comenzi_delete', methods: ['POST'])]
    public function delete(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comanda->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comanda);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comenzi_index');
    }

    #[Route('/{id}/show', name: 'app_comenzi_show', methods: ['GET'])]
    public function show(Comenzi $comanda, ParcAutoRepository $parcAutoRepository): Response
    {
        return $this->render('comenzi/show.html.twig', [
            'comanda' => $comanda,
            'parc_autos' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}/update-rezolvat', name: 'app_comenzi_update_rezolvat', methods: ['POST'])]
    public function updateRezolvat(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $rezolvat = filter_var($request->request->get('rezolvat', false), FILTER_VALIDATE_BOOLEAN);
            $comanda->setRezolvat($rezolvat);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/update-observatii', name: 'app_comenzi_update_observatii', methods: ['POST'])]
    public function updateObservatii(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $observatii = $request->request->get('observatii');
            $comanda->setObservatii($observatii);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'observatii' => $observatii]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/update-nr-accident-auto', name: 'app_comenzi_update_nr_accident_auto', methods: ['POST'])]
    public function updateNrAccidentAuto(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $nrAccidentAuto = $request->request->get('nrAccidentAuto');
            $comanda->setNrAccidentAuto($nrAccidentAuto);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'nrAccidentAuto' => $nrAccidentAuto ?: 'N/A']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Adăugarea unei cheltuieli noi
     */
    #[Route('/{id}/cheltuieli/new', name: 'app_comenzi_cheltuieli_new', methods: ['GET', 'POST'])]
    public function newCheltuiala(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): Response
    {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setComanda($comanda);
        $cheltuiala->setDataCheltuiala(new \DateTime());

        $form = $this->createForm(CheltuieliType::class, $cheltuiala);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categorie = $cheltuiala->getCategorie();
            $subcategorieId = $form->get('subcategorie')->getData();

            // Dacă e categorie Consumabile => cautăm "consumabil"
            if ($categorie && $categorie->getNume() === 'Consumabile') {
                if ($subcategorieId) {
                    $consumabil = $entityManager->getRepository(Consumabile::class)->find($subcategorieId);
                    if ($consumabil) {
                        $cheltuiala->setConsumabil($consumabil);
                        $cheltuiala->setSubcategorie(null);

                        // Calculăm suma proporțional
                        $numarKm = $comanda->getNumarKm();
                        $pretMaxim = $consumabil->getPretMaxim();
                        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

                        if ($numarKm !== null && $kmUtilizareMax !== null && $kmUtilizareMax > 0) {
                            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
                            $cheltuiala->setSuma($sumaProportionala);
                        } else {
                            $cheltuiala->setSuma($pretMaxim);
                        }
                    }
                }
            } else {
                // subcategorie normală
                if ($subcategorieId) {
                    $subcategorie = $entityManager->getRepository(SubcategoriiCheltuieli::class)->find($subcategorieId);
                    if ($subcategorie) {
                        $cheltuiala->setSubcategorie($subcategorie);
                        $cheltuiala->setConsumabil(null);
                    }
                }
            }

            $entityManager->persist($cheltuiala);
            $entityManager->flush();

            // Recalculăm profitul
            $comanda->calculateAndSetProfit();
            $entityManager->flush();

            return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
        }

        return $this->render('comenzi/cheltuieli_new.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
        ]);
    }

    /**
     * Editarea unei cheltuieli existente
     */
    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/edit', name: 'app_comenzi_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function editCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(Comenzi::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComanda() !== $comanda) {
            throw $this->createNotFoundException('Cheltuiala nu a fost găsită pentru această comandă.');
        }

        $form = $this->createForm(CheltuieliType::class, $cheltuiala);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Reasociem consumabil/subcategorie
            $cheltuiala->setConsumabil(null);
            $cheltuiala->setSubcategorie(null);

            $categorie = $cheltuiala->getCategorie();
            $subcategorieId = $form->get('subcategorie')->getData();

            if ($categorie && $categorie->getNume() === 'Consumabile') {
                if ($subcategorieId) {
                    $consumabil = $entityManager->getRepository(Consumabile::class)->find($subcategorieId);
                    if ($consumabil) {
                        $cheltuiala->setConsumabil($consumabil);
                        // Recalcul (dacă vrei) – aici e pentru un consumabil
                        $numarKm = $comanda->getNumarKm();
                        $pretMaxim = $consumabil->getPretMaxim();
                        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

                        if ($numarKm !== null && $kmUtilizareMax !== null && $kmUtilizareMax > 0) {
                            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
                            $cheltuiala->setSuma($sumaProportionala);
                        } else {
                            $cheltuiala->setSuma($pretMaxim);
                        }
                    }
                }
            } else {
                if ($subcategorieId) {
                    $subcategorie = $entityManager->getRepository(SubcategoriiCheltuieli::class)->find($subcategorieId);
                    if ($subcategorie) {
                        $cheltuiala->setSubcategorie($subcategorie);
                    }
                }
            }

            $entityManager->flush();
            // Recalculăm profitul
            $comanda->calculateAndSetProfit();
            $entityManager->flush();

            return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
        }

        return $this->render('comenzi/cheltuieli_edit.html.twig', [
            'comanda' => $comanda,
            'cheltuiala' => $cheltuiala,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ștergerea unei cheltuieli
     */
    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/delete', name: 'app_comenzi_cheltuieli_delete', methods: ['POST'])]
    public function deleteCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(Comenzi::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComanda() !== $comanda) {
            throw $this->createNotFoundException('Cheltuiala nu a fost găsită pentru această comandă.');
        }

        if ($this->isCsrfTokenValid('delete'.$cheltuiala->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cheltuiala);
            $entityManager->flush();

            // Recalculăm profitul
            $comanda->calculateAndSetProfit();
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
    }

    /**
     * Returnează subcategoriile (inclusiv pret_per_l) prin AJAX
     */
    #[Route('/get-subcategories', name: 'app_get_subcategories', methods: ['GET'])]
    public function getSubcategories(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $categorieId = $request->query->get('categorie');
        if (!$categorieId) {
            return new JsonResponse([]);
        }

        $categorie = $entityManager->getRepository(CategoriiCheltuieli::class)->find($categorieId);
        if (!$categorie) {
            return new JsonResponse([]);
        }

        // Dacă e "Consumabile", listăm obiectele din tabelul Consumabile
        if ($categorie->getNume() === 'Consumabile') {
            $consumabile = $entityManager->getRepository(Consumabile::class)
                ->findBy(['categorie' => $categorieId]);

            $data = array_map(function ($consumabil) {
                return [
                    'id' => $consumabil->getId(),
                    'nume' => $consumabil->getNume(),
                    'pret_standard' => $consumabil->getPretMaxim(),
                    'km_utilizare_max' => $consumabil->getKmUtilizareMax(),
                    // consumabile nu au pret_per_l => setăm null
                    'pret_per_l' => null,
                ];
            }, $consumabile);
        } else {
            // Altfel, subcategorii clasice
            $subcategorii = $entityManager->getRepository(SubcategoriiCheltuieli::class)
                ->findBy(['categorie' => $categorieId]);

            $data = array_map(function ($subcategorie) {
                return [
                    'id' => $subcategorie->getId(),
                    'nume' => $subcategorie->getNume(),
                    'pret_standard' => $subcategorie->getPretStandard(),
                    'km_utilizare_max' => null, // Folosit la consumabile
                    'pret_per_l' => $subcategorie->getPretPerL(), // IATĂ, pentru "motorină"
                ];
            }, $subcategorii);
        }

        return new JsonResponse($data);
    }
}
