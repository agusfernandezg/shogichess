<?php

namespace App\Controller;

use App\Entity\Matrix;
use App\Form\MatrixType;
use App\Repository\MatrixRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/matrix")
 */
class MatrixController extends AbstractController
{
    /**
     * @Route("/", name="matrix_index", methods={"GET"})
     */
    public function index(MatrixRepository $matrixRepository): Response
    {
        return $this->render('matrix/index.html.twig', [
            'matrices' => $matrixRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="matrix_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $matrix = new Matrix();
        $form = $this->createForm(MatrixType::class, $matrix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($matrix);
            $entityManager->flush();

            return $this->redirectToRoute('matrix_index');
        }

        return $this->render('matrix/new.html.twig', [
            'matrix' => $matrix,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="matrix_show", methods={"GET"})
     */
    public function show(Matrix $matrix): Response
    {
        return $this->render('matrix/show.html.twig', [
            'matrix' => $matrix,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="matrix_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Matrix $matrix): Response
    {
        $form = $this->createForm(MatrixType::class, $matrix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('matrix_index');
        }

        return $this->render('matrix/edit.html.twig', [
            'matrix' => $matrix,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="matrix_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Matrix $matrix): Response
    {
        if ($this->isCsrfTokenValid('delete'.$matrix->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($matrix);
            $entityManager->flush();
        }

        return $this->redirectToRoute('matrix_index');
    }
}
