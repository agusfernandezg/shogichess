<?php

namespace App\Controller;

use App\Entity\Matrix;
use App\Entity\Matriz;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

//        $newBoardF = $this->fila($boardArray, 5, 9);
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

        print_r("<br>");
        $newBoardKing = $this->pawn($boardArray, 5, 3);
        $this->drawBoard($newBoardKing, 9, 9);

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


    public function drawBoard($matrixArray, $row, $col)
    {
        print_r("<style> .center{text-align: center;}</style>");

        print_r("<table>");
        for ($i = 0; $i < $row; $i++) {
            print_r("<tr>");
            for ($j = 0; $j < $col; $j++) {
                print_r("<td class='center'>" . $matrixArray[$i][$j]) . "<td>";
            }
            print_r("</tr>");
        }

        print_r("</table>");
    }


    public function fila($matrixArray, $row, $size)
    {
        for ($j = 0; $j < $size; $j++) {
            $matrixArray[$row][$j] = 'F';
        }
        return $matrixArray;
    }


    public function col($matrixArray, $col, $size)
    {
        for ($i = 0; $i < $size; $i++) {
            $matrixArray[$i][$col] = 'C';
        }
        return $matrixArray;
    }

    public function colForward($matrixArray, $y, $x)
    {
        for ($i = $y; $i >= 0; $i--) {
            $matrixArray[$i][$x] = 'C';
        }
        return $matrixArray;
    }


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


    public function tower($matrixArray, $y, $x, $size)
    {
        $matrixRow = $this->fila($matrixArray, $y, $size);
        $matrix = $this->col($matrixRow, $x, $size);

        return $matrix;
    }


    public function bishop($matrixArray, $y, $x)
    {
        $matrixDiagonalP = $this->mainDiagonal($matrixArray, $y, $x);
        $matrix = $this->secondaryDiagonal($matrixDiagonalP, $y, $x, 9, 9);

        return $matrix;
    }

    public function goldGeneral($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
        isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
        isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
        isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
        //isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
        isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
        //isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
        isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

        return $matrixArray;
    }


    public function silverGeneral($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 1][$x - 1]) ? $matrixArray[$y - 1][$x - 1] = "r" : null;
        isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;
        isset($matrixArray[$y - 1][$x + 1]) ? $matrixArray[$y - 1][$x + 1] = "r" : null;
        //isset($matrixArray[$y][$x + 1]) ? $matrixArray[$y][$x + 1] = "r" : null;
        isset($matrixArray[$y + 1][$x + 1]) ? $matrixArray[$y + 1][$x + 1] = "r" : null;
        //isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = "r" : null;
        isset($matrixArray[$y + 1][$x - 1]) ? $matrixArray[$y + 1][$x - 1] = "r" : null;
        //isset($matrixArray[$y][$x - 1]) ? $matrixArray[$y][$x - 1] = "r" : null;

        return $matrixArray;
    }


    public function knight($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 2][$x - 1]) ? $matrixArray[$y - 2][$x - 1] = "r" : null;
        isset($matrixArray[$y - 2][$x + 1]) ? $matrixArray[$y - 2][$x + 1] = "r" : null;
        return $matrixArray;
    }

    public function lance($matrixArray, $y, $x)
    {
        $matrix = $this->colForward($matrixArray, $y, $x);

        return $matrix;
    }


    public function pawn($matrixArray, $y, $x)
    {
        isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = "r" : null;

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
