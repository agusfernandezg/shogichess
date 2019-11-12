<?php

namespace App\Controller;

use App\Entity\Bitboard;
use App\Entity\Matrix;
use App\Entity\Matriz;
use App\Entity\Piece;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ChessController extends AbstractController
{

    /**
     * @Route("/makeMove", name="make_move")
     */
    public function makeMove()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        $id_piece = $request->get('id_piece');
        $row_to = $request->get('row_to');
        $col_to = $request->get('col_to');
        $eat = $request->get('eatable');

        //Get Piece
        $piece = $entityManager->getRepository('App:Piece')->find($id_piece);

        if ($eat == "true") {
            $victimBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy([
                'name' => 'current_position',
                'row' => $row_to,
                'col' => $col_to,
            ]);
            $victimBitboard->setPieceDeleted(true);
            $entityManager->persist($victimBitboard);
            $entityManager->flush();
            $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to);
        } else {
            $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to);
        }

        return new JsonResponse();
    }


    /**
     * Generate all needes DBS for the game to work.
     * @Route("/generateAllBitBoards", name="generate_all_bit_boards")
     */
    public function generateAllBitBoards()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findAll();

        foreach ($pieces as $piece) {
            $this->generatePositionBitboardsByPiece($piece, 9, 9);
        }

        //Generate a BitBoard with all the pieces in the initial position
        $this->generateAllPiecesPositionBitBoard();

        //Generate a BitBoard with all the WHITE pieces in the initial position
        $this->generateWhitePiecesPositionBitBoard();

        //Generate a BitBoard with all the BLACK pieces in the initial position
        $this->generateBlackPiecesPositionBitBoard();

        return new JsonResponse(['res' => 'ok']);
    }


    /**
     * PromotePiece
     * @Route("/promotePiece", name="promote_piece")
     */
    public function promotePiece()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        $id_piece = $request->get('id_piece');
        $piece = $entityManager->getRepository('App\Entity\Piece')->find($id_piece);

        $piece->setPromoted(true);
        $entityManager->persist($piece);
        $entityManager->flush();

        return new JsonResponse($piece->getName());
    }


    /**
     * Clear
     * - all_pieces
     * - all_white_pieces
     * - all_black_pieces
     * - default_pieces
     * Db's
     * @Route("/clearBds", name="clear_bds")
     */
    public function clearBds()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findAll();

        $curretPositionBitboars = $entityManager->getRepository('App:Bitboard')->findBy([
            'name' => 'current_position',
        ]);

        foreach ($curretPositionBitboars as $bitboard) {
            $bitboard->setPieceDeleted(false);
            $entityManager->persist($bitboard);
        }
        $entityManager->flush();

        foreach ($pieces as $piece) {
            $this->generatePositionBitboardsByPiece($piece, 9, 9);
            $piece->setPromoted(false);
            $entityManager->persist($piece);
        }
        $entityManager->flush();

        //Generate a BitBoard with all the pieces in the initial position
        $this->generateAllPiecesPositionBitBoard();

        //Generate a BitBoard with all the WHITE pieces in the initial position
        $this->generateWhitePiecesPositionBitBoard();

        //Generate a BitBoard with all the BLACK pieces in the initial position
        $this->generateBlackPiecesPositionBitBoard();


        return new JsonResponse(['res' => 'ok']);
    }


    /**
     *
     * Update all neede Db's after a move have being made
     *
     */
    public function generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to)
    {
        $entityManager = $this->getDoctrine()->getManager();

        //I change the  piece "current_position_bitboard"
        $this->generateBitBoardInitialPositionPerPiece($piece, $row_to, $col_to);

        //Generate a BitBoard with all the WHITE pieces in the initial position
        $this->generateWhitePiecesPositionBitBoardByCurrentPositionBitboards();

        //Generate a BitBoard with all the BLACK pieces in the initial position
        $this->generateBlackPiecesPositionBitBoardByCurrentPositionBitboards();

        //Generate a BitBoard with all the pieces in the initial position
        $this->generateAllPiecesPositionBitBoardByCurrentPositionBitboards();


        return new JsonResponse(['res' => 'ok']);
    }


    /**
     *
     * Remove an re-generate the current_position bitboards
     * after a move have being made
     *
     */
    public function generateAllPiecesPositionBitBoardByCurrentPositionBitboards()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $allPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_pieces']);
        $currentPositionPiecesBitboards = $entityManager->getRepository('App:Bitboard')->findBy(
            [
                'name' => 'current_position',
                'pieceDeleted' => false
            ]);

        $entityManager->remove($allPiecesBitboard);
        $entityManager->flush();

        $bitBoardAllPieces = new Bitboard();
        $matrix = $this->matrixCreateWithoutModel(9, 9);
        $bitBoardAllPieces->setName('all_pieces');

        foreach ($currentPositionPiecesBitboards as $currentPositionBitboard) {
            if (!$currentPositionBitboard->getPieceDeleted()) {
                $pieceRow = $currentPositionBitboard->getRow();
                $pieceCol = $currentPositionBitboard->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }
        }

        $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
        $stringArrayBitBoard = implode($bitBoardArray);

        $bitBoardAllPieces->setBitboard($stringArrayBitBoard);

        $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
        $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
        $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

        $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
        $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
        $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);

        $entityManager->persist($bitBoardAllPieces);
        $entityManager->flush();


        return new JsonResponse("ok");
    }

    /**
     *
     * Remove an re-generate the all_white_pieces bitboards
     * after a move have being made
     *
     */
    public function generateWhitePiecesPositionBitBoardByCurrentPositionBitboards()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $allWhitePiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);
        $currentPositionPiecesBitboards = $entityManager->getRepository('App:Bitboard')->findBy(
            [
                'name' => 'current_position',
                'color' => 'white',
            ]
        );

        $entityManager->remove($allWhitePiecesBitboard);
        $entityManager->flush();

        $bitBoardAllPieces = new Bitboard();
        $matrix = $this->matrixCreateWithoutModel(9, 9);
        $bitBoardAllPieces->setName('all_white_pieces');

        foreach ($currentPositionPiecesBitboards as $currentPositionBitboard) {
            if (!$currentPositionBitboard->getPieceDeleted()) {
                $pieceRow = $currentPositionBitboard->getRow();
                $pieceCol = $currentPositionBitboard->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }
        }

        $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
        $stringArrayBitBoard = implode($bitBoardArray);

        $bitBoardAllPieces->setBitboard($stringArrayBitBoard);
        $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
        $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
        $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

        $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
        $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
        $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);


        $entityManager->persist($bitBoardAllPieces);
        $entityManager->flush();


        return new JsonResponse("ok");
    }


    /**
     *
     * Remove an re-generate the all_black_pieces bitboards
     * after a move have being made
     *
     */
    public function generateBlackPiecesPositionBitBoardByCurrentPositionBitboards()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $allBlackPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);
        $currentPositionPiecesBitboards = $entityManager->getRepository('App:Bitboard')->findBy(
            [
                'name' => 'current_position',
                'color' => 'black',
            ]
        );

        $entityManager->remove($allBlackPiecesBitboard);
        $entityManager->flush();

        $bitBoardAllPieces = new Bitboard();
        $matrix = $this->matrixCreateWithoutModel(9, 9);
        $bitBoardAllPieces->setName('all_black_pieces');

        foreach ($currentPositionPiecesBitboards as $currentPositionBitboard) {
            if (!$currentPositionBitboard->getPieceDeleted()) {
                $pieceRow = $currentPositionBitboard->getRow();
                $pieceCol = $currentPositionBitboard->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }
        }

        $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);

        $stringArrayBitBoard = implode($bitBoardArray);

        $bitBoardAllPieces->setBitboard($stringArrayBitBoard);

        $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
        $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
        $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

        $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
        $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
        $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);


        $entityManager->persist($bitBoardAllPieces);
        $entityManager->flush();


        return new JsonResponse("ok");
    }


    /**
     *
     * Create
     * - all_pieces
     *  In the Initial Position (Depends on Piece Object configuration)
     *
     */
    public function generateAllPiecesPositionBitBoard()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findAll();

        $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_pieces']);

        if (!$checkIfAlreadyExiste) {
            $bitBoardAllPieces = new Bitboard();
            $matrix = $this->matrixCreateWithoutModel(9, 9);
            $bitBoardAllPieces->setName('all_pieces');

            foreach ($pieces as $piece) {
                $pieceRow = $piece->getRow();
                $pieceCol = $piece->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
                $this->generateBitBoardInitialPositionPerPiece($piece, $pieceRow, $pieceCol);
            }

            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardAllPieces->setBitboard($stringArrayBitBoard);

            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
            $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
            $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);

            $entityManager->persist($bitBoardAllPieces);
            $entityManager->flush();

        } else {

            $entityManager->remove($checkIfAlreadyExiste);
            $entityManager->flush();

            $bitBoardAllPieces = new Bitboard();
            $matrix = $this->matrixCreateWithoutModel(9, 9);
            $bitBoardAllPieces->setName('all_pieces');

            foreach ($pieces as $piece) {
                $pieceRow = $piece->getRow();
                $pieceCol = $piece->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
                $this->generateBitBoardInitialPositionPerPiece($piece, $pieceRow, $pieceCol);
            }

            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardAllPieces->setBitboard($stringArrayBitBoard);

            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
            $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
            $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);

            $entityManager->persist($bitBoardAllPieces);
            $entityManager->flush();


        }
        return new JsonResponse("ok");
    }


    /**
     * Generate all current_position bitboars by pieces inital position
     */
    public function generateBitBoardInitialPositionPerPiece($piece, $row, $col)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $bitBoardPieceActualPosition = new Bitboard();
        $matrix = $this->matrixCreateWithoutModel(9, 9);

        $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'name' => 'current_position',
        ]);


        //Si yá existe, lo actualizo a la posición Inicial de la piza, sino creo uno nuevo.
        if ($checkIfAlreadyExiste && !$checkIfAlreadyExiste->getPieceDeleted()) {
            $matrix[$row][$col] = 1;
            $piece->addBitboard($checkIfAlreadyExiste);
            $checkIfAlreadyExiste->setRow($row);
            $checkIfAlreadyExiste->setCol($col);

            $checkIfAlreadyExiste->setName("current_position");
            $checkIfAlreadyExiste->setColor($piece->getColor());
            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);

            $stringArrayBitBoard = implode($bitBoardArray);
            $checkIfAlreadyExiste->setBitboard($stringArrayBitBoard);

            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $checkIfAlreadyExiste->setBoard1($stringArrayBitBoard1);
            $checkIfAlreadyExiste->setBoard2($stringArrayBitBoard2);
            $checkIfAlreadyExiste->setBoard3($stringArrayBitBoard3);

            $entityManager->persist($checkIfAlreadyExiste);
            $entityManager->persist($piece);

        } else {
            $matrix[$row][$col] = 1;
            $piece->addBitboard($bitBoardPieceActualPosition);
            $bitBoardPieceActualPosition->setRow($row);
            $bitBoardPieceActualPosition->setCol($col);
            $bitBoardPieceActualPosition->setName("current_position");
            $bitBoardPieceActualPosition->setColor($piece->getColor());
            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardPieceActualPosition->setBitboard($stringArrayBitBoard);
            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardPieceActualPosition->setBoard1($stringArrayBitBoard1);
            $bitBoardPieceActualPosition->setBoard2($stringArrayBitBoard2);
            $bitBoardPieceActualPosition->setBoard3($stringArrayBitBoard3);

            $entityManager->persist($bitBoardPieceActualPosition);
            $entityManager->persist($piece);
        }
        $entityManager->flush();

    }


    public function generateWhitePiecesPositionBitBoard()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findBy(array('color' => 'white'));

        $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);

        if (!$checkIfAlreadyExiste) {
            $bitBoardAllPieces = new Bitboard();
            $matrix = $this->matrixCreateWithoutModel(9, 9);

            $bitBoardAllPieces->setName('all_white_pieces');

            foreach ($pieces as $piece) {
                $pieceRow = $piece->getRow();
                $pieceCol = $piece->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }

            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardAllPieces->setBitboard($stringArrayBitBoard);
            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
            $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
            $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);


            $entityManager->persist($bitBoardAllPieces);
            $entityManager->flush();
        } else {

            $entityManager->remove($checkIfAlreadyExiste);
            $entityManager->flush();

            $bitBoardAllPieces = new Bitboard();
            $matrix = $this->matrixCreateWithoutModel(9, 9);

            $bitBoardAllPieces->setName('all_white_pieces');

            foreach ($pieces as $piece) {
                $pieceRow = $piece->getRow();
                $pieceCol = $piece->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }

            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);
            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardAllPieces->setBitboard($stringArrayBitBoard);
            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
            $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
            $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);


            $entityManager->persist($bitBoardAllPieces);
            $entityManager->flush();


        }

        return new JsonResponse("ok");
    }

    public function generateBlackPiecesPositionBitBoard()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findBy(array('color' => 'black'));

        $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);

        if (!$checkIfAlreadyExiste) {
            $bitBoardAllPieces = new Bitboard();
            $matrix = $this->matrixCreateWithoutModel(9, 9);

            $bitBoardAllPieces->setName('all_black_pieces');

            foreach ($pieces as $piece) {
                $pieceRow = $piece->getRow();
                $pieceCol = $piece->getCol();
                $matrix[$pieceRow][$pieceCol] = 1;
            }

            $bitBoardArray = $this->fromMatrixToBitboard($matrix, 9, 9);

            $stringArrayBitBoard = implode($bitBoardArray);

            $bitBoardAllPieces->setBitboard($stringArrayBitBoard);

            $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
            $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
            $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

            $bitBoardAllPieces->setBoard1($stringArrayBitBoard1);
            $bitBoardAllPieces->setBoard2($stringArrayBitBoard2);
            $bitBoardAllPieces->setBoard3($stringArrayBitBoard3);


            $entityManager->persist($bitBoardAllPieces);
            $entityManager->flush();
        } else {

        }
        return new JsonResponse("ok");
    }


    public function generatePositionBitboardsByPiece($piece, $row, $col)
    {
        $metodoGeneradorString = $piece->getGenerator();
        $entityManager = $this->getDoctrine()->getManager();

        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {

                $checkIfAlreadyExiste = false;
                $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy(
                    [
                        'piece' => $piece,
                        'row' => $i,
                        'col' => $j,
                        'pieceDeleted' => false
                    ]);

                if (!$checkIfAlreadyExiste) {
                    $bitboard = new  Bitboard();
                    $promoted = $piece->getPromoted();
                    $color = $piece->getColor();

                    $baseMatrix = $this->matrixCreateWithoutModel(9, 9);
                    $matrix = $this->createMatrixPosibleMovements($baseMatrix, $metodoGeneradorString, $promoted, $color, $i, $j);
                    $arrayBitBoard = $this->fromMatrixToBitboard($matrix, 9, 9);

                    $stringArrayBitBoard = implode($arrayBitBoard);
                    $stringArrayBitBoard1 = substr($stringArrayBitBoard, 0, 27);
                    $stringArrayBitBoard2 = substr($stringArrayBitBoard, 26, 27);
                    $stringArrayBitBoard3 = substr($stringArrayBitBoard, 54, 27);

                    $bitboard->setBitboard($stringArrayBitBoard);

                    $bitboard->setBoard1($stringArrayBitBoard1);
                    $bitboard->setBoard2($stringArrayBitBoard2);
                    $bitboard->setBoard3($stringArrayBitBoard3);

                    $bitboard->setRow($i);
                    $bitboard->setCol($j);
                    $bitboard->setName($piece->getCode());

                    $piece->addBitboard($bitboard);

                    $entityManager->persist($piece);
                    $entityManager->persist($bitboard);
                }
            }
        }
        $entityManager->flush();
    }


    public function createMatrixPosibleMovements($baseMatrix, $metodoGeneradorString, $promoted, $color, $y, $x)
    {
        $resultMatrix = null;
        switch ($metodoGeneradorString) {
            case "king":
                $resultMatrix = $this->king($baseMatrix, $y, $x);
                break;
            case "rook":
                $resultMatrix = $this->rook($baseMatrix, $y, $x, 9, $promoted);
                break;
            case "bishop":
                $resultMatrix = $this->bishop($baseMatrix, $y, $x, $promoted);
                break;
            case "goldGeneral":
                $resultMatrix = $this->goldGeneral($baseMatrix, $y, $x, $color);
                break;
            case "silverGeneral":
                $resultMatrix = $this->silverGeneral($baseMatrix, $y, $x, $color, $promoted);
                break;
            case "knight":
                $resultMatrix = $this->knight($baseMatrix, $y, $x, $color, $promoted);
                break;
            case "lance":
                $resultMatrix = $this->lance($baseMatrix, $y, $x, $color, $promoted);
                break;
            case "pawn":
                $resultMatrix = $this->pawn($baseMatrix, $y, $x, $color, $promoted);
                break;
        }

        return $resultMatrix;
    }


    public function drawBoard($matrixArray, $row, $col)
    {
        print_r("<style> .center{text-align: center;}</style>");
        print_r("<table>");
        for ($i = 0; $i < $row; $i++) {
            print_r("<tr>");
            for ($j = 0; $j < $col; $j++) {
                print_r("<td data-row='" . $i . "' data-col='" . $j . "'  class='center cell'>" . $matrixArray[$i][$j]) . "<td>";
            }
            print_r("</tr>");
        }
        print_r("</table>");
    }


    /**
     * @Route("/crearPawns", name="crearPawns")
     */
    public function crearPawns()
    {
        $entityManager = $this->getDoctrine()->getManager();

        //White
        for ($i = 0, $j = 2; $i < 9; $i++) {
            $pawn = new Piece();
            $pawn->setName('Pawn' . strval($i + 1));
            $pawn->setCode('pawn_' . strval($i + 1));
            $pawn->setColor('white');
            $pawn->setRow($j);
            $pawn->setCol($i);
            $pawn->setGenerator('pawn');
            $pawn->setPromotedgenerator('pawn');
            $pawn->setPromoted(false);
            $entityManager->persist($pawn);
        }


        //Black
        for ($i = 0, $j = 6; $i < 9; $i++) {
            $pawn = new Piece();
            $pawn->setName('Pawn' . strval($i + 1));
            $pawn->setCode('pawn_' . strval($i + 1));
            $pawn->setColor('black');
            $pawn->setRow($j);
            $pawn->setCol($i);
            $pawn->setGenerator('pawn');
            $pawn->setPromotedgenerator('pawn');
            $pawn->setPromoted(false);
            $entityManager->persist($pawn);
        }
        $entityManager->flush();

        return new JsonResponse("ok");
    }


//Doesn't matter if its  Black or White side
    public function king($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = 1 : null;
        isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
        isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = 1 : null;
        isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = 1 : null;
        isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = 1 : null;
        isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
        isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = 1 : null;
        isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = 1 : null;

        return $matrixArray;
    }

//Doesn't matter if its  Black or White side
    public function rook($matrixArray, $y, $x, $size, $promoted = false)
    {
        switch ($promoted) {
            case false:
                $matrixRow = $this->row($matrixArray, $y, $size);
                $matrix = $this->col($matrixRow, $x, $size);
                break;
            case true:
                $matrixRow = $this->row($matrixArray, $y, $size);
                $matrix = $this->col($matrixRow, $x, $size);

                isset($matrix[$y - 1][$x - 1]) ? $matrix[$y - 1][$x - 1] = 1 : null;
                //isset($matrix[$y - 1][$x]) ? $matrix[$y - 1][$x] = 1 : null;
                isset($matrix[$y - 1][$x + 1]) ? $matrix[$y - 1][$x + 1] = 1 : null;
                //isset($matrix[$y][$x + 1]) ? $matrix[$y][$x + 1] = 1 : null;
                isset($matrix[$y + 1][$x + 1]) ? $matrix[$y + 1][$x + 1] = 1 : null;
                //isset($matrix[$y + 1][$x]) ? $matrix[$y + 1][$x] = 1 : null;
                isset($matrix[$y + 1][$x - 1]) ? $matrix[$y + 1][$x - 1] = 1 : null;
                // isset($matrix[$y][$x - 1]) ? $matrix[$y][$x - 1] = 1 : null;
                break;
        }

        return $matrix;
    }

//Doesn't matter if its  Black or White side
    public function bishop($matrixArray, $y, $x, $promoted)
    {
        switch ($promoted) {
            case false:
                $matrixDiagonalP = $this->mainDiagonal($matrixArray, $y, $x);
                $matrix = $this->secondaryDiagonal($matrixDiagonalP, $y, $x, 9, 9);
                break;
            case true:
                $matrixDiagonalP = $this->mainDiagonal($matrixArray, $y, $x);
                $matrix = $this->secondaryDiagonal($matrixDiagonalP, $y, $x, 9, 9);
                //isset($matrix[$y - 1][$x - 1]) ? $matrix[$y - 1][$x - 1] = 1 : null;
                isset($matrix[$y - 1][$x]) ? $matrix[$y - 1][$x] = 1 : null;
                //isset($matrix[$y - 1][$x + 1]) ? $matrix[$y - 1][$x + 1] = 1 : null;
                isset($matrix[$y][$x + 1]) ? $matrix[$y][$x + 1] = 1 : null;
                //isset($matrix[$y + 1][$x + 1]) ? $matrix[$y + 1][$x + 1] = 1 : null;
                isset($matrix[$y + 1][$x]) ? $matrix[$y + 1][$x] = 1 : null;
                //isset($matrix[$y + 1][$x - 1]) ? $matrix[$y + 1][$x - 1] = 1 : null;
                isset($matrix[$y][$x - 1]) ? $matrix[$y][$x - 1] = 1 : null;

                break;
        }

        return $matrix;
    }

// Does matter side non promotional
    public function goldGeneral($matrixArray, $y, $x, $color)
    {
        switch ($color) {
            case('white'):
                //isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = 1 : null;
                isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                //isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = 1 : null;
                isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = 1 : null;
                isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = 1 : null;
                isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
                isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = 1 : null;
                isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = 1 : null;

                break;

            case('black'):
                isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = 1 : null;
                isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = 1 : null;
                isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = 1 : null;
                //isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = 1 : null;
                isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
                //isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = 1 : null;
                isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = 1 : null;

                break;
        }

        return $matrixArray;
    }

// Does matter side
    public function silverGeneral($matrixArray, $y, $x, $color, $promoted)
    {
        if ($promoted == true) {
            $matrixArray = $this->goldGeneral($matrixArray, $y, $x, $color);
        } else {

            switch ($color) {

                case('white'):
                    isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = 1 : null;
                    //isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                    isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = 1 : null;
                    //isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = 1 : null;
                    isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = 1 : null;
                    isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
                    isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = 1 : null;
                    //isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = 1 : null;

                    break;

                case('black'):
                    isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = 1 : null;
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                    isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = 1 : null;
                    //isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = 1 : null;
                    isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = 1 : null;
                    //isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
                    isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = 1 : null;
                    //isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = 1 : null;

                    break;
            }
        }
        return $matrixArray;
    }

// Does matter side
    public function knight($matrixArray, $y, $x, $color, $promoted)
    {
        if ($promoted == true) {
            $matrixArray = $this->goldGeneral($matrixArray, $y, $x, $color);
        } else {
            switch ($color) {

                case('white'):
                    isset($matrixArray[$y + 2][$x + 1]) ? $matrixArray[$y + 2][$x + 1] = 1 : null;
                    isset($matrixArray[$y + 2][$x - 1]) ? $matrixArray[$y + 2][$x - 1] = 1 : null;

                    break;

                case('black'):
                    isset($matrixArray[$y - 2][$x - 1]) ? $matrixArray[$y - 2][$x - 1] = 1 : null;
                    isset($matrixArray[$y - 2][$x + 1]) ? $matrixArray[$y - 2][$x + 1] = 1 : null;

                    break;
            }
        }

        return $matrixArray;
    }

// Does matter side
    public function lance($matrixArray, $y, $x, $color, $promoted)
    {
        if ($promoted == true) {
            $matrix = $this->goldGeneral($matrixArray, $y, $x, $color);
        } else {
            $matrix = $this->colForward($matrixArray, $y, $x, 9, $color);
        }
        return $matrix;
    }


// Does matter side
    public function pawn($matrixArray, $y, $x, $color, $promoted)
    {
        if ($promoted == true) {
            $matrixArray = $this->goldGeneral($matrixArray, $y, $x, $color);
        } else {
            switch ($color) {
                case('white'):
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                    break;
                case('black'):
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                    break;
            }
        }
        return $matrixArray;
    }


//Doesn't matter if its  Black or White side
    public function row($matrixArray, $row, $size)
    {
        for ($j = 0; $j < $size; $j++) {
            $matrixArray[$row][$j] = 1;
        }
        return $matrixArray;
    }

//Doesn't matter if its  Black or White side
    public function col($matrixArray, $col, $size)
    {
        for ($i = 0; $i < $size; $i++) {
            $matrixArray[$i][$col] = 1;
        }
        return $matrixArray;
    }

// Does matter side
    public function colForward($matrixArray, $y, $x, $size, $color)
    {
        switch ($color) {
            case('white'):
                for ($i = $y; $i <= $size; $i++) {
                    $matrixArray[$i][$x] = 1;
                }
                break;
            case('black'):
                for ($i = $y; $i >= 0; $i--) {
                    $matrixArray[$i][$x] = 1;
                }
                break;
        }

        return $matrixArray;
    }


//Doesn't matter if its  Black or White side
    public function mainDiagonal($matrixArray, $y, $x)
    {
        for ($i = $y, $j = $x; $i >= 0; $i--, $j++) {
            $matrixArray[$i][$j] = 1;
        }

        for ($j = $x, $i = $y; $j >= 0; $i++, $j--) {
            $matrixArray[$i][$j] = 1;
        }
        return $matrixArray;
    }

//Doesn't matter if its  Black or White side
    public function secondaryDiagonal($matrixArray, $y, $x, $row, $col)
    {
        for ($i = $y, $j = $x; $i >= 0 || $j >= 0; $i--, $j--) {
            $matrixArray[$i][$j] = 1;
        }

        for ($i = $y, $j = $x; $i <= $row || $j <= $col; $i++, $j++) {
            $matrixArray[$i][$j] = 1;
        }
        return $matrixArray;
    }


    /**
     * @param Matrix $matrix
     * @return array
     */
    public function matrixCreate(Matrix $matrixModel)
    {
        $matrixArray = array();
        $row = $matrixModel->getRow();
        $col = $matrixModel->getCol();

        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                $matrixArray[$i][$j] = "(" . $i . ';' . $j . ")";
            }
        }
        return $matrixArray;
    }


    /**
     * @param Matrix $matrix
     * @return array
     */
    public function matrixCreateWithoutModel($row, $col)
    {
        $matrixArray = array();
        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                $matrixArray[$i][$j] = 0;
            }
        }
        return $matrixArray;
    }

    public function fromMatrixToBitboard($matrix, $row, $col)
    {
        $arrayBitboard = array();
        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                array_push($arrayBitboard, $matrix[$i][$j]);
            }
        }
        return $arrayBitboard;
    }


}
