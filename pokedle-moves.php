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
  //if (isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd")) {
  //  unset($_SESSION);
  //  $_SESSION['mensagem'] = 'Havia um jogo em andamento.';
  //  header('Location: index.php');
  //  die();
  //}

  if (empty($_SESSION['modo'])) {
    header('Location: index.php');
    die();
  } else if ($_SESSION['modo'] != 'tecnica') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

$URL_BASE = 'http://localhost/pokedle-api/pokedle-moves-api/v1';
//$URL_BASE = 'https://wilsrpg.42web.io/pokedle-api/pokedle-moves-api/v1';
//$URL_BASE = 'http://wilsrpg.unaux.com/pokedle-moves-api/v1';
//$URL_BASE = 'https://wilsrpg.x10.mx/pokedle-moves-api/v1';
$TIMEOUT = 15;
$cookieFile = getcwd().'/cookies/cookie.txt';

$seed = 0;
$geracoes = '';
$geracao_contexto = '';
$palpites = [];
$tecnicas = [];
$descobriu = false;
$dicas = [
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false],
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false]
];
$qtde_palpites_pra_revelar_dica_1 = 4;
$qtde_palpites_pra_revelar_dica_2 = 8;

$palpite = '';
$erro = '';
$tecnica = '';
$nomes = [];

if (isset($_SESSION['seed']))
  $seed = $_SESSION['seed'];
if (isset($_SESSION['palpites']))
  $palpites = array_reverse($_SESSION['palpites']);
if (isset($_SESSION['tecnicas']))
  $tecnicas = $_SESSION['tecnicas'];
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
  if (isset($_POST['geracao_contexto']))
    $geracao_contexto = $_POST['geracao_contexto'];
  if (isset($_POST['data']))
    $data = str_replace('-', '', $_POST['data']);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_POST => 2,
    CURLOPT_POSTFIELDS => ['geracoes' => $geracoes, 'geracao_contexto' => $geracao_contexto, 'data' => $data],
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

  $_SESSION['seed'] = $response->seed;
  $_SESSION['modo'] = $response->modo;
  $_SESSION['geracoes'] = $response->geracoes;
  $_SESSION['geracao_contexto'] = $response->geracao_contexto;
  $seed = $_SESSION['seed'];
  $palpites = [];
  $tecnicas = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['tecnicas']);
  unset($_SESSION['descobriu']);
  $_SESSION['dicas'] = [
    ['dica' => $response->dicas[0], 'revelada' => false, 'durante_o_jogo' => false],
    ['dica' => $response->dicas[1], 'revelada' => false, 'durante_o_jogo' => false]
  ];
  $dicas = $_SESSION['dicas'];
}

if (empty($_SESSION['tecnicas'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/tecnicas',
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
  $_SESSION['tecnicas'] = $response->nomes_das_tecnicas_das_geracoes_selecionadas;
  sort($_SESSION['tecnicas']);
  $tecnicas = $_SESSION['tecnicas'];
}

if (isset($_POST['palpite']) && $_SESSION['descobriu'] == false) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
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

  if (!$response)
    $erro = 'Erro na comunicação com o servidor: '.curl_error($curl);
  else if (isset($response->erro))
    $erro = $response->erro;
  else {
    $tecnica = $response;
    array_push($_SESSION['palpites'], $tecnica);
    array_unshift($palpites, $tecnica);
  }
}

if (empty($_SESSION['palpites'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
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
  $_SESSION['palpites'] = $response->palpites;
  $palpites = array_reverse($_SESSION['palpites']);
}

if (empty($_SESSION['descobriu'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
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
  $descobriu = $_SESSION['descobriu'];
  $geracoes = $_SESSION['geracoes'];
  $geracao_contexto = $_SESSION['geracao_contexto'];
}

$nomes_das_tecnicas_palpitadas = array_map(function($p) {return $p->nome;}, $palpites);
$nomes = array_diff($tecnicas, $nomes_das_tecnicas_palpitadas);

if (isset($tecnica->id_r) && $tecnica->id_r === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu a técnica!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokédle+Gerações: Técnicas</title>
  </head>
<body>

<datalist id="tecnicas">
<?php
foreach ($nomes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Pokédle+: Técnicas<br>
seed: [<?php echo $seed; ?>], gerações: [<?php echo implode(',', $geracoes); ?>], contexto: [<?php echo $geracao_contexto; ?>ª geração]<br>

<form action="pokedle-moves.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<form id="form_palpite" action="pokedle-moves.php" method="POST" style="margin: 0.5rem 0;">
  <label for="palpite">Técnica:</label><br>
  <input id="palpite" list="tecnicas" name="palpite" autofocus autocomplete="off"/>
  <input id="enviar" type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>Dicas reveladas durante o jogo:
<?php
  echo ($dicas[0]['durante_o_jogo'] ? 'pokémon' : '')
    . ($dicas[0]['durante_o_jogo'] && $dicas[1]['durante_o_jogo'] ? ', ' : '')
    . ($dicas[1]['durante_o_jogo'] ? 'descrição' : '')
    . (!$dicas[0]['durante_o_jogo'] && !$dicas[1]['durante_o_jogo'] ? 'nenhuma' : '');
?>
<form action="pokedle-moves.php" method="POST">
<?php
  if (!$dicas[0]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_1 && !$descobriu)
      echo '<button disabled>Revelar um pokémon que pode aprender a técnica naturalmente em '
        .($qtde_palpites_pra_revelar_dica_1 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 0 .'">Revelar um pokémon que pode aprender a técnica naturalmente</button>';
  } else if ($_SESSION['dicas'][0]['dica'])
    echo 'Pokémon que pode aprender naturalmente: '.$_SESSION['dicas'][0]['dica'];
  else
    echo '[informação não encontrada]';
  echo '<br>';
  if (!$dicas[1]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_2 && !$descobriu)
      echo '<button disabled>Revelar descrição em '
        .($qtde_palpites_pra_revelar_dica_2 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 1 .'">Revelar descrição</button>';
  } else if ($_SESSION['dicas'][1]['dica'])
    echo 'Descrição: '.$_SESSION['dicas'][1]['dica'];
  else
    echo '[descrição não encontrada]';
?>
</form>

<table>
<tr>
  <th>Nome</th>
  <th>Tipo</th>
  <th>Poder</th>
  <th>Precisão</th>
  <th>PP</th>
  <th>Categoria</th>
  <th>Alvo</th>
  <th>Afeta atributo</th>
  <th>Causa condição</th>
  <th>Cura o usuário</th>
  <th>Efeito único</th>
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo_r === 1 ? 'lime' : ($pp->tipo_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo.'</td>
    <td style="background-color: '.($pp->poder_r === 1 ? 'lime' : 'red').';">'
    .($pp->poder_r === 2 ? '<' : ($pp->poder_r === 0 ? '>' : '')).($pp->poder).'</td>
    <td style="background-color: '.($pp->precisao_r === 1 ? 'lime' : 'red').';">'
    .($pp->precisao_r === 2 ? '<' : ($pp->precisao_r === 0 ? '>' : '')).($pp->precisao).'</td>
    <td style="background-color: '.($pp->pp_r === 1 ? 'lime' : 'red').';">'
    .($pp->pp_r === 2 ? '<' : ($pp->pp_r === 0 ? '>' : '')).($pp->pp).'</td>
    <td style="background-color: '.($pp->categoria_r ? 'lime' : 'red').';">'
    .$pp->categoria.'</td>
    <td style="background-color: '.($pp->alvo_r ? 'lime' : 'red').';">'
    .$pp->alvo.'</td>
    <td style="background-color: '.($pp->afeta_stat_r ? 'lime' : 'red').';">'
    .$pp->afeta_stat.'</td>
    <td style="background-color: '.($pp->causa_ailment_r ? 'lime' : 'red').';">'
    .$pp->causa_ailment.'</td>
    <td style="background-color: '.($pp->cura_usuario_r ? 'lime' : 'red').';">'
    .$pp->cura_usuario.'</td>
    <td style="background-color: '.($pp->efeito_unico_r ? 'lime' : 'red').';">'
    .$pp->efeito_unico.'</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu a técnica!')</script>";
?>

</body>

<script>
  let alterou,tecla;
  document.getElementById('palpite').addEventListener('keydown', function (e) {
    if (e.keyCode >= 33 && e.keyCode <= 40)
      tecla = false;
    else
      tecla = true;
  });
  document.getElementById('palpite').addEventListener('click', function (e) {
    tecla = false;
  });
  document.getElementById('palpite').addEventListener('input', function (e) {
    if (!tecla && !document.getElementById('enviar').disabled)
      document.getElementById('form_palpite').submit();
    tecla = false;
  });
</script>

</html>