<?php
/**
 *
 */
 require_once "Peca.php";
class Game
{
  static function meGames($value='')
  {
    global $dbs;
    $id = User::id();
    $retorno = $dbs->tableSelect("ks_jogo","WHERE (player1 = '$id' or player2 = '$id') and status = 'desafiando'");
    return $retorno;
  }
  static function desafiar($id){
    global $dbs;
    require_once 'gameNewArr.php';
    $tabuleiroJson = json_encode($gameNewArr,JSON_FORCE_OBJECT);
    $newGameId = $dbs->TableInsert("ks_jogo", array(
        "null",
        $tabuleiroJson,
        User::id(),
        "Você é o Player 1. Peças pretas. Aguarde o movimeto das brancas.",
        $id,
        "Faça seu movimeto. Você é o Player2, Paças brancas.",
        "Jogo Desafiado",
        "desafiando",
        $id
    ));
    return $newGameId;
  }
  static function playersName($id){
    global $dbs;
    $gameTable = $dbs->tableSelect("ks_jogo","WHERE id = '$id'")[0];

    $retorno[1] = User::username($gameTable["player1"]);
    $retorno[2] = User::username($gameTable["player2"]);
    return $retorno;
  }
  static function inTable($id){
    global $dbs;
    $gameTable = $dbs->tableSelect("ks_jogo","WHERE id = '$id'")[0];
    return $gameTable;
  }
  static function tabuleiro($id){
    $gameTable = Game::inTable($id);
    $retorno["id"] = $id;
    $retorno["pecas"] = json_decode($gameTable["tabuleiro"]);
    $retorno["cor"] = Game::meCor($id);
    $retorno["elevar"] = "false";
    foreach ($retorno["pecas"] as $key => $value) {
      //echo "[$cor,".Peca::nameForY($value->posicao).",{$value->tipo}]";
      if($retorno["cor"]=="preto" && Peca::nameForY($value->posicao)==1 && $value->tipo==7){
          $retorno["elevar"] = $key;
      }
      if($retorno["cor"]=="branco" && Peca::nameForY($value->posicao)==8 && $value->tipo==1){
          $retorno["elevar"] = $key;
      }

      if($value->tipo==6) $reiBranco = true;
      if($value->tipo==12)  $reiPreto = true;
    }
    if(!isset($reiBranco)) Game::gameOver($id,$gameTable["player1"]);
    if(!isset($reiPreto)) Game::gameOver($id,$gameTable["player2"]);

    $retorno["status"] = $gameTable["status"];
    //echo $gameTable["tabuleiro"]."\n";

    //-------
    if ($gameTable["player1"]==User::id()) {
      $retorno["msg"] = $gameTable["player1msg"];
    }
    else {$retorno["msg"] = $gameTable["player2msg"];
    }
    //-----
    if ($gameTable["vez"]==User::id()) {
      $retorno["vez"] = "minha";
    }
    else {$retorno["vez"] = "outro";
    }


    //----------- checkmat
    $retorno["checkmat"] = "true";
    return $retorno;

  }
  static function peca($jogoId,$peca){
    $tabuleiro = Game::tabuleiro($jogoId);
    if($tabuleiro["vez"] == "minha"){
      require_once "peca.php";
      for ($p=1; $p <= 12; $p++) {
        require_once "peca$p.php";
      }
      $tipo = "peca".$tabuleiro["pecas"]->$peca->tipo;
      $pecaObj = new $tipo($tabuleiro, $peca);
      if ($pecaObj::me_color == Game::meCor($jogoId)) {
        return $pecaObj;
      }
    }
    else {
      //colocar mensagem para o usuario
    }
  }
  static function move($gameId, $peca, $destino){
    $tabuleiroObj = Game::tabuleiro($gameId);
    $tabuleiroObj["pecas"]->$peca->posicao = $destino;
    $tabuleiroObj["pecas"]->$peca->situacao = "movido";
    $tabuleiroJson = json_encode($tabuleiroObj["pecas"], JSON_FORCE_OBJECT);
    global $dbs;
    $dbs->TableUpdateOne(
      "ks_jogo",
      "tabuleiro",
      $tabuleiroJson,
      "WHERE id = '$gameId'"
    );
    Game::passarVez($gameId);
  }
  static function passarVez($gameId){
    $game = Game::inTable($gameId);
    if ($game["vez"] == User::id()) {
      global $dbs;
      if (User::id() == $game["player1"]) {
        $vez = $game["player2"];
      }
      else {
        $vez = $game["player1"];
      }
      $dbs->tableUpdateOne(
        "ks_jogo",
        "vez",
        $vez,
        "WHERE  id = '$gameId'"
      );
    }
  }
  static function meCor($gameeId){
    $game = Game::inTable($gameeId);
    if ($game["player1"] == User::id()) {
      $color = "preto";
    }
    elseif ($game["player2"] == User::id()) {
      $color = "branco";
    }
    else {
      $color = "other";
    }

    return $color;
  }
  static function msgSet($gameId,$numPlayer,$msg){
    global $dbs;
    $dbs->tableUpdateOne(
      "ks_jogo",
      "player{$numPlayer}msg",
      "$msg",
      "WHERE id = '$gameId'"
    );
  }
  static function captura($game,$pecaNum,$destino)
  {
    global $dbs;
    $tabuleiro = Game::tabuleiro($game);
    foreach ($tabuleiro["pecas"] as $key => $value) {
      if($value->posicao == $destino) continue;
      if($key == $pecaNum){
        $value->posicao = $destino;
      }
      $newPecas[$key] = $value;
    }
    $tabuleiroJson = json_encode($newPecas,JSON_FORCE_OBJECT);
    $dbs->tableUpdateOne(
      "ks_jogo",
      "tabuleiro",
      $tabuleiroJson,
      "WHERE id = '$game'"
    );
    Game::passarVez($game);
  }
  static function elevar($gameId, $peca, $tipo){
    $tabuleiroObj = Game::tabuleiro($gameId);
    $tabuleiroObj["pecas"]->$peca->tipo = $tipo;
    $tabuleiroJson = json_encode($tabuleiroObj["pecas"], JSON_FORCE_OBJECT);
    global $dbs;

    $dbs->TableUpdateOne(
      "ks_jogo",
      "tabuleiro",
      $tabuleiroJson,
      "WHERE id = '$gameId'"
    );
  }
  static function gameOver($gameId,$ganhador){
    global $dbs;
    $dbs->TableUpdateOne(
      "ks_jogo",
      "status",
      $ganhador,
      "WHERE id = '$gameId'"
    );
  }
}
