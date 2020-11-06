<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

if (!function_exists('ttext')) {
	include_once 'helpdesk/fn_ttext.php';
}

$pr_trad = array (
	'titulo' => array (
		'pt-br'	=> 'Pesquisa Revendas',
		'es'	=> 'Busca Distribuidores',
		'en'	=> 'Resellers Search'
	),
	"pesquisa_nome" => array (
		"pt-br"	=> "Resultados da pesquisa pelo <b>nome</b> da Revenda: ",
		"es"	=> "Resultado de la búsqueda por <b>nombre</b> del Distribuidor: ",
	),
	"nome_not_found" => array (
		"pt-br"	=> "Revenda '%s' não encontrada",
		"es"	=> "Distribuidor '%s' no encontrado",
	),
	"pesquisa_cnpj" => array (
		"pt-br"	=> "Resultados da pesquisa pelo <b>CNPJ</b> da Revenda: ",
		"es"	=> "Resultado de la búsqueda por <b>nº ID Fiscal</b> del Distribuidor: ",
	),
	"cnpj_not_found" => array (
		"pt-br"	=> "Revenda de CNPJ '%s' não encontrada",
		"es"	=> "Distribuidor con ID Fiscal '%s' no encontrado",
	),
	"digite_ao_menos" => array (
		"pt-br"	=> "Digite ao menos as 4 primeiras letras para pesquisar por nome, ou os 6 primeiros dígitos do CNPJ",
		"es"	=> "Escriba al menos las primeras 4 letras del nombre, o los 6 primeros dígitos del nº de ID Fiscal",
	),
);

$cook_idioma = 'pt-br';
if ($sistema_lingua == 'ES') {
    $cook_idioma = 'es';
    $img_suffix  = '_es';
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv='pragma' content='no-cache'>
    <title><?=ttext($pr_trad, "titulo")?></title>
    <style type="text/css">
    <!--
    body {
        margin: 0;
        font-family: Arial, Verdana, Times, Sans;
    }
    h1,h2,h3{padding: 1em;}
    h1      {font-size: 20px;text-align:center;}
    h2, h3  {font-size: 18px;display:inline-block}
    h3      {font-size: 15px;font-style: italic;}
    tbody > tr:nth-child(even) {background-color:#def}
    th      {font-weight:bold;font-size:11px;background-color:#acf}
    td      {font-size: 10px; color: black}
    td > a  {color:blue;text-decoration: none;}
    td>a:hover{color:navy;text-decoration: underline;}
    //-->
    </style>
    <script type='text/javascript'>
        <!--
        window.focus();
        // -->
    </script>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
</head>
<body>
    <img src="imagens/pesquisa_revenda<?=$img_suffix?>.gif">
<?
require "_class_paginacao.php";

// ##### PAGINACAO ##### //
$max_links = 15;				// máximo de links à serem exibidos
$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

//  HD 234135 19/08/2010 - Usar tbl_revenda_fabrica...
//                         Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3,161));

$filtrar_pais = ($login_fabrica == 20) ? " AND tbl_revenda.pais='$login_pais'" : '';

if($cook_idioma == 'pt-br') $cond_cnpj_validado = " AND cnpj_validado IS TRUE ";

$consumidor_revenda = $_GET['consumidor_revenda'];

if (strlen(trim($_GET['nome'])) > 3) {
	$nome = strtoupper(trim($_GET['nome']));
?>
    <br>
    <h2><?=ttext($pr_trad, "pesquisa_nome")?></h2>
	<h3><?=$nome?></h3>
    <p>
<?

	//HD 34515 22/8/2008 LPAD
	$sql = "SELECT DISTINCT
              LPAD(tbl_revenda.cnpj, 14, '0') AS cnpj ,
						tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_estado.nome AS nome_estado,
						tbl_cidade.estado
			FROM        tbl_revenda
			     JOIN   tbl_cidade USING (cidade)
			     JOIN   tbl_estado USING (estado)
			WHERE       tbl_revenda.nome LIKE UPPER('$nome%')
						$cond_cnpj_validado
						$filtrar_pais
						AND ativo IS NOT FALSE
			ORDER BY    nome_estado, nome_cidade, bairro, nome";
    if ($usa_rev_fabrica) $sql = "SELECT DISTINCT
                    	cnpj,
						contato_razao_social AS nome        ,
                        contato_endereco     AS endereco    ,
                        contato_bairro       AS bairro      ,
                        contato_complemento  AS complemento ,
                        contato_numero       AS numero      ,
                        contato_cep          AS cep         ,
                        contato_fone         AS fone        ,
                        contato_email        AS email       ,
						tbl_cidade.nome      AS nome_cidade ,
						tbl_estado.nome      AS nome_estado ,
                        tbl_cidade.estado      AS estado
			FROM        tbl_revenda_fabrica
			     JOIN   tbl_cidade   ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			     JOIN   tbl_estado USING (estado)
			     WHERE       contato_razao_social LIKE UPPER('$nome%')
			     AND tbl_revenda_fabrica.fabrica = $login_fabrica
                        $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	// ##### PAGINACAO ##### //
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	if (pg_num_rows($res) == 0) { ?>
        <h1><? printf(ttext($pr_trad, "nome_not_found"), $nome); ?></h1>
        <script language='javascript'>
            setTimeout('window.close()',2500);
        </script>
    </body>
</html>
<?		exit;
	}

} elseif (strlen($_GET['cnpj']) > 5) {
	$cnpj = preg_replace('/\D/', '', trim($_GET['cnpj'])); ?>
    <br>
    <h2><?=ttext($pr_trad, "pesquisa_cnpj")?></h2>
	<h3><?
    echo $cnpj;
    if (strlen($cnpj) < 14) echo substr(str_repeat('.', 14 - strlen($cnpj)), 0, 3);
    ?></h3>
    <p>
<?
	// HD 37000
    $cond_cnpj = (strlen($cnpj) == 14) ? "cnpj = '$cnpj'" : "cnpj ~ E'^$cnpj'";
	$sql = "SELECT DISTINCT
              LPAD(tbl_revenda.cnpj, 14, '0') AS cnpj ,
						tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_estado.nome AS nome_estado,
						tbl_cidade.estado
			FROM        tbl_revenda
			     JOIN   tbl_cidade USING (cidade)
			     JOIN   tbl_estado USING (estado)
			WHERE       tbl_revenda.$cond_cnpj
                        $cond_cnpj_validado
                        $filtrar_pais
						AND ativo IS NOT FALSE
			ORDER BY    nome_estado, nome_cidade, bairro, nome";

    if ($usa_rev_fabrica) $sql = "SELECT DISTINCT
                    	cnpj								,
						contato_razao_social AS nome        ,
                        contato_endereco     AS endereco    ,
                        contato_bairro       AS bairro      ,
                        contato_complemento  AS complemento ,
                        contato_numero       AS numero      ,
                        contato_cep          AS cep         ,
                        contato_fone         AS fone        ,
                        contato_email        AS email       ,
						tbl_cidade.nome      AS nome_cidade ,
						tbl_estado.nome      AS nome_estado ,
                        tbl_cidade.estado      AS estado
			FROM        tbl_revenda_fabrica
			     JOIN   tbl_cidade   ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			     JOIN   tbl_estado USING (estado)
			WHERE       tbl_revenda_fabrica.$cond_cnpj
			     AND tbl_revenda_fabrica.fabrica = $login_fabrica
                  $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
 //echo "<pre>".var_dump($sql).'</pre>';
	// ##### PAGINACAO ##### //
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	if (pg_num_rows($res) == 0) { ?>
        <h1><? printf(ttext($pr_trad, "cnpj_not_found"), $cnpj); ?></h1>
        <script language='javascript'>
            setTimeout('window.close();',2500);
        </script>
    </body>
</html>
<?		exit;
	}
} else {    ?>
    <h2><?=ttext($pr_trad, "digite_ao_menos")?></h2>
    </body>
</html>
<?	exit;

?>
        <br>
        <h2><?=ttext($pr_trad, "pesquisa_nome")?></h2>
	    <h3><?=$cpf?></h3>
        <p>
<?
	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
            WHERE       cnpj_validado IS TRUE
			LEFT JOIN   tbl_cidade USING (cidade)
			ORDER BY    tbl_revenda.nome
            ";
    if ($usa_rev_fabrica) $sql = "SELECT DISTINCT
                    	cnpj,
                        contato_razao_social AS nome        ,
                        contato_endereco     AS endereco    ,
                        contato_bairro       AS bairro      ,
                        contato_complemento  AS complemento ,
                        contato_numero       AS numero      ,
                        contato_cep          AS cep         ,
                        contato_fone         AS fone        ,
                        contato_email        AS email       ,
						tbl_cidade.nome      AS nome_cidade ,
						tbl_estado.nome      AS nome_estado ,
                        tbl_cidade.estado    AS estado
			FROM        tbl_revenda_fabrica
			     JOIN   tbl_cidade   ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			     JOIN   tbl_estado USING (estado)
			WHERE       ativo IS NOT FALSE
			     AND tbl_revenda_fabrica.fabrica = $login_fabrica
                        $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	if (pg_num_rows($res) == 0) { ?>
        <h1><?=ttext($pr_trad, "nome_not_found")?></h1>
        <script language='javascript'>
            setTimeout('nome.value="",cnpj.value="",2500');
        </script>
    </body>
</html>
<?		exit;
	}
}

if (pg_num_rows($res) > 0 ) {   ?>
        </p>
        <table width='100%' border='0'>
        <thead>
        	<tr>
        		<th><?=traduz("cnpj.revenda",$con,$cook_idioma)?></th>
        		<th><?=traduz('nome.revenda',$con,$cook_idioma)?></th>
        		<th><?=traduz('bairro',$con,$cook_idioma)?></th>
        		<th><?=traduz('cidade',$con,$cook_idioma)?></th>
        		<th><?=traduz('estado',$con,$cook_idioma)?></th>
        	</tr>
        </thead>
		<tbody>
<?
    $tot = pg_num_rows($res);

	for ( $i = 0 ; $i < $tot; $i++ ) {
		extract(pg_fetch_assoc($res, $i));
		$cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
		$js_obj    .= "{ $i:['nome':'$nome','cnpj':'$cnpj','fone':'$fone','endereco':'$endereco',".
							"'numero':'$numero','complemento':'$complemento','bairro':'$bairro',".
							"'cep':'$cep','cidade':'$nome_cidade','estado':'$estado','email':'$email'},\n";
?>
            <tr>
                <td><?=$cnpj?></td>
                <td>
<?		if ($_GET['forma'] == 'reload') {
			$rev_id = ($usa_rev_fabrica) ? preg_replace('/\D/', '', $cnpj) : $revenda;
?>
                    <a href="javascript:opener.document.location=retorno+'?revenda=<?=$rev_id?>';window.close();">
<?      } elseif($consumidor_revenda =='C') { ?>

		<a href="javascript:
     	if (window.opener.frm_os.revenda_nome != undefined) {window.opener.frm_os.consumidor_nome.value='<?=$nome?>';}

		if (window.opener.frm_os.consumidor_cnpj != undefined) {window.opener.frm_os.consumidor_cnpj.value='<?=$cnpj?>';}

       window.close();">

<?		}else{ ?>
	  <a href="javascript:

     	if (window.opener.frm_os.revenda_nome != undefined) {window.opener.frm_os.revenda_nome.value='<?=$nome?>';}

		if (window.opener.frm_os.revenda_cnpj != undefined){window.opener.frm_os.revenda_cnpj.value='<?=$cnpj?>';}

		if(window.opener.frm_os.revenda_cidade != undefined) {window.opener.frm_os.revenda_cidade.value='<?=$nome_cidade?>';}

		if(window.opener.frm_os.revenda_fone != undefined) {window.opener.frm_os.revenda_fone.value='<?=pg_fetch_result($res,$i,fone)?>';}

		if(window.opener.frm_os.revenda_endereco != undefined) {window.opener.frm_os.revenda_endereco.value='<?=pg_fetch_result($res,$i,endereco)?>';}

		if(window.opener.frm_os.revenda_numero != undefined) {window.opener.frm_os.revenda_numero.value='<?=pg_fetch_result($res,$i,numero)?>';}

		if(window.opener.frm_os.revenda_bairro != undefined) {window.opener.frm_os.revenda_bairro.value='<?=$bairro?>';}

		if(window.opener.frm_os.revenda_cep != undefined) {window.opener.frm_os.revenda_cep.value='<?=pg_fetch_result($res,$i,cep)?>';}

		if(window.opener.frm_os.revenda_estado != undefined) {window.opener.frm_os.revenda_estado.value='<?=pg_fetch_result($res,$i,estado)?>';}

       window.close();">

<?		}?><?=$nome?></a>
                </td>
                <td><?=$bairro?></td>
                <td><?=$nome_cidade?></td>
                <td><?=$nome_estado?></td>
            </tr>
<?	}
	echo "</tbody>\n</table>\n";
	// ##### PAGINACAO ##### //
	// links da paginacao
	echo "<br>";

	echo "<div style='color:#ddd'>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("todos", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo $links_limitados[$n]."&nbsp;&nbsp;";
	}
	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){   ?>
        <br>
		<div style='font-size:12px;color:#555'>
<?      fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
		echo "&nbsp;&nbsp;<span style='font-size:11px;color:#aaa'>";
		fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
		echo "</span></div>";
	}
	// ##### PAGINACAO ##### //
}
?>
    </body>
</html>
