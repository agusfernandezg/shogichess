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
     * @Route("/chess", name="chess")
     */
    public function index()
    {
        return $this->render('chess/index.html.twig', [
            'controller_name' => 'ChessController',
        ]);
    }


    public function startTheGame()
    {


    }


    /**
     * @Route("/board", name="board")
     */
    public function createBoard()
    {
        $boardModel = new Matrix();
        $boardModel->setName("board");
        $boardModel->setRow(9);
        $boardModel->setCol(9);

        $boardArray = $this->matrixCreate($boardModel);
        $this->drawBoard($boardArray, 9, 9);
        print_r("<br>");

//        $newBoardF = $this->row($boardArray, 5, 9);
//        $this->drawBoard($newBoardF, 9, 9);
//        print_r("<br>");
//
//        $newBoardC = $this->col($boardArray, 5, 9);
//        $this->drawBoard($newBoardC, 9, 9);
//
//        print_r("<br>");
//        $newBoardDP = $this->diagonalPrincipal($boardArray, 5, 5);
//        $this->drawBoard($newBoardDP, 9, 9);
//
//        print_r("<br>");
//        $newBoardDS = $this->diagonalSecundaria($boardArray, 5, 5, 9, 9);
//        $this->drawBoard($newBoardDS, 9, 9);
//
//        print_r("<br>");
//        $newBoardKing = $this->pawn($boardArray, 5, 3);
//        $this->drawBoard($newBoardKing, 9, 9);

        die();
        //return new JsonResponse("Punck");
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
                $matrixArray[$i][$j] = "(" . $i . ';' . $j . ")";
            }
        }

        return $matrixArray;
    }

    /**
     * @Route("/generateBitBoards", name="generate_bit_boards")
     */
    public function genetaBitboards()
    {

        $entityManager = $this->getDoctrine()->getManager();
        $pieces = $entityManager->getRepository('App\Entity\Piece')->findAll();

        foreach ($pieces as $piece) {
            $this->generatePositionBitboardsByPiece($piece, 9, 9);
        }

        die("Listo");

    }


    public function generatePositionBitboardsByPiece($piece, $row, $col)
    {
        $metodoGeneradorString = "";
        $metodoGeneradorString = $piece->getGenerator();

        $entityManager = $this->getDoctrine()->getManager();

        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {

                $bitboard = new  Bitboard();
                $promoted = $piece->getPromoted();
                $color = $piece->getColor();

                $baseMatrix = $this->matrixCreateWithoutModel(9, 9);
                $matrix = $this->createMatrixPosibleMovements($baseMatrix, $metodoGeneradorString, $promoted, $color, $i, $j);
                $arrayBitBoard = $this->fromMatrixToBitboard($matrix, 9, 9);

                $bitboard->setBitboard(implode(", ", $arrayBitBoard));

                $piece->addBitboard($bitboard);

                $entityManager->persist($piece);
                $entityManager->persist($bitboard);
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
        for ($i = 0, $j = 8; $i < 9; $i++) {
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
    public function row($matrixArray, $row, $size)
    {
        for ($j = 0; $j < $size; $j++) {
            $matrixArray[$row][$j] = 'F';
        }
        return $matrixArray;
    }

    //Doesn't matter if its  Black or White side
    public function col($matrixArray, $col, $size)
    {
        for ($i = 0; $i < $size; $i++) {
            $matrixArray[$i][$col] = 'C';
        }
        return $matrixArray;
    }

    // Does matter side
    public function colForward($matrixArray, $y, $x, $size, $color)
    {
        switch ($color) {

            case('white'):
                for ($i = $y; $i >= 0; $i--) {
                    $matrixArray[$i][$x] = 'C';
                }
                break;

            case('black'):
                for ($i = $y; $i <= $size; $i++) {
                    $matrixArray[$i][$x] = 'C';
                }
                break;
        }

        return $matrixArray;
    }


    //Doesn't matter if its  Black or White side
    public function mainDiagonal($matrixArray, $y, $x)
    {
        for ($i = $y, $j = $x; $i >= 0; $i--, $j++) {
            $matrixArray[$i][$j] = 'D';
        }

        for ($j = $x, $i = $y; $j >= 0; $i++, $j--) {
            $matrixArray[$i][$j] = 'D';
        }
        return $matrixArray;
    }

    //Doesn't matter if its  Black or White side
    public function secondaryDiagonal($matrixArray, $y, $x, $row, $col)
    {
        for ($i = $y, $j = $x; $i >= 0 || $j >= 0; $i--, $j--) {
            $matrixArray[$i][$j] = 'D';
        }

        for ($i = $y, $j = $x; $i <= $row || $j <= $col; $i++, $j++) {
            $matrixArray[$i][$j] = 'D';
        }
        return $matrixArray;
    }


    //Doesn't matter if its  Black or White side
    public function king($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
        isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
        isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
        isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
        isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
        isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
        isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
        isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

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

                isset($matrix[$y - 1][$x - 1]) ? $matrix[$y - 1][$x - 1] = "r" : null;
                //isset($matrix[$y - 1][$x]) ? $matrix[$y - 1][$x] = "r" : null;
                isset($matrix[$y - 1][$x + 1]) ? $matrix[$y - 1][$x + 1] = "r" : null;
                //isset($matrix[$y][$x + 1]) ? $matrix[$y][$x + 1] = "r" : null;
                isset($matrix[$y + 1][$x + 1]) ? $matrix[$y + 1][$x + 1] = "r" : null;
                //isset($matrix[$y + 1][$x]) ? $matrix[$y + 1][$x] = "r" : null;
                isset($matrix[$y + 1][$x - 1]) ? $matrix[$y + 1][$x - 1] = "r" : null;
                // isset($matrix[$y][$x - 1]) ? $matrix[$y][$x - 1] = "r" : null;

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

                //isset($matrix[$y - 1][$x - 1]) ? $matrix[$y - 1][$x - 1] = "r" : null;
                isset($matrix[$y - 1][$x]) ? $matrix[$y - 1][$x] = "r" : null;
                //isset($matrix[$y - 1][$x + 1]) ? $matrix[$y - 1][$x + 1] = "r" : null;
                isset($matrix[$y][$x + 1]) ? $matrix[$y][$x + 1] = "r" : null;
                //isset($matrix[$y + 1][$x + 1]) ? $matrix[$y + 1][$x + 1] = "r" : null;
                isset($matrix[$y + 1][$x]) ? $matrix[$y + 1][$x] = "r" : null;
                //isset($matrix[$y + 1][$x - 1]) ? $matrix[$y + 1][$x - 1] = "r" : null;
                isset($matrix[$y][$x - 1]) ? $matrix[$y][$x - 1] = "r" : null;

                break;
        }

        return $matrix;
    }

    // Does matter side non promotional
    public function goldGeneral($matrixArray, $y, $x, $color)
    {
        switch ($color) {

            case('white'):
                //isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
                isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                //isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
                isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
                isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
                isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
                isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
                isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

                break;

            case('black'):
                isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
                isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
                isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
                //isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
                isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
                //isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
                isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

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
                    isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
                    //isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                    isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
                    //isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
                    isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
                    isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
                    isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
                    //isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

                    break;

                case('black'):
                    isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                    isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
                    //isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
                    isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
                    //isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
                    isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
                    //isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

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
                    isset($matrixArray[$y + 2][$x + 1]) ? $matrixArray[$y - 2][$x - 1] = "r" : null;
                    isset($matrixArray[$y + 2][$x - 1]) ? $matrixArray[$y - 2][$x + 1] = "r" : null;

                    break;

                case('black'):
                    isset($matrixArray[$y - 2][$x - 1]) ? $matrixArray[$y - 2][$x - 1] = "r" : null;
                    isset($matrixArray[$y - 2][$x + 1]) ? $matrixArray[$y - 2][$x + 1] = "r" : null;

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
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                    break;
                case('black'):
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
                    break;
            }
        }
        return $matrixArray;
    }


    /**
     *
     *
     * Pieces
     * Piece    Filename
     * King    kingB.svg
     * Rook    rookB.svg
     * Bishop    bishopB.svg
     * Gold General    goldB.svg
     * Silver General    silverB.svg
     * Knight    knightB.svg
     * Lance    lanceB.svg
     * Pawn    pawnB.svg
     * Promoted Rook    rookPB.svg
     * Promoted Bishop    bishopPB.svg
     * Promoted Silver General    silverPB.svg
     * Promoted Knight    knightPB.svg
     * Promoted Lance    lancePB.svg
     * Promoted Pawn    pawnPB.svg
     *
     */

    /**
     * 1-
     * 0 0 0
     * 0 x 0
     * 0 0 0
     *
     * 2-
     *   |
     * - x -
     *   |
     *
     * 3-
     * \   /
     *   x
     * /   \
     *
     * 4-
     * 0 0 0
     * 0 x 0
     *   0
     *
     * 5-
     * 0 0 0
     *   x
     * 0   0
     *
     * 6-
     * 0   0
     *
     *   x
     *
     * 7-
     *   |
     *   x
     *
     * 8-
     *   0
     *   x
     *
     * 9-
     * \ 0 /
     * 0 x 0
     * / 0 \
     *
     * 10-
     * 0 | 0
     * - x -
     * 0 | 0
     *
     *
     **/


    /**
     *
     *    9 8 7 6 5 4 3 2 1
     * a
     * b
     * c
     * d
     * e          0
     * f
     * g
     * h
     * i
     *
     */


}
