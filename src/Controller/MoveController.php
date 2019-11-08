<?php

namespace App\Controller;

use App\Entity\Move;
use App\Form\MoveType;
use App\Repository\MoveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/move")
 */
class MoveController extends AbstractController
{
    /**
     * @Route("/", name="move_index", methods={"GET"})
     */
    public function index(MoveRepository $moveRepository): Response
    {
        return $this->render('move/index.html.twig', [
            'moves' => $moveRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="move_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $move = new Move();
        $form = $this->createForm(MoveType::class, $move);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($move);
            $entityManager->flush();

            return $this->redirectToRoute('move_index');
        }

        return $this->render('move/new.html.twig', [
            'move' => $move,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="move_show", methods={"GET"})
     */
    public function show(Move $move): Response
    {
        return $this->render('move/show.html.twig', [
            'move' => $move,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="move_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Move $move): Response
    {
        $form = $this->createForm(MoveType::class, $move);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('move_index');
        }

        return $this->render('move/edit.html.twig', [
            'move' => $move,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="move_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Move $move): Response
    {
        if ($this->isCsrfTokenValid('delete'.$move->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($move);
            $entityManager->flush();
        }

        return $this->redirectToRoute('move_index');
    }
}
