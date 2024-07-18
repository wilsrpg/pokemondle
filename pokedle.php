<?php
require 'vendor/autoload.php';
session_start();

if(isset($_POST['voltar'])) {
  header('Location: index.php');
  die();
}

//var_dump($_SESSION);
//echo '<br>';
$URL_BASE = 'http://localhost/pokedle-api/pokedle-api/v1';
//$URL_BASE = 'https://wilsrpg.42web.io/pokedle-api/pokedle-api/v1';
//$URL_BASE = 'http://wilsrpg.unaux.com/pokedle-api/v1';
//$URL_BASE = 'https://wilsrpg.x10.mx/pokedle-api/v1';
$TIMEOUT = 15;
$cookieFile = getcwd().'/cookies/cookie.txt';

$seed = 0;
$geracoes = '';
$geracao_contexto = '';
$palpites = [];
$pokemons = [];
$descobriu = false;

$palpite = '';
$erro = '';
$pokemon = '';
$nomes = [];

if (isset($_SESSION['seed']))
  $seed = $_SESSION['seed'];
if (isset($_SESSION['palpites']))
  $palpites = array_reverse($_SESSION['palpites']);
if (isset($_SESSION['pokemons']))
  $pokemons = $_SESSION['pokemons'];
if (isset($_SESSION['descobriu']))
  $descobriu = $_SESSION['descobriu'];
if (isset($_SESSION['geracoes']))
  $geracoes = $_SESSION['geracoes'];
if (isset($_SESSION['geracao_contexto']))
  $geracao_contexto = $_SESSION['geracao_contexto'];

if(isset($_POST['novo'])) {
  $geracoes = $_POST['geracoes'];
  //var_dump($_POST);exit;
  if (isset($_POST['geracao_contexto']))
    $geracao_contexto = $_POST['geracao_contexto'];
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_POST => 2,
    CURLOPT_POSTFIELDS => ['geracoes' => $geracoes, 'geracao_contexto' => $geracao_contexto],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  //var_dump($response);
  //echo '..postjogo<br>';
  curl_close($curl);
  //var_dump($response);
  //exit;
  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
  //var_dump($response->erro);
    header('Location: index.php');
    die();
  }

  $_SESSION['seed'] = $response->seed;
  $seed = $_SESSION['seed'];
  $palpites = [];
  $pokemons = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['pokemons']);
  unset($_SESSION['descobriu']);
}

if (empty($_SESSION['pokemons'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/pokemons',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);
  //var_dump($response);exit;

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['ids'] = $response->ids_dos_pokemons_das_geracoes_selecionadas;
  $_SESSION['pokemons'] = $response->nomes_dos_pokemons_das_geracoes_selecionadas;
  $_SESSION['sprites'] = $response->urls_dos_sprites_dos_pokemons_das_geracoes_selecionadas;
  $pokemons = $_SESSION['pokemons'];
}

if (isset($_POST['palpite']) && $_SESSION['descobriu'] == false) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => ['pokemon' => $_POST['palpite']],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);
  //var_dump($response);exit;

  if (!$response)
    $erro = 'Erro na comunicação com o servidor: '.curl_error($curl);
  else if (isset($response->erro))
    $erro = $response->erro;
  else {
    $pokemon = $response;
    array_push($_SESSION['palpites'], $pokemon);
    array_unshift($palpites, $pokemon);
  }
}

if (empty($_SESSION['palpites'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  //var_dump($response);
  //echo '..getpalpites<br>';
  curl_close($curl);
  //var_dump($response);exit;

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    //echo 'errinho';
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    //echo $response->erro;
    //echo $_COOKIE['PHPSESSID'];
    header('Location: index.php');
    die();
    //exit;
  }
  $_SESSION['palpites'] = $response->palpites;
  $palpites = array_reverse($_SESSION['palpites']);
  //var_dump($palpites);
}

if (empty($_SESSION['descobriu'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  //var_dump($response);
  //echo '..getjogo<br>';
  curl_close($curl);

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['descobriu'] = $response->descobriu;
  $_SESSION['geracoes'] = $response->geracoes;
  $_SESSION['geracao_contexto'] = $response->geracao_contexto;
  //if (isset($response->descobriu))
    $descobriu = $_SESSION['descobriu'];
    //$geracoes = implode(',', $_SESSION['geracoes']);
    $geracoes = $_SESSION['geracoes'];
    $geracao_contexto = $_SESSION['geracao_contexto'];
}

$nomes_dos_pokemons_palpitados = array_map(function($p) {return $p->nome;}, $palpites);
$nomes = array_diff($pokemons, $nomes_dos_pokemons_palpitados);

if (isset($pokemon->id_c) && $pokemon->id_c === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
}
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

<datalist id="pokemons">
<?php
foreach ($nomes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Pokédle+<br>
seed: [<?php echo $seed; ?>], gerações: [<?php echo implode(',', $geracoes); ?>], contexto: [<?php echo $geracao_contexto; ?>ª geração]<br>

<form action="pokedle.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<label for="palpite">Pokémon:</label><br>
<form action="pokedle.php" method="POST" style="margin: 0.5rem 0;">
<input list="pokemons" id="palpite" name="palpite" autofocus autocomplete="off"/>
<input type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>

<table>
<tr>
  <th></th>
  <th>Nome</th>
  <th>Tipo 1</th>
  <th>Tipo 2</th>
  <th>Cor principal</th>
  <th>Evoluído</th>
  <th>Altura</th>
  <th>Peso</th>
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td><img src="'.$_SESSION['sprites'][array_search($pp->id,$_SESSION['ids'])].'"</td>
    <td style="background-color: '.($pp->nome_c ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo1_c === 1 ? 'lime' : ($pp->tipo1_c === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo1.'</td>
    <td style="background-color: '.($pp->tipo2_c === 1 ? 'lime' : ($pp->tipo2_c === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo2.'</td>
    <td style="background-color: '.($pp->cor_c ? 'lime' : 'red').';">'
    .$pp->cor.'</td>
    <td style="background-color: '.($pp->evoluido_c ? 'lime' : 'red').';">'
    .$pp->evoluido.'</td>
    <td style="background-color: '.($pp->altura_c === 1 ? 'lime' : 'red').';">'
    .($pp->altura_c === 2 ? '<' : ($pp->altura_c === 0 ? '>' : '')).($pp->altura).'m</td>
    <td style="background-color: '.($pp->peso_c === 1 ? 'lime' : 'red').';">'
    .($pp->peso_c === 2 ? '<' : ($pp->peso_c === 0 ? '>' : '')).($pp->peso).'kg</td>
  </tr>
  ';
}
?>
</table>

<?php
if ($descobriu && isset($_POST['palpite']))
  echo "<script>alert('Parabéns! Você descobriu o pokémon!')</script>";
?>

</body>
</html>