<?php
//header('Access-Control-Allow-Origin: *');
date_default_timezone_set('America/Sao_Paulo');
//echo session_id();
require 'vendor/autoload.php';
if (session_status() === 1)
  session_start();
$seed = (int) date("Ymd");

if(isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd"))
  $_SESSION = [];

if(isset($_POST['continuar']) && isset($_SESSION['modo'])) {
  if ($_SESSION['modo'] == 'pokemon')
    header('Location: pokedle.php');
  else if ($_SESSION['modo'] == 'tecnica')
    header('Location: pokedle-moves.php');
  die();
}

if(isset($_POST['excluir']))
  $_SESSION = [];
?>

<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokédle+Gerações</title>
  </head>
<body>

Pokédle+Gerações
<br><br>
<form id="jogo" method="POST" action="pokedle.php">
  Selecione o modo de jogo:<br>
  <input type="radio" name="modo" id="pokemon" value="pokemon" onchange="atualizarModo()" checked />
  <label for="pokemon">Pokémon</label>
  <input type="radio" name="modo" id="tecnica" value="tecnica" onchange="atualizarModo()" />
  <label for="tecnica">Técnica</label>
  <br>
  Selecione as gerações para jogar:
  <label title="<?php
    echo 'As gerações de pokémons/técnicas que serão válidas no jogo. Por exemplo, se a geração escolhida for somente a'
      .' 3, nenhum pokémon das gerações anteriores poderá ser o pokémon secreto, mesmo que estejam presentes nos jogos'
      .' da 3ª geração. Da mesma forma, técnicas de gerações anteriores, como Tackle ou Charm, não estarão presentes na'
      .' partida. ';
    ?>"
  >
    ⓘ
  </label>
  <!--<br>
  <input name="geracoes" autofocus placeholder="ex.: 1,3,5"
    title="As gerações que serão válidas na partida. Apenas números de 1 a 9, separados por vírgula."
  >-->
  <?php
    for ($i=1; $i <= 9; $i++) {
      if (($i-1) % 3 == 0)
        echo '<br>';
      echo '<input type="checkbox" name="g'.$i.'" id="g'.$i.'" '.($i === 1 ? 'autofocus' : '')
        .' onclick="atualizarBotaoDeTodas()" />';
      echo '<label for="g'.$i.'">'.$i.'</label>';
    }
  ?>
  <br>
  <input type="checkbox" id="todas" onclick="alternarTodas()" />
  <label for="todas">Todas</label>
  <input type="hidden" name="geracoes" id="geracoes" />
  <br>
  Geração do contexto:
  <label title="<?php
    echo 'A geração que vai determinar os tipos e evoluções. Por exemplo, se a geração for 1, Magnemite não será'
      .' do tipo metálico, pois esse tipo ainda não existia na 1ª geração. Da mesma forma, a técnica Bite não será'
      .' do tipo noturno, será do tipo normal, como era na 1ª geração. Outro exemplo: se a geração for 2, Pikachu será'
      .' considerado evoluído, por causa de Pichu. &#13;Não pode ser menor que a maior geração escolhida.';
    ?>"
  >
    ⓘ
  </label>
  <br>
  <select name="geracao_contexto" id="select"
    title="A geração que vai determinar os tipos e evoluções. Não pode ser menor que a maior geração escolhida."
  >
    <option value="" title="Seleciona automaticamente a maior das gerações digitadas acima.">Auto</option>
    <?php
    $jogos = ['RBY', 'GSC', 'RSE/FRLG', 'DPPt/HGSS', 'BW', 'XY/ORAS', 'SM/PE', 'SWSH/BDSP', 'SV'];
    //$regiao = ['Kanto', 'Johto', 'Hoenn', 'Sinnoh', 'Unova', 'Kalos', 'Alola', 'Galar', 'Paldea'];
    for ($i=0; $i < 9; $i++)
    echo '<option value="'.($i+1).'">'.($i+1).' ('.$jogos[$i].')</option>';
  ?>
  </select>
  <br><br>
  <input type="submit" name="novo" value="Iniciar novo jogo">
  <!--<input type="submit" formaction="pokedle.php" name="novo" value="Novo jogo - Pokémon">
  <br>
  <input type="submit" formaction="pokedle-moves.php" name="novo" value="Novo jogo - Técnica">-->
</form>

<form action="index.php" method="POST">
  <input type="submit" name="continuar" <?php if (empty($_SESSION['modo'])) echo 'disabled'?> value="Continuar jogo anterior">
  <br>
  <input type="submit" name="excluir" <?php if (empty($_SESSION['modo'])) echo 'disabled'?> value="Excluir jogo atual">
</form>


<?php
if (!empty($_SESSION['mensagem'])) {
  echo '<span style="color: red;">'.$_SESSION['mensagem'].'</span>';
  //echo "<script>alert('{$_SESSION['mensagem']}')</script>";
  unset($_SESSION['mensagem']);
}
?>

</body>

<script>
  function atualizarModo() {
    if (document.getElementById('pokemon').checked)
      document.getElementById('jogo').action = 'pokedle.php';
    if (document.getElementById('tecnica').checked)
      document.getElementById('jogo').action = 'pokedle-moves.php';
  }
  
  function atualizarBotaoDeTodas() {
    for (let i=1; i <= 9; i++) {
      if (!document.getElementById('g'+i).checked) {
        document.getElementById('todas').checked = false;
        break;
      } else if (i == 9)
        document.getElementById('todas').checked = true;
    }

    let geracoes = [];
    for (let i=1; i <= 9; i++)
      if (document.getElementById('g'+i).checked)
        geracoes.push(i);
    document.getElementById('geracoes').value = geracoes.join();
  }

  function alternarTodas() {
    for (let i=1; i <= 9; i++)
      document.getElementById('g'+i).checked = document.getElementById('todas').checked;

    if (document.getElementById('todas').checked)
      document.getElementById('geracoes').value = '1,2,3,4,5,6,7,8,9';
    else
      document.getElementById('geracoes').value = '';
  }

  function iniciar() {
    if (document.getElementById('pokemon').checked)
      document.getElementById('jogo').action = 'pokedle.php';
    if (document.getElementById('tecnica').checked)
      document.getElementById('jogo').action = 'pokedle-moves.php';
    
    let geracoes = [];
    for (let i=1; i <= 9; i++)
      if (document.getElementById('g'+i).checked)
        geracoes.push(i);
    document.getElementById('geracoes').value = geracoes.join();
  }

  document.getElementById('select').addEventListener('keydown', function (e) {
    if(e.repeat)
      return;
    if(e.key == "Enter"){
      //console.log('clikei');
      document.getElementById('jogo').submit();
      //document.getElementById('jogo').click();
      //iniciar();
    }
  });
</script>

</html>