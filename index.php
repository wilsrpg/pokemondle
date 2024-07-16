<?php
//header('Access-Control-Allow-Origin: *');
//date_default_timezone_set('America/Sao_Paulo');
//echo session_id();
require 'vendor/autoload.php';
session_start();
//if(isset($_SESSION['mensagem']))
//  echo $_SESSION['mensagem'];
//unset($_SESSION);
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

Pokédle+<br>

<form action="pokedle.php" method="POST">
  <input type="submit" name="continuar" <?php if (empty($_SESSION['seed'])) echo 'disabled'?> value="Continuar jogo anterior">
</form>

<form action="pokedle.php" method="POST">
  Selecione as gerações para jogar:<br>
  <input name="geracoes" autofocus
    title="As gerações que serão válidas na partida. Apenas números de 1 a 9, separados por vírgula."
  ><br>
  Geração do contexto:<br>
  <select name="geracao_contexto"
    title="A geração que vai determinar os tipos e evoluções. Não pode ser menor que a maior geração escolhida."
  >
    <option value="" title="Seleciona automaticamente a maior das gerações digitadas acima.">Auto</option>
    <?php
    $jogos = ['RBY', 'GSC', 'RSE/FRLG', 'DPPt/HGSS', 'BW', 'XY/ORAS', 'SM/PE', 'SWSH/BDSP', 'SV'];
    //$regiao = ['Kanto', 'Johto', 'Hoenn', 'Sinnoh', 'Unova', 'Kalos', 'Alola', 'Galar', 'Paldea'];
    for ($i=0; $i < 9; $i++)
    echo '<option value="'.($i+1).'">'.($i+1).' ('.$jogos[$i].')</option>';
  ?>
  </select><br><br>
  <input type="submit" name="novo" value="Novo jogo">
  <input type="reset" value="Limpar">
</form>
<br>

<?php
if (!empty($_SESSION['mensagem'])) {
  echo "<script>alert('{$_SESSION['mensagem']}')</script>";
  unset($_SESSION['mensagem']);
}
?>

</body>
</html>