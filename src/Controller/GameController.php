<?php

namespace App\Controller;

use App\Entity\Matrix;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    /**
     * @Route("/game", name="game")
     */
    public function index()
    {
        return $this->render('game/index.html.twig', [
            'controller_name' => 'GameController',
        ]);
    }

    /**
     * @Route("/chess", name="chess")
     */
    public function chess()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $matrixHtmlAllPiecesCurrentPosition = $this->drawSelectMoveBoard(9, 9);

        return $this->render('game/index.html.twig', [
            'board' => $matrixHtmlAllPiecesCurrentPosition,
        ]);
    }


    /**
     * @Route("/getMainBoard", name="get_main_board")
     */
    public function getMainBoard()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $matrixHtmlAllPiecesCurrentPosition = $this->drawSelectMoveBoard(9, 9);

        return new JsonResponse([
            'board' => $matrixHtmlAllPiecesCurrentPosition,
        ]);
    }


    /**
     * Esta función, diciendole de que pieza se trata, me dice que movimientos puedo hacer
     * @Route("/movePiece", name="move_piece")
     */
    public function movePiece()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        $id_piece = $request->get('id_piece');

        //Get Piece
        $piece = $entityManager->getRepository('App:Piece')->find($id_piece);

        //Get Current Piece Posion Bitboard
        $bitBoardCurrentPiecePosition = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'name' => 'current_position'
        ]);

        //Posibles moves for the piece by coordinates
        $possibleMovesByPiece = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'row' => $bitBoardCurrentPiecePosition->getRow(),
            'col' => $bitBoardCurrentPiecePosition->getCol()
        ]);

        //Get Own Pieces BitBoard
        switch ($piece->getColor()) {
            case 'white':
                $ownPiecesBitBoard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);
                $enemyPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);
                break;
            case'black':
                $ownPiecesBitBoard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);
                $enemyPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);
                break;
        }


//        //Matrix to show possible piece moves
//        $matrixPossible = $this->fromBitboardToMatrix(str_split($possibleMovesByPiece->getBitboard()), 9, 9);
//        $matrixHtmlPossibleMoves = $this->drawBoard($matrixPossible, 9, 9);
//
//        //Matrix to show Own Pieces
//        $matrixOwnPieces = $this->fromBitboardToMatrix(str_split($ownPiecesBitBoard->getBitboard()), 9, 9);
//        $matrixHtmlOwnPieces = $this->drawBoard($matrixOwnPieces, 9, 9);
//
//        //Matrix to show Own Pieces
//        $matrixEnemyPieces = $this->fromBitboardToMatrix(str_split($enemyPiecesBitboard->getBitboard()), 9, 9);
//        $matrixHtmlEnemyPieces = $this->drawBoard($matrixEnemyPieces, 9, 9);
//
//
//        //Matrix
//        $arrayBitboard = $this->boardAndBoard($ownPiecesBitBoard, $possibleMovesByPiece);
//        $matrixRes = $this->fromBitboardToMatrix($arrayBitboard, 9, 9);
//        $matrixHtmlRes = $this->drawBoard($matrixRes, 9, 9);
//
//        //Board1 and Not Board2
//        $arrayBitboardNot = $this->boardAndNotBitBoard($possibleMovesByPiece, $ownPiecesBitBoard);
//        $matrixNotRes = $this->fromBitboardToMatrix($arrayBitboardNot, 9, 9);
//        $matrixHtmlNotRes = $this->drawBoard($matrixNotRes, 9, 9);


        $own = $this->fromBitboardToCoordinatesArray(str_split($ownPiecesBitBoard->getBitboard()), 9, 9);
        $enemy = $this->fromBitboardToCoordinatesArray(str_split($enemyPiecesBitboard->getBitboard()), 9, 9);

        $baseMatrix = $this->matrixCreateWithoutModel(9, 9);
        $resultado = $this->getPieceVectorCoordinatesArrayToOwnPiece($baseMatrix, $bitBoardCurrentPiecePosition->getRow(), $bitBoardCurrentPiecePosition->getCol(), $own, $enemy, $piece);

        return new JsonResponse([
//            'ownPieces' => $matrixHtmlOwnPieces,
//            'possibleMoves' => $matrixHtmlPossibleMoves,
//            'enemyPieces' => $matrixHtmlEnemyPieces,
//            'res' => $matrixHtmlRes,
//            'notRes' => $matrixHtmlNotRes,
            'possibleMovesArray' => $resultado
        ]);
    }


    public function getPieceVectorCoordinatesArrayToOwnPiece($baseMatrix, $y, $x, $arrayPiecesOwn, $arrayPiecesEnemy, $piece)
    {
        $pieceGenerator = $piece->getGenerator();
        $promoted = $piece->getPromoted();
        $color = $piece->getColor();

        switch ($pieceGenerator) {
            case 'king';
                $resultMatrix = $this->kingOverOtherPieces($baseMatrix, $y, $x, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "rook":
                $resultMatrix = $this->rookOverOtherPieces($baseMatrix, $y, $x, 9, $color, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "bishop":
                $resultMatrix = $this->bishopOverOtherPieces($baseMatrix, $y, $x, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "goldGeneral":
                $resultMatrix = $this->goldGeneralOverOtherPieces($baseMatrix, $y, $x, $color, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "silverGeneral":
                $resultMatrix = $this->silverGeneralOverOtherPieces($baseMatrix, $y, $x, $color, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "knight":
                $resultMatrix = $this->knightOverOtherPieces($baseMatrix, $y, $x, $color, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "lance":
                $resultMatrix = $this->lanceOverOtherPieces($baseMatrix, $y, $x, $color, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
            case "pawn":
                $resultMatrix = $this->pawnOverOtherPieces($baseMatrix, $y, $x, $color, $promoted, $arrayPiecesOwn, $arrayPiecesEnemy);
                break;
        }
        return $resultMatrix;
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


    //Doesn't matter if its  Black or White side
    public function kingOverOtherPieces($matrixArray, $y, $x, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];

        $pieceMovementCoordinates = [
            [$y - 1, $x - 1],
            [$y - 1, $x],
            [$y - 1, $x + 1],
            [$y, $x + 1],
            [$y + 1, $x + 1],
            [$y + 1, $x],
            [$y + 1, $x - 1],
            [$y, $x - 1],
        ];

        foreach ($pieceMovementCoordinates as $coordinate) {
            if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                    array_push($arrayCoordinatesCanEat, $coordinate);
                } else {
                    array_push($arrayCoordinatesClean, $coordinate);
                }
            }
        }

        return [
            'clear' => $arrayCoordinatesClean,
            'eat' => $arrayCoordinatesCanEat
        ];
    }

    //Doesn't matter if its  Black or White side
    public function bishopOverOtherPieces($matrixArray, $y, $x, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];
        $arrayPieceMoves = [];

        switch ($promoted) {
            case false:
                $matrixDiagonalP = $this->mainDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces);
                $matrixDiagonalS = $this->secondaryDiagonal($y, $x, 9, 9, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayPieceMoves = array_merge($matrixDiagonalP, $matrixDiagonalS);
                break;
            case true:
                $matrixDiagonalP = $this->mainDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces);
                $matrixDiagonalS = $this->secondaryDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces);

                $pieceMovementCoordinates = [
                    [$y - 1, $x],
                    [$y, $x + 1],
                    [$y + 1, $x],
                    [$y, $x - 1],
                ];

                $arrayPieceMoves = array_merge($matrixDiagonalP, $matrixDiagonalS, $pieceMovementCoordinates);
                break;
        }


        foreach ($arrayPieceMoves as $coordinate) {
            if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                    array_push($arrayCoordinatesCanEat, $coordinate);
                } else {
                    array_push($arrayCoordinatesClean, $coordinate);
                }
            }
        }

        return [
            'clear' => $arrayCoordinatesClean,
            'eat' => $arrayCoordinatesCanEat
        ];
    }


    // Does matter side
    public function lanceOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];

        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {

            $pieceMovementCoordinates = $this->colForward($y, $x, 9, $color, $arrayOwnPieces, $arrayEnemyPieces);

            foreach ($pieceMovementCoordinates as $coordinate) {
                if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                    if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                        array_push($arrayCoordinatesCanEat, $coordinate);
                    } else {
                        array_push($arrayCoordinatesClean, $coordinate);
                    }
                }
            }
            $result = [
                'clear' => $arrayCoordinatesClean,
                'eat' => $arrayCoordinatesCanEat
            ];
        }
        return $result;
    }

    // Does matter side
    public function pawnOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];

        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {
            switch ($color) {
                case('white'):
                    $pieceMovementCoordinates = [
                        [$y + 1, $x]
                    ];
                    break;
                case('black'):
                    $pieceMovementCoordinates = [
                        [$y - 1, $x]
                    ];
                    break;
            }
            foreach ($pieceMovementCoordinates as $coordinate) {
                if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                    if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                        array_push($arrayCoordinatesCanEat, $coordinate);
                    } else {
                        array_push($arrayCoordinatesClean, $coordinate);
                    }
                }
            }
            $result = [
                'clear' => $arrayCoordinatesClean,
                'eat' => $arrayCoordinatesCanEat
            ];
        }
        return $result;
    }


    // Does matter side non promotional
    public function goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];

        switch ($color) {
            case('white'):
                $pieceMovementCoordinates = [
                    [$y - 1, $x],
                    [$y, $x + 1],
                    [$y + 1, $x + 1],
                    [$y + 1, $x],
                    [$y + 1, $x - 1],
                    [$y, $x - 1],
                ];
                break;
            case('black'):
                $pieceMovementCoordinates = [
                    [$y - 1, $x - 1],
                    [$y - 1, $x],
                    [$y - 1, $x + 1],
                    [$y, $x + 1],
                    [$y + 1, $x],
                    [$y, $x - 1],
                ];
                break;
        }

        foreach ($pieceMovementCoordinates as $coordinate) {
            if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                    array_push($arrayCoordinatesCanEat, $coordinate);
                } else {
                    array_push($arrayCoordinatesClean, $coordinate);
                }
            }
        }
        return [
            'clear' => $arrayCoordinatesClean,
            'eat' => $arrayCoordinatesCanEat
        ];
    }


    // Does matter side
    public function knightOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {

            $arrayCoordinatesClean = [];
            $arrayCoordinatesCanEat = [];
            switch ($color) {

                case('white'):
                    $pieceMovementCoordinates = [
                        [$y + 2, $x + 1],
                        [$y + 2, $x - 1],
                    ];
                    break;
                case('black'):
                    $pieceMovementCoordinates = [
                        [$y - 2, $x - 1],
                        [$y - 2, $x + 1],
                    ];
                    break;
            }

            foreach ($pieceMovementCoordinates as $coordinate) {
                if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                    if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                        array_push($arrayCoordinatesCanEat, $coordinate);
                    } else {
                        array_push($arrayCoordinatesClean, $coordinate);
                    }
                }
            }
            $result = [
                'clear' => $arrayCoordinatesClean,
                'eat' => $arrayCoordinatesCanEat
            ];

        }

        return $result;

    }


    // Does matter side
    public function silverGeneralOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {

            $arrayCoordinatesClean = [];
            $arrayCoordinatesCanEat = [];
            switch ($color) {

                case('white'):
                    $pieceMovementCoordinates = [
                        [$y - 1, $x - 1],
                        [$y - 1, $x + 1],
                        [$y + 1, $x + 1],
                        [$y + 1, $x],
                        [$y + 1, $x - 1],
                    ];
                    break;
                case('black'):
                    $pieceMovementCoordinates = [
                        [$y - 1, $x - 1],
                        [$y - 1, $x],
                        [$y - 1, $x + 1],
                        [$y + 1, $x + 1],
                        [$y + 1, $x - 1],
                    ];
                    break;
            }

            foreach ($pieceMovementCoordinates as $coordinate) {
                if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                    if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                        array_push($arrayCoordinatesCanEat, $coordinate);
                    } else {
                        array_push($arrayCoordinatesClean, $coordinate);
                    }
                }
            }

            $result = [
                'clear' => $arrayCoordinatesClean,
                'eat' => $arrayCoordinatesCanEat
            ];
        }
        return $result;
    }


    //Doesn't matter if its  Black or White side
    public function rookOverOtherPieces($matrixArray, $y, $x, $size, $color, $promoted = false, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesClean = [];
        $arrayCoordinatesCanEat = [];
        $arrayPieceMoves = [];

        switch ($promoted) {
            case false:
                $arrayRow = $this->row($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayCol = $this->col($y, $x, $color, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayPieceMoves = array_merge($arrayRow, $arrayCol);
                break;
            case true:
                $arrayRow = $this->row($y, $y, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayCol = $this->col($y, $x, $color, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $pieceMovementCoordinates = [
                    [$y - 1, $x - 1],
                    [$y - 1, $x + 1],
                    [$y + 1, $x + 1],
                    [$y + 1, $x - 1],
                ];
                $arrayPieceMoves = array_merge($arrayRow, $arrayCol, $pieceMovementCoordinates);
                break;
        }


        foreach ($arrayPieceMoves as $coordinate) {
            if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                if (array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces)) {
                    array_push($arrayCoordinatesCanEat, $coordinate);
                } else {
                    array_push($arrayCoordinatesClean, $coordinate);
                }
            }
        }

        return [
            'clear' => $arrayCoordinatesClean,
            'eat' => $arrayCoordinatesCanEat
        ];
    }


    public function row($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $forward = $this->rowForward($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces);
        $back = $this->rowBack($y, $x, $arrayOwnPieces, $arrayEnemyPieces);

        return array_merge($forward, $back);
    }


    //Doesn't matter if its  Black or White side
    public function col($y, $x, $color, $size, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $forward = $this->colForward($y, $x, $size, $color, $arrayOwnPieces, $arrayEnemyPieces);
        $down = $this->colDown($y, $x, $size, $color, $arrayOwnPieces, $arrayEnemyPieces);

        $res = array_merge($forward, $down);

        return $res;
    }


    //Doesn't matter if its  Black or White side
    public function mainDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = []; //   (/>)
        $x2 = $x;
        $y2 = $y;

        for ($i = $y - 1, $j = $x + 1; $i >= 0; $i--, $j++) {
            $res = $this->pushArrayCoordinates($i, $j, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }
        //   (</)
        for ($i2 = $y2 + 1, $j2 = $x2 - 1; $j2 >= 0; $i2++, $j2--) {
            $res = $this->pushArrayCoordinates($i2, $j2, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }

        return $arrayCoordinates;
    }

    //Doesn't matter if its  Black or White side
    public function secondaryDiagonal($y, $x, $row, $col, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = [];
        $x2 = $x;
        $y2 = $y;
        for ($i = $y - 1, $j = $x - 1; $i >= 0 || $j >= 0; $i--, $j--) {
            $res = $this->pushArrayCoordinates($i, $j, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }

        for ($i2 = $y2 + 1, $j2 = $x2 + 1; $i2 <= $row || $j2 <= $col; $i2++, $j2++) {
            $res = $this->pushArrayCoordinates($i2, $j2, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }

        return $arrayCoordinates;
    }


    // Does matter side
    public function colForward($y, $x, $size, $color, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = [];
        switch ($color) {
            case('white'):
                for ($i = $y + 1; $i <= $size; $i++) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        break;
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
            case('black'):
                for ($i = $y - 1; $i >= 0; $i--) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        break;
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
        }


        return $arrayCoordinates;
    }

    // Does matter side
    public function colDown($y, $x, $size, $color, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = [];
        switch ($color) {
            case('white'):
                for ($i = $y - 1; $i >= 0; $i--) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        break;
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
            case('black'):
                for ($i = $y + 1; $i <= $size; $i++) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        break;
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
        }

        return $arrayCoordinates;
    }


    public function rowForward($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = [];
        for ($j = $x + 1; $j < $size; $j++) {
            $res = $this->pushArrayCoordinates($y, $j, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }
        return $arrayCoordinates;
    }

    public function rowBack($y, $x, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinates = [];

        for ($j = $x - 1; $j >= 0; $j--) {
            $res = $this->pushArrayCoordinates($y, $j, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                break;
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }
        return $arrayCoordinates;
    }


    public function pushArrayCoordinates($y, $x, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $coord = null;

        if (array_search([$y, $x], $arrayOwnPieces) !== false) {
            $sigo = false;
        } elseif (array_search([$y, $x], $arrayEnemyPieces) !== false) {
            $coord = [$y, $x];
            $sigo = false;
        } else {
            $coord = [$y, $x];
            $sigo = true;
        }

        return [
            'sigo' => $sigo,
            'coord' => $coord
        ];

    }


    public function findKeyValue($array, $key, $val)
    {
        foreach ($array as $item) {
            if (is_array($item) && $this->findKeyValue($item, $key, $val)) return true;
            if (isset($item[$key]) && $item[$key] == $val) return true;
        }

        return false;
    }


    public function boardAndBoard($bitBoard1, $bitBoard2)
    {

        //Transformo a int para poder hacer operaciones Bitwise
        $upperBirboard1 = intval($bitBoard1->getBoard1(), 2);
        $middleBirboard1 = intval($bitBoard1->getBoard2(), 2);
        $bottomBirboard1 = intval($bitBoard1->getBoard3(), 2);

        $upperBirboard2 = intval($bitBoard2->getBoard1(), 2);
        $middleBirboard2 = intval($bitBoard2->getBoard2(), 2);
        $bottomBirboard2 = intval($bitBoard2->getBoard3(), 2);

        //Hago la operación por cada segmento
        $upper = ($upperBirboard2 & $upperBirboard1);
        $middle = ($middleBirboard2 & $middleBirboard1);
        $bottom = ($bottomBirboard2 & $bottomBirboard1);

        //Completo los strings a 27 cada uno.
        $resultadoUpper = str_pad(decbin($upper), 27, "0", STR_PAD_LEFT);
        $resultadoMiddle = str_pad(decbin($middle), 27, "0", STR_PAD_LEFT);
        $resultadoBottom = str_pad(decbin($bottom), 27, "0", STR_PAD_LEFT);


        //What can really do after checking that the Piece cant move to Own pieces already occupied possitions
        $resultadoFinal = $resultadoUpper . $resultadoMiddle . $resultadoBottom;

        return str_split($resultadoFinal);


    }

    public function notBitBoard($bitBoard)
    {
        // NOT Bitboard
        $ownUp = substr(decbin(~intval($bitBoard->getBoard1(), 2)), -27);
        $ownMiddle = substr(decbin(~intval($bitBoard->getBoard2(), 2)), -27);
        $ownBottom = substr(decbin(~intval($bitBoard->getBoard3(), 2)), -27);

        $ressss = $ownUp . $ownMiddle . $ownBottom;

        return str_split($ressss);

    }


    public function boardAndNotBitBoard($bitBoard1, $bitBoard2)
    {
        $upperNotBirboard2 = substr(decbin(~intval($bitBoard2->getBoard1(), 2)), -27);
        $middleNotBirboard2 = substr(decbin(~intval($bitBoard2->getBoard2(), 2)), -27);
        $bottomNotBirboard2 = substr(decbin(~intval($bitBoard2->getBoard3(), 2)), -27);

        $intUpperNotBirboard2 = intval($upperNotBirboard2, 2);
        $intMiddleNotBirboard2 = intval($middleNotBirboard2, 2);
        $intBottomNotBirboard2 = intval($bottomNotBirboard2, 2);

        $upperBirboard1 = intval($bitBoard1->getBoard1(), 2);
        $middleBirboard1 = intval($bitBoard1->getBoard2(), 2);
        $bottomBirboard1 = intval($bitBoard1->getBoard3(), 2);


        //Hago la operación por cada segmento
        $upper = ($upperBirboard1 & $intUpperNotBirboard2);
        $middle = ($middleBirboard1 & $intMiddleNotBirboard2);
        $bottom = ($bottomBirboard1 & $intBottomNotBirboard2);

        //Completo los strings a 27 cada uno.
        $resultadoUpper = str_pad(decbin($upper), 27, "0", STR_PAD_LEFT);
        $resultadoMiddle = str_pad(decbin($middle), 27, "0", STR_PAD_LEFT);
        $resultadoBottom = str_pad(decbin($bottom), 27, "0", STR_PAD_LEFT);

        $ressss = $resultadoUpper . $resultadoMiddle . $resultadoBottom;

        return $ressss;
    }


    public function drawSelectMoveBoard($row, $col)
    {
        $matrixHtml = "";
        $entityManager = $this->getDoctrine()->getManager();
        $matrixHtml .= "<style> .center{text-align: center; cursor: pointer; }</style>";
        $matrixHtml .= "<table id='move_to_table'>";
        for ($i = 0; $i < $row; $i++) {
            $matrixHtml .= "<tr>";
            for ($j = 0; $j < $col; $j++) {
                $pieceCurrentPositionBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(
                    [
                        'name' => 'current_position',
                        'row' => $i,
                        'col' => $j
                    ]);
                $piece_first_letter = isset($pieceCurrentPositionBitboard) ? substr($pieceCurrentPositionBitboard->getPiece()->getName(), 0, 1) : 0;
                $piece_id = isset($pieceCurrentPositionBitboard) ? $pieceCurrentPositionBitboard->getPiece()->getId() : null;
                $matrixHtml .= "<td data-row='" . $i . "' data-col='" . $j . "' data-piece='" . $piece_id . "' id='" . $i . $j . "'  class='center cell'>" . $piece_first_letter . "<td>";
            }
            $matrixHtml .= "</tr>";
        }
        $matrixHtml .= "</table>";

        return $matrixHtml;

    }


    public function drawBoard($matrixArray, $row, $col)
    {
        $matrixHtml = "";
        $matrixHtml .= "<table>";
        for ($i = 0; $i < $row; $i++) {
            $matrixHtml .= "<tr>";
            for ($j = 0; $j < $col; $j++) {
                if ($matrixArray[$i][$j] == 1) {
                    $matrixHtml .= "<td data-row='" . $i . "' data-col='" . $j . "'  class='center red cell'>" . $matrixArray[$i][$j] . "<td>";
                } else {
                    $matrixHtml .= "<td data-row='" . $i . "' data-col='" . $j . "'  class='center cell'>" . $matrixArray[$i][$j] . "<td>";
                }
            }
            $matrixHtml .= "</tr>";
        }
        $matrixHtml .= "</table>";
        return $matrixHtml;
    }


    public function fromBitboardToMatrix($arrayBitboard, $row, $col)
    {
        $matrix = array();
        $count = 0;
        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                $matrix[$i][$j] = $arrayBitboard[$count];
                $count++;
            }
        }
        return $matrix;
    }

    public function fromBitboardToCoordinatesArray($arrayBitboard, $row, $col)
    {
        $arrayCoordinates = array();
        $count = 0;
        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                if ($arrayBitboard[$count] == 1) {
                    array_push($arrayCoordinates, [$i, $j]);
                }
                $count++;
            }
        }
        return $arrayCoordinates;
    }


}
