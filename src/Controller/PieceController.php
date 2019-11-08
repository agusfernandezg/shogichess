<?php

namespace App\Controller;

use App\Entity\Piece;
use App\Form\PieceType;
use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/piece")
 */
class PieceController extends AbstractController
{
    /**
     * @Route("/", name="piece_index", methods={"GET"})
     */
    public function index(PieceRepository $pieceRepository): Response
    {
        return $this->render('piece/index.html.twig', [
            'pieces' => $pieceRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="piece_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $piece = new Piece();
        $form = $this->createForm(PieceType::class, $piece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($piece);
            $entityManager->flush();

            return $this->redirectToRoute('piece_index');
        }

        return $this->render('piece/new.html.twig', [
            'piece' => $piece,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="piece_show", methods={"GET"})
     */
    public function show(Piece $piece): Response
    {
        return $this->render('piece/show.html.twig', [
            'piece' => $piece,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="piece_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Piece $piece): Response
    {
        $form = $this->createForm(PieceType::class, $piece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('piece_index');
        }

        return $this->render('piece/edit.html.twig', [
            'piece' => $piece,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="piece_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Piece $piece): Response
    {
        if ($this->isCsrfTokenValid('delete'.$piece->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($piece);
            $entityManager->flush();
        }

        return $this->redirectToRoute('piece_index');
    }
}
