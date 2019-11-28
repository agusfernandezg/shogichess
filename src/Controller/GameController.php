<?php

namespace App\Controller;

use App\Entity\Bitboard;
use App\Entity\History;
use App\Entity\Matrix;
use App\Entity\Piece;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{

    /**
     * @Route("/", name="chess")
     */
    public function chess()
    {
        $resultado = $this->drawSelectMoveBoard(9, 9);

        return $this->render('game/index.html.twig', [
            'eatenPieces' => $resultado['eatenPieces'],
            'board' => $resultado['board'],
        ]);
    }


    /**
     * @Route("/getMainBoard", name="get_main_board")
     */
    public function getMainBoard()
    {
        $resultado = $this->drawSelectMoveBoard(9, 9);

        return new JsonResponse([
            'eatenPieces' => $resultado['eatenPieces'],
            'board' => $resultado['board'],
        ]);
    }


    /**
     * @Route("/getEatenPiecesJson", name="get_eaten_pieces_json")
     */
    function getEatenPiecesJson()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $whitePiecesHtml = "";
        $blackPiecesHtml = "";
        $eatenPieces = $entityManager->getRepository('App\Entity\Bitboard')->findBy(['pieceDeleted' => true]);

        foreach ($eatenPieces as $bitboard) {
            $piece_id = $bitboard->getPiece()->getId();
            switch ($bitboard->getColor()) {
                case 'white':
                    $whitePiecesHtml .= "<div id='" . $piece_id . "' class='eaten white-eaten-piece'>" . $piece_id . "</div>";
                    break;
                case 'black':
                    $blackPiecesHtml .= "<div id='" . $piece_id . "' class='eaten black-eaten-piece'>" . $piece_id . "</div>";
                    break;
            }
        }

        return new JsonResponse([
            'white' => $whitePiecesHtml,
            'black' => $blackPiecesHtml
        ]);
    }


    /**
     * @Route("/getEatenPeaces", name="get_eaten_pieces")
     */
    function getEatenPieces()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $whitePiecesHtml = "";
        $blackPiecesHtml = "";
        $eatenPieces = $entityManager->getRepository('App\Entity\Bitboard')->findBy(['pieceDeleted' => true]);

        foreach ($eatenPieces as $bitboard) {
            $piece_id = $bitboard->getPiece()->getId();
            switch ($bitboard->getColor()) {
                case 'white':
                    $whitePiecesHtml .= "<div id='" . $piece_id . "' class='eaten white-eaten-piece'>" . $piece_id . "</div>";
                    break;
                case 'black':
                    $blackPiecesHtml .= "<div id='" . $piece_id . "' class='eaten black-eaten-piece'>" . $piece_id . "</div>";
                    break;
            }
        }
        return [
            'white' => $whitePiecesHtml,
            'black' => $blackPiecesHtml
        ];
    }


    /**
     * @Route("/getAmountOfEatenPieces", name="get_amount_of_eaten_pieces")
     */
    function getAmountOfEatenPieces()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $whitePiecesArray = [];
        $blackPiecesArray = [];
        $eatenPieces = $entityManager->getRepository('App\Entity\Bitboard')->findBy(['pieceDeleted' => true]);

        foreach ($eatenPieces as $bitboard) {
            $piece_id = $bitboard->getPiece()->getId();
            switch ($bitboard->getPiece()->getColor()) {
                case 'white':
                    array_push($whitePiecesArray, $piece_id);
                    break;
                case 'black':
                    array_push($blackPiecesArray, $piece_id);
                    break;
            }
        }
        return new JsonResponse([
            'white' => count($whitePiecesArray),
            'black' => count($blackPiecesArray),
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
        $validMove = false;
        $colorTurn = "";


        //Get Piece
        $piece = $entityManager->getRepository('App:Piece')->find($id_piece);

        $result = $this->validateTurn($piece);

        if ($result['validMove']) {
            $resultado = $this->getPiecePossibleMoves($piece);

            return new JsonResponse([
                'possibleMovesArray' => $resultado,
                'validMove' => $result['validMove'],
                'colorTurn' => ucfirst($result['colorTurn'])
            ]);
        } else {

            return new JsonResponse([
                'possibleMovesArray' => [],
                'validMove' => $result['validMove'],
                'colorTurn' => ucfirst($result['colorTurn'])
            ]);
        }
    }


    public function validateTurn($piece, $inverseColor = false)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $history = $entityManager->getRepository('App:History')->findOneBy(
            [],
            ['date' => 'DESC']);

        if ($inverseColor) {
            $color = $piece->getColor() == 'white' ? 'black' : 'white';
        } else {
            $color = $piece->getColor();
        }


        if ($history == null && $color == 'white') {
            $validMove = true;
            $colorTurn = "white";
        } else if ($history == null && $color == 'black') {
            $validMove = false;
            $colorTurn = "white";
        } else if ($history->getPiece()->getColor() == $color) {
            $validMove = false;
            $colorTurn = $color == 'black' ? 'black' : 'white';
        } else {

            $validMove = true;
            $colorTurn = $color;
        }


        return [
            'validMove' => $validMove,
            'colorTurn' => $colorTurn
        ];
    }


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

        $history = new History();
        $history->setCellTo($row_to . $col_to);
        $history->setPiece($piece);
        $now = new \DateTime(date('Y-m-d H:i:s', time()));
        $history->setDate($now);
        $entityManager->persist($history);
        $entityManager->flush($history);

        if ($eat == "true") {
            $victimBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy([
                'name' => 'current_position',
                'row' => $row_to,
                'col' => $col_to,
            ]);
            $victimBitboard->setPieceDeleted(true);
            $victimBitboard->setColor($piece->getColor());
            $victimPiece = $victimBitboard->getPiece()->setColor($piece->getColor());
            $entityManager->persist($victimBitboard);
            $entityManager->persist($victimPiece);
            $entityManager->flush();
            $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to);

        } else {
            $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to);
        }

        $jaqueSituation = $this->jaqueCheck($piece);
        $piece->getColor() === 'white' ? $colorTurn = 'Black' : $colorTurn = 'White';

        return new JsonResponse([
            'colorTurn' => $colorTurn,
            'jaqueSituation' => $jaqueSituation
        ]);
    }


    public function makeMoveHere($id_piece, $row_to, $col_to)
    {
        $entityManager = $this->getDoctrine()->getManager();

        //Get Piece
        $piece = $entityManager->getRepository('App:Piece')->find($id_piece);

        $victimBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'name' => 'current_position',
            'row' => $row_to,
            'col' => $col_to,
        ]);

        $victimBitboard->setPieceDeleted(true);
        $entityManager->persist($victimBitboard);
        $entityManager->flush();

        $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to);

        return new JsonResponse();
    }


    public function undoMove($id_piece, $row_undo_eat, $col_undo_eat, $victimBitboard)
    {
        $entityManager = $this->getDoctrine()->getManager();

        //Get Piece
        $piece = $entityManager->getRepository('App:Piece')->find($id_piece);

        $victimBitboard->setPieceDeleted(false);
        $entityManager->persist($victimBitboard);
        $entityManager->flush();

        $this->generateAllBitBoardsAfterPieceMove($piece, $row_undo_eat, $col_undo_eat);

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

        $jaqueSituation = $this->jaqueCheck($piece);

        return new JsonResponse([
            'jaqueSituation' => $jaqueSituation,
            'pieceName' => $piece->getName()
        ]);
    }


    /**
     * addPieceBack
     * @Route("/addPieceBack", name="add_piece_back")
     */
    public function addPieceBack()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        $id_piece = $request->get('id_piece');
        $row_to = $request->get('row_to');
        $col_to = $request->get('col_to');

        $piece = $entityManager->getRepository('App\Entity\Piece')->find($id_piece);

        $result = $this->validateTurn($piece, false);

        if ($result['validMove']) {

            $history = new History();
            $history->setCellTo($row_to . $col_to);
            $history->setPiece($piece);
            $now = new \DateTime(date('Y-m-d H:i:s', time()));
            $history->setDate($now);

            $entityManager->persist($history);
            $entityManager->flush();

            $this->generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to, true);

            $jaqueSituation = $this->jaqueCheck($piece, true);
            $piece->getColor() === 'white' ? $colorTurn = 'Black' : $colorTurn = 'White';

            return new JsonResponse([
                'validMove' => $result['validMove'],
                'colorTurn' => ucfirst($result['colorTurn']),
                'jaqueSituation' => $jaqueSituation,
            ]);

        } else {
            return new JsonResponse([
                'validMove' => $result['validMove'],
                'colorTurn' => ucfirst($result['colorTurn']),
            ]);
        }

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

        $q = $entityManager->createQuery('delete from App\Entity\History where 1=1');
        $numDeleted = $q->execute();

        foreach ($curretPositionBitboars as $bitboard) {
            $bitboard->setPieceDeleted(false);
            $entityManager->persist($bitboard);
        }
        $entityManager->flush();

        foreach ($pieces as $piece) {
            $piece->setPromoted(false);
            $originalColor = $piece->getOriginalColor();
            $piece->setColor($originalColor);
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


    function getKingBitboardByPieceColor($piece, $addPiece = false)
    {
        $entityManager = $this->getDoctrine()->getManager();
        //Get Own Pieces BitBoard

        if ($addPiece) {
            switch ($piece->getColor()) {
                case 'white':
                    $kingBitboard = $entityManager->getRepository('App:Bitboard')->getKing('white')->getQuery()->execute()[0];
                    break;
                case'black':
                    $kingBitboard = $entityManager->getRepository('App:Bitboard')->getKing('black')->getQuery()->execute()[0];
                    break;
            }
        } else {
            switch ($piece->getColor()) {
                case 'white':
                    $kingBitboard = $entityManager->getRepository('App:Bitboard')->getKing('black')->getQuery()->execute()[0];
                    break;
                case'black':
                    $kingBitboard = $entityManager->getRepository('App:Bitboard')->getKing('white')->getQuery()->execute()[0];
                    break;
            }
        }

        return $kingBitboard;
    }


    /**
     * Get the actual piece, create the atack board of the piece, and check if the king it's being atack by it.
     */
    public function jaqueCheck($piece, $addPiece = false)
    {
        $checkmate = $this->calculateMate($piece, $addPiece);

        if ($addPiece) {
            $color = $piece->getColor() == 'black' ? 'black' : 'white';
        } else {
            $color = $piece->getColor() == 'black' ? 'white' : 'black';
        }


        return [
            'color' => $color,
            'jaque' => $checkmate['jaque'],
            'checkmate' => $checkmate['mate'],
        ];

    }


    public function calculateMate($piece, $addPiece = false)
    {
        $kingBitboard = $this->getKingBitboardByPieceColor($piece, $addPiece);
        $king = $kingBitboard->getPiece();

        $possibleKingMoves = $this->mergeEatAndCleanCoordsNormal($this->getPiecePossibleMoves($king), $kingBitboard->getRow(), $kingBitboard->getCol());

        if ($addPiece) {
            $color = $piece->getColor() === 'white' ? 'black' : 'white';
        } else {
            $color = $piece->getColor();
        }

        $allPossibleAtacksCoordsOfTheEnemy = $this->getAtackBoardByTeam($color);
        $allPossibleAtacksCoordsOfTheEnemyMerged = [];

        //I get all the possible atacks of the enemy
        foreach ($allPossibleAtacksCoordsOfTheEnemy as $array) {
            $result = [
                'piece_id' => $array['piece_id'],
                'coords' => $this->mergeEatAndCleanCoordsResultArray($array)
            ];
            array_push($allPossibleAtacksCoordsOfTheEnemyMerged, $result);
        }

        $piecesAtackingTheKing = $this->getPiecesThatAtackKingPossibleMovePositions($possibleKingMoves, $allPossibleAtacksCoordsOfTheEnemyMerged);
        $result = $this->switchMateCases($piecesAtackingTheKing, $piece);

        return $result;
    }


    public function calculateMatePartial($piece)
    {
        $kingBitboard = $this->getKingBitboardByPieceColor($piece);
        $king = $kingBitboard->getPiece();
        $possibleKingMoves = $this->mergeEatAndCleanCoordsNormal($this->getPiecePossibleMoves($king), $kingBitboard->getRow(), $kingBitboard->getCol());

        $allPossibleAtacksCoordsOfTheEnemy = $this->getAtackBoardByTeam($piece->getColor());
        $allPossibleAtacksCoordsOfTheEnemyMerged = [];

        //I get all the possible atacks of the enemy
        foreach ($allPossibleAtacksCoordsOfTheEnemy as $array) {
            $result = [
                'piece_id' => $array['piece_id'],
                'coords' => $this->mergeEatAndCleanCoordsResultArray($array)
            ];
            array_push($allPossibleAtacksCoordsOfTheEnemyMerged, $result);
        }

        $piecesAtackingTheKing = $this->getPiecesThatAtackKingPossibleMovePositions($possibleKingMoves, $allPossibleAtacksCoordsOfTheEnemyMerged);

        return $piecesAtackingTheKing;
    }


    function switchMateCases($piecesAtackingTheKing, $piece_actual)
    {
        $mate = false;
        $jaque = false;
        $entityManager = $this->getDoctrine()->getManager();
        $canIEatThatDangerousPieces = [];

        //Free spaces that are not under atack and the king can move.
        $freeMoves = $piecesAtackingTheKing['freeMoves'];

        //Moves that the king can do, but they are under attak by other pieces.
        $underAtack = $piecesAtackingTheKing['underAtack'];

        if (count($underAtack) > 0) {
            $jaque = true;
        }

        //If the king have free moves, that are not under atack, there is no checkmate
        if (count($freeMoves) > 0) {
            $mate = false;
        } else {
            //If the king have no free moves, we will simulate what happens if we eat some of the pieces
            // that are avoiding the king to move
            if (count($underAtack) > 0) {
                $jaque = true;
                foreach ($underAtack as $atacker) {
                    $atacker_id = $atacker['piece_id'];
                    //Can I eat that piece?????
                    $canBeEatenByMe = $this->checkIfIcanEatAPiece($atacker_id);
                    $break = false;

                    //If I can eat that piece, let's simulate what happens if I do so, one by one.
                    if (count($canBeEatenByMe) > 0) {
                        foreach ($canBeEatenByMe as $move) {
                            $piece_id = $move['piece_id'];
                            $coordMoveTo = $move['coord'];
                            $break = false;
                            $piece = $entityManager->getRepository('App:Piece')->find($piece_id);

                            $pieceCurrentBitBoardBeforeMove = $entityManager->getRepository('App:Bitboard')->findOneBy([
                                'piece' => $piece,
                                'name' => 'current_position'
                            ]);

                            $victimBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy([
                                'name' => 'current_position',
                                'row' => $coordMoveTo[0],
                                'col' => $coordMoveTo[1],
                            ]);

                            $coordMoveFrom = [$pieceCurrentBitBoardBeforeMove->getRow(), $pieceCurrentBitBoardBeforeMove->getCol()];
                            $this->makeMoveHere($piece_id, $coordMoveTo[0], $coordMoveTo[1]);
                            $result = $this->calculateMatePartial($piece_actual);

                            if (count($result['freeMoves']) > 0) {
                                $mate = false;
                                $break = true;
                            }

                            $this->undoMove($piece_id, $coordMoveFrom[0], $coordMoveFrom[1], $victimBitboard);

                            if ($break) {
                                break;
                            }
                        }
                    }
                }
                $break ? $mate = false : $mate = true;
            }

        }

        return [
            'jaque' => $jaque,
            'mate' => $mate
        ];
    }


    function checkIfIcanEatAPiece($piece_id)
    {
        $canBeEaten = false;
        $entityManager = $this->getDoctrine()->getManager();
        $piece = $entityManager->getRepository('App:Piece')->find($piece_id);

        $bitBoardCurrentPiecePosition = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'name' => 'current_position'
        ]);
        $pieceToEatCoord = [$bitBoardCurrentPiecePosition->getRow(), $bitBoardCurrentPiecePosition->getCol()];

        $color = $piece->getColor() == 'black' ? 'white' : 'black';
        $allPossibleAtacksCoordsOfTheEnemy = $this->getAtackBoardByTeam($color);
        $allPossibleAtacksCoordsOfTheEnemyMerged = [];

        //I get all the possible atacks of the enemy
        foreach ($allPossibleAtacksCoordsOfTheEnemy as $array) {
            $atackingPiece = $array['piece_id'];
            foreach ($array['result']['eat'] as $eatCoord) {
                if ($eatCoord === $pieceToEatCoord) {
                    array_push($allPossibleAtacksCoordsOfTheEnemyMerged, ['piece_id' => $atackingPiece, 'coord' => $eatCoord]);
                }
            }
        }

        return $allPossibleAtacksCoordsOfTheEnemyMerged;
    }


    function getPiecesThatAtackKingPossibleMovePositions($kingMoves, $enemyMoves)
    {
        $result = [];
        $freeMove = [];

        foreach ($kingMoves as $kingMove) {
            $flag = false;
            foreach ($enemyMoves as $enemyMove) {
                foreach ($enemyMove['coords'] as $coord) {
                    if ($kingMove == $coord) {
                        $flag = true;
                        array_push($result, ['piece_id' => $enemyMove['piece_id'], 'coord' => $coord]);
                    }
                }
            }
            $flag == false ? array_push($freeMove, $kingMove) : "";
        }

        return ['underAtack' => $result, 'freeMoves' => $freeMove];
    }


    function mergeEatAndCleanCoordsResultArray($array)
    {
        $result = [];
        $clear = $array['result']['clear'];
        $eat = $array['result']['eat'];

        foreach ($clear as $cl) {
            array_push($result, $cl);
        }

        foreach ($eat as $ea) {
            array_push($result, $ea);
        }

        return $result;
    }

    function mergeEatAndCleanCoordsNormal($array, $row_actual, $col_actual)
    {
        $result = [];
        $clear = $array['clear'];
        $eat = $array['eat'];

        foreach ($clear as $cl) {
            array_push($result, $cl);
        }

        foreach ($eat as $ea) {
            array_push($result, $ea);
        }


        return $result;
    }

    function getAtackBoardByTeam($color)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $coords = [];
        $pieces = [];

        //Get Own Pieces BitBoard
        switch ($color) {
            case 'black':
                $pieces = $entityManager->getRepository('App:Piece')->findBy(['color' => $color]);
                $ownPiecesBitBoard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);
                $enemyPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);
                break;
            case'white':
                $pieces = $entityManager->getRepository('App:Piece')->findBy(['color' => $color]);
                $ownPiecesBitBoard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_white_pieces']);
                $enemyPiecesBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(['name' => 'all_black_pieces']);
                break;
        }

        $own = $this->fromBitboardToCoordinatesArray(str_split($ownPiecesBitBoard->getBitboard()), 9, 9);
        $enemy = $this->fromBitboardToCoordinatesArray(str_split($enemyPiecesBitboard->getBitboard()), 9, 9);

        foreach ($pieces as $piece) {
            $bitBoardCurrentPiecePosition = $entityManager->getRepository('App:Bitboard')->findOneBy([
                'piece' => $piece,
                'name' => 'current_position'
            ]);

            $baseMatrix = $this->matrixCreateWithoutModel(9, 9);
            $resultado = $this->getPieceVectorCoordinatesArrayToOwnPiece($baseMatrix, $bitBoardCurrentPiecePosition->getRow(), $bitBoardCurrentPiecePosition->getCol(), $own, $enemy, $piece);

            array_push($coords, [
                    'piece_id' => $piece->getId(),
                    'result' => $resultado
                ]
            );
        }

        return $coords;
    }


    function getPiecePossibleMoves($piece)
    {
        $entityManager = $this->getDoctrine()->getManager();

        //Get Current Piece Posion Bitboard
        $bitBoardCurrentPiecePosition = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'name' => 'current_position'
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

        $own = $this->fromBitboardToCoordinatesArray(str_split($ownPiecesBitBoard->getBitboard()), 9, 9);
        $enemy = $this->fromBitboardToCoordinatesArray(str_split($enemyPiecesBitboard->getBitboard()), 9, 9);

        $baseMatrix = $this->matrixCreateWithoutModel(9, 9);
        $resultado = $this->getPieceVectorCoordinatesArrayToOwnPiece($baseMatrix, $bitBoardCurrentPiecePosition->getRow(), $bitBoardCurrentPiecePosition->getCol(), $own, $enemy, $piece);

        return $resultado;

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
        return $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
    }

    //Doesn't matter if its  Black or White side
    public function bishopOverOtherPieces($matrixArray, $y, $x, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayPieceMoves = [];
        switch ($promoted) {
            case false:
                $matrixDiagonalP = $this->mainDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces);
                $matrixDiagonalS = $this->secondaryDiagonal($y, $x, 9, 9, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayPieceMoves = array_merge($matrixDiagonalP, $matrixDiagonalS);
                break;
            case true:
                $matrixDiagonalP = $this->mainDiagonal($y, $x, $arrayOwnPieces, $arrayEnemyPieces);
                $matrixDiagonalS = $this->secondaryDiagonal($y, $x, 9, 9, $arrayOwnPieces, $arrayEnemyPieces);

                $pieceMovementCoordinates = [
                    [$y - 1, $x],
                    [$y, $x + 1],
                    [$y + 1, $x],
                    [$y, $x - 1],
                ];
                $arrayPieceMoves = array_merge($matrixDiagonalP, $matrixDiagonalS, $pieceMovementCoordinates);
                break;
        }
        return $this->getCleanAndAtackCoorinates($matrixArray, $arrayPieceMoves, $arrayOwnPieces, $arrayEnemyPieces);
    }


    // Does matter side
    public function lanceOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {
            $pieceMovementCoordinates = $this->colForward($y, $x, 9, $color, $arrayOwnPieces, $arrayEnemyPieces);
            return $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
        }
        return $result;
    }

    // Does matter side
    public function pawnOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $result = null;
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
            $result = $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
        }
        return $result;
    }

    // Does matter side non promotional
    public function goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces)
    {
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
        return $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
    }


    // Does matter side
    public function knightOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {
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
            $result = $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
        }
        return $result;
    }


    // Does matter side
    public function silverGeneralOverOtherPieces($matrixArray, $y, $x, $color, $promoted, $arrayOwnPieces, $arrayEnemyPieces)
    {
        if ($promoted == true) {
            $result = $this->goldGeneralOverOtherPieces($matrixArray, $y, $x, $color, $arrayOwnPieces, $arrayEnemyPieces);
        } else {

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

            return $this->getCleanAndAtackCoorinates($matrixArray, $pieceMovementCoordinates, $arrayOwnPieces, $arrayEnemyPieces);
        }
        return $result;
    }


    //Doesn't matter if its  Black or White side
    public function rookOverOtherPieces($matrixArray, $y, $x, $size, $color, $promoted = false, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayPieceMoves = [];

        switch ($promoted) {
            case false:
                $arrayRow = $this->row($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayCol = $this->col($y, $x, $color, $size, $arrayOwnPieces, $arrayEnemyPieces);
                $arrayPieceMoves = array_merge($arrayRow, $arrayCol);
                break;
            case true:
                $arrayRow = $this->row($y, $x, $size, $arrayOwnPieces, $arrayEnemyPieces);
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
        return $this->getCleanAndAtackCoorinates($matrixArray, $arrayPieceMoves, $arrayOwnPieces, $arrayEnemyPieces);
    }


    public function getCleanAndAtackCoorinates($matrixArray, $arrayPieceMoves, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $arrayCoordinatesCanEat = [];
        $arrayCoordinatesClean = [];

        foreach ($arrayPieceMoves as $coordinate) {
            if (isset($matrixArray[$coordinate[0]][$coordinate[1]]) && !array_search([$coordinate[0], $coordinate[1]], $arrayOwnPieces)) {
                $belongToEnemyCoordinates = array_search([$coordinate[0], $coordinate[1]], $arrayEnemyPieces);
                if ($belongToEnemyCoordinates !== false) {
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
        $arrayCoordinates = [];
        $x2 = $x;
        $y2 = $y;

        for ($i = $y - 1, $j = $x + 1; $i >= 0; $i--, $j++) {
            $res = $this->pushArrayCoordinates($i, $j, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }

        for ($i2 = $y2 + 1, $j2 = $x2 - 1; $j2 >= 0; $i2++, $j2--) {
            $res = $this->pushArrayCoordinates($i2, $j2, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
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
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }
        for ($i2 = $y2 + 1, $j2 = $x2 + 1; $i2 <= $row || $j2 <= $col; $i2++, $j2++) {
            $res = $this->pushArrayCoordinates($i2, $j2, $arrayOwnPieces, $arrayEnemyPieces);
            if (!$res['sigo']) {
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
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
                        if ($res['enemy'] == true) {
                            array_push($arrayCoordinates, $res['coord']);
                            break;
                        } else {
                            break;
                        }
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
            case('black'):
                for ($i = $y - 1; $i >= 0; $i--) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        if ($res['enemy'] == true) {
                            array_push($arrayCoordinates, $res['coord']);
                            break;
                        } else {
                            break;
                        }
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
                        if ($res['enemy'] == true) {
                            array_push($arrayCoordinates, $res['coord']);
                            break;
                        } else {
                            break;
                        }
                    } else {
                        array_push($arrayCoordinates, $res['coord']);
                    }
                }
                break;
            case('black'):
                for ($i = $y + 1; $i <= $size; $i++) {
                    $res = $this->pushArrayCoordinates($i, $x, $arrayOwnPieces, $arrayEnemyPieces);
                    if (!$res['sigo']) {
                        if ($res['enemy'] == true) {
                            array_push($arrayCoordinates, $res['coord']);
                            break;
                        } else {
                            break;
                        }
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
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
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
                if ($res['enemy'] == true) {
                    array_push($arrayCoordinates, $res['coord']);
                    break;
                } else {
                    break;
                }
            } else {
                array_push($arrayCoordinates, $res['coord']);
            }
        }
        return $arrayCoordinates;
    }


    public function pushArrayCoordinates($y, $x, $arrayOwnPieces, $arrayEnemyPieces)
    {
        $coord = null;
        $enemy = false;

        if (array_search([$y, $x], $arrayOwnPieces) !== false) {
            $sigo = false;
            $enemy = false;
        } elseif (array_search([$y, $x], $arrayEnemyPieces) !== false) {
            $coord = [$y, $x];
            $enemy = true;
            $sigo = false;
        } else {
            $coord = [$y, $x];
            $sigo = true;
        }

        return [
            'sigo' => $sigo,
            'coord' => $coord,
            'enemy' => $enemy
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
        $matrixHtml .= "<table id='move_to_table'>";

        $history = $entityManager->getRepository('App:History')->findOneBy(
            [],
            ['date' => 'DESC']);

        if ($history) {
            //$mateResult = $this->calculateMate($history->getPiece());
            $mateResult['underAtack'] = [];
            $mateResult['freeMoves'] = [];
        } else {
            $mateResult['underAtack'] = [];
            $mateResult['freeMoves'] = [];
        }

        for ($i = 0; $i < $row; $i++) {
            $matrixHtml .= "<tr>";
            for ($j = 0; $j < $col; $j++) {
                $pieceCurrentPositionBitboard = $entityManager->getRepository('App:Bitboard')->findOneBy(
                    [
                        'name' => 'current_position',
                        'row' => $i,
                        'col' => $j,
                        'pieceDeleted' => false
                    ]);
                $piece_first_letter = isset($pieceCurrentPositionBitboard) ? substr($pieceCurrentPositionBitboard->getPiece()->getName(), 0, 1) : "";


                $piece_id = isset($pieceCurrentPositionBitboard) ? $pieceCurrentPositionBitboard->getPiece()->getId() : null;
                $piece_color = isset($pieceCurrentPositionBitboard) ? $pieceCurrentPositionBitboard->getPiece()->getColor() : null;
                $promoted = 'nopromoted';

                $underAtack = "";
                $freeMove = "";
                if (array_search([$i, $j], $mateResult['underAtack']) !== false) {
                    $underAtack = 'under-atack';
                }
                if (array_search([$i, $j], $mateResult['freeMoves']) !== false) {
                    $freeMove = 'free-move';
                }

                if (isset($pieceCurrentPositionBitboard) && $pieceCurrentPositionBitboard->getPiece()->getPromoted()) {
                    $promoted = 'promoted';
                    $piece_first_letter = "+" . $piece_first_letter;
                }

                $matrixHtml .= "<td data-prom='" . $promoted . "' data-row='" . $i . "' data-col='" . $j . "' data-piece='" . $piece_id . "' id='" . $i . $j . "'  class='center cell " . $piece_color . " " . $promoted . " " . $underAtack . " " . $freeMove . "'>" . $piece_first_letter . "</td>";
            }
            $matrixHtml .= "</tr>";
        }
        $matrixHtml .= "</table>";

        $eatenPieces = $this->getEatenPieces();

        return [
            'board' => $matrixHtml,
            'eatenPieces' => $eatenPieces
        ];
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

    //results for array1 (when it is in more, it is in array1 and not in array2. same for less)

    /**
     *
     * Values in array1 not in array2 (more)
     * Values in array2 not in array1 (less)
     * Values in array1 and in array2 but different (diff)
     *
     **/

    function compare_multi_Arrays($array1, $array2)
    {
        $result = array("more" => array(), "less" => array(), "diff" => array());
        foreach ($array1 as $k => $v) {
            if (is_array($v) && isset($array2[$k]) && is_array($array2[$k])) {
                $sub_result = $this->compare_multi_Arrays($v, $array2[$k]);
                //merge results
                foreach (array_keys($sub_result) as $key) {
                    if (!empty($sub_result[$key])) {
                        $result[$key] = array_merge_recursive($result[$key], array($k => $sub_result[$key]));
                    }
                }
            } else {
                if (isset($array2[$k])) {
                    if ($v !== $array2[$k]) {
                        $result["diff"][$k] = array("from" => $v, "to" => $array2[$k]);
                    }
                } else {
                    $result["more"][$k] = $v;
                }
            }
        }
        foreach ($array2 as $k => $v) {
            if (!isset($array1[$k])) {
                $result["less"][$k] = $v;
            }
        }
        return $result;
    }


    /**
     *
     * Update all neede Db's after a move have being made
     *
     */
    public function generateAllBitBoardsAfterPieceMove($piece, $row_to, $col_to, $insert = false)
    {

        //I change the  piece "current_position_bitboard"
        $this->generateBitBoardInitialPositionPerPiece($piece, $row_to, $col_to, $insert);

        //Generate a BitBoard with all the pieces in the current position
        $this->generateAllPiecesPositionBitBoardByCurrentPositionBitboards();

        //Generate a BitBoard with all the WHITE pieces in the current position
        $this->generateWhitePiecesPositionBitBoardByCurrentPositionBitboards();

        //Generate a BitBoard with all the BLACK pieces in the current position
        $this->generateBlackPiecesPositionBitBoardByCurrentPositionBitboards();


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

            $entityManager->flush();

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

            $entityManager->flush();

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

        }
        $entityManager->flush();

    }


    /**
     * Generate all current_position bitboars by pieces inital position
     */
    public function generateBitBoardInitialPositionPerPiece($piece, $row, $col, $insert = false)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $bitBoardPieceActualPosition = new Bitboard();
        $matrix = $this->matrixCreateWithoutModel(9, 9);

        $checkIfAlreadyExiste = $entityManager->getRepository('App:Bitboard')->findOneBy([
            'piece' => $piece,
            'name' => 'current_position',
        ]);

        if ($insert == true) {
            $checkIfAlreadyExiste->setPieceDeleted(false);
            $entityManager->persist($checkIfAlreadyExiste);
            $entityManager->flush();
        }

        //Si yá existe, lo actualizo  sino creo uno nuevo.
        if ($checkIfAlreadyExiste && !$checkIfAlreadyExiste->getPieceDeleted()) {
            $matrix[$row][$col] = 1;

            $checkIfAlreadyExiste->setRow($row);
            $checkIfAlreadyExiste->setCol($col);
            $checkIfAlreadyExiste->setName("current_position");
            $insert == true ? "" : $checkIfAlreadyExiste->setColor($piece->getColor());
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
            $insert == true ? "" : $bitBoardPieceActualPosition->setColor($piece->getColor());
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

            $entityManager->remove($checkIfAlreadyExiste);
            $entityManager->flush();

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


        }
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
                    isset($matrixArray[$y + 1][$x]) ? $matrixArray[$y + 1][$x] = 1 : null;
                    break;
                case('black'):
                    isset($matrixArray[$y - 1][$x]) ? $matrixArray[$y - 1][$x] = 1 : null;
                    break;
            }
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
