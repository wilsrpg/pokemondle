<?php
date_default_timezone_set('America/Sao_Paulo');
require 'vendor/autoload.php';
session_start();

if (isset($_POST['voltar'])) {
  header('Location: index.php');
  die();
}

if (isset($_POST['data']) && $_POST['data'] > date("Y-m-d")) {
  $_SESSION['mensagem'] = 'Não é permitido jogar no futuro.';
  header('Location: index.php');
  die();
}
  
if (empty($_POST['geracoes'])) {
//  if (isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd")) {
//    unset($_SESSION);
//    $_SESSION['mensagem'] = 'Havia um jogo em andamento.';
//    header('Location: index.php');
//    die();
//  }

  if (empty($_SESSION['modo'])) {
    header('Location: index.php');
    die();
  } else if ($_SESSION['modo'] != 'pokemon') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$URL_BASE = $_ENV['ENDERECO_DO_SERVIDOR'].'/pokemondle-api/v1';

//$URL_BASE = 'http://localhost/pokemondle-api/pokemondle-api/v1';
//$URL_BASE = 'https://wilsrpg.42web.io/pokedle-api/pokedle-api/v1';
//$URL_BASE = 'http://wilsrpg.unaux.com/pokedle-api/v1';
//$URL_BASE = 'https://pokemondle.x10.mx/pokemondle-api/v1';
$TIMEOUT = 15;
$cookieFile = getcwd().'/cookies/cookie.txt';

$seed = 0;
$geracoes = '';
$geracao_contexto = '';
$palpites = [];
$pokemons = [];
$descobriu = false;
$dicas = [
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false],
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false]
];
$qtde_palpites_pra_revelar_dica_1 = 4;
$qtde_palpites_pra_revelar_dica_2 = 8;

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

  if (isset($_SESSION['dicas']))
  $dicas = $_SESSION['dicas'];

if (isset($_POST['dica'])) {
  $n = (int) $_POST['dica'];
  if ($n < 0 || $n > 1) {
    $_SESSION['mensagem'] = 'Dica inexistente: "'.$_POST['dica'].'"';
    die();
  }
  $_SESSION['dicas'][$n]['revelada'] = true;
  if (!$descobriu)
    $_SESSION['dicas'][$n]['durante_o_jogo'] = true;
  $dicas = $_SESSION['dicas'];
}

if(isset($_POST['geracoes'])) {
  $geracoes = $_POST['geracoes'];
  //var_dump($_POST);exit;
  if (isset($_POST['geracao_contexto']))
    $geracao_contexto = $_POST['geracao_contexto'];
  if (isset($_POST['data']))
    $data = str_replace('-','',$_POST['data']);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_POST => 2,
    CURLOPT_POSTFIELDS => ['geracoes' => $geracoes, 'geracao_contexto' => $geracao_contexto, 'data' => $data],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  //var_dump($response);exit;
  curl_close($curl);
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
  //var_dump($response);exit;

  $_SESSION['seed'] = $response->seed;
  $_SESSION['modo'] = $response->modo;
  $_SESSION['geracoes'] = $response->geracoes;
  $_SESSION['geracao_contexto'] = $response->geracao_contexto;
  $seed = $_SESSION['seed'];
  $palpites = [];
  $pokemons = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['pokemons']);
  unset($_SESSION['descobriu']);
  unset($_SESSION['ids']);
  unset($_SESSION['sprites']);
  $_SESSION['dicas'] = [
    ['dica' => $response->dicas[0], 'revelada' => false, 'durante_o_jogo' => false],
    ['dica' => $response->dicas[1], 'revelada' => false, 'durante_o_jogo' => false]
  ];
  $dicas = $_SESSION['dicas'];
  //var_dump($response);exit;
  header('Location: pokemondle.php');
  die();
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
    CURLOPT_POSTFIELDS => ['palpite' => $_POST['palpite']],
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
    header('Location: pokemondle.php');
    die();
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

if (isset($pokemon->id_r) && $pokemon->id_r === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu o pokémon!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokémondle</title>
  </head>
<body>

<datalist id="pokemons">
<?php
foreach ($nomes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Pokémondle<br>
Data do jogo: <?php echo $seed; ?>. Gerações: <?php echo implode(',', $geracoes); ?>. Geração base: <?php echo $geracao_contexto; ?>ª geração.<br>

<form action="pokemondle.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<form id="form_palpite" action="pokemondle.php" method="POST" style="margin: 0.5rem 0;">
  <label for="palpite">Pokémon:</label><br>
  <input id="palpite" list="pokemons" name="palpite" autofocus autocomplete="off" />
  <input id="enviar" type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>Dicas reveladas durante o jogo:
<?php
  echo ($dicas[0]['durante_o_jogo'] ? 'cry' : '')
    . ($dicas[0]['durante_o_jogo'] && $dicas[1]['durante_o_jogo'] ? ', ' : '')
    . ($dicas[1]['durante_o_jogo'] ? 'ability' : '')
    . (!$dicas[0]['durante_o_jogo'] && !$dicas[1]['durante_o_jogo'] ? 'nenhuma' : '');
?>
<form action="pokemondle.php" method="POST">
<?php
  if (!$dicas[0]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_1 && !$descobriu)
      echo '<button disabled>Revelar cry do pokémon em '
        .($qtde_palpites_pra_revelar_dica_1 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 0 .'">Revelar cry do pokémon</button>';
  } else if ($_SESSION['dicas'][0]['dica'])
    echo 'Cry: <audio controls>
      <source src="'.$_SESSION['dicas'][0]['dica'].'" type="audio/ogg">
        Seu navegador não suporta o elemento "audio"
      </audio>';
  else
    echo '[arquivo de áudio não encontrado]';
  echo '<br>';
  if (!$dicas[1]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_2 && !$descobriu)
      echo '<button disabled>Revelar uma ability em '
        .($qtde_palpites_pra_revelar_dica_2 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 1 .'">Revelar uma ability</button>';
  } else if ($_SESSION['dicas'][1]['dica'])
    echo 'Ability: '.$_SESSION['dicas'][1]['dica'];
  else
    echo 'Sem ability.';
?>
</form>

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
    <td><img src="'.$_SESSION['sprites'][array_search($pp->id,$_SESSION['ids'])].'"></td>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo1_r === 1 ? 'lime' : ($pp->tipo1_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo1.'</td>
    <td style="background-color: '.($pp->tipo2_r === 1 ? 'lime' : ($pp->tipo2_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo2.'</td>
    <td style="background-color: '.($pp->cor_r ? 'lime' : 'red').';">'
    .$pp->cor.'</td>
    <td style="background-color: '.($pp->evoluido_r ? 'lime' : 'red').';">'
    .$pp->evoluido.'</td>
    <td style="background-color: '.($pp->altura_r === 1 ? 'lime' : 'red').';">'
    .($pp->altura_r === 2 ? '<' : ($pp->altura_r === 0 ? '>' : '')).($pp->altura).'m</td>
    <td style="background-color: '.($pp->peso_r === 1 ? 'lime' : 'red').';">'
    .($pp->peso_r === 2 ? '<' : ($pp->peso_r === 0 ? '>' : '')).($pp->peso).'kg</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu o pokémon!')</script>";
?>

</body>

<script>
  let alterou,tecla,tkey;
  document.getElementById('palpite').addEventListener('keydown', function (e) {
    tkey = e.key;
    tecla = !(e.keyCode >= 33 && e.keyCode <= 40);
  });
  document.getElementById('palpite').addEventListener('click', function (e) {
    tecla = false;
  });
  document.getElementById('palpite').addEventListener('input', function (e) {
    if ((!tecla || !tkey) && !document.getElementById('enviar').disabled)
      document.getElementById('form_palpite').submit();
    tecla = false;
  });
</script>

</html>