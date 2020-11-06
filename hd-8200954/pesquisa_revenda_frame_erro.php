<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (!function_exists('ttext')) {
	include_once '/var/www/telecontrol/www/trad_site/fn_ttext.php';
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
?>
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
    <!--<img src="imagens/pesquisa_revenda<?=$img_suffix?>.gif">-->
<?
require "_class_paginacao.php";

// ##### PAGINACAO ##### //
$max_links = 15;				// máximo de links à serem exibidos
$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

//  HD 234135 19/08/2010 - Usar tbl_revenda_fabrica...
//                         Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3));

$filtrar_pais = ($login_fabrica == 20) ? " AND tbl_revenda.pais='$login_pais'" : '';

if($cook_idioma == 'pt-br') $cond_cnpj_validado = " AND cnpj_validado IS TRUE ";

if (strlen(trim($_GET['nome'])) > 3) {
	$nome = strtoupper(trim($_GET['nome']));
?>
<div style="float:left;width:96%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:4%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>
<div style="float:left;color:#596d9b;width:100%;background:;height:35px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');float:left;">
    <h3>
			<?=ttext($pr_trad, "pesquisa_nome")?><?=$nome?></h3>
</div>

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
                  AND   ativo IS NOT FALSE
                        $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	$res = pg_exec ($con,$sql);

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
    <div style="float:left;width:96%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
	</div>
	<div style="float:right;width:4%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
	</div>
	<div style="float:left;color:#596d9b;width:100%;background:;height:35px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');float:left;">
    <h3><?=ttext($pr_trad, "pesquisa_cnpj")?>
	<?
    echo $cnpj;
    if (strlen($cnpj) < 14) echo substr(str_repeat('.', 14 - strlen($cnpj)), 0, 3);
    ?>
	</div>
	</h3>
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
                  $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";

	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	
	$res = pg_exec ($con,$sql);

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
	<div style="float:left;width:96%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
	</div>
	<div style="float:right;width:4%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
	</div>
	<div style="float:left;color:#596d9b;width:100%;background:;height:47px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');float:left;">
    <h2><?=ttext($pr_trad, "digite_ao_menos")?></h2>
	</div>
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
                        $filtrar_pais
            ORDER BY    nome_estado, nome_cidade, bairro, nome";
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	
	$res = pg_exec ($con,$sql);

	if (pg_num_rows($res) == 0) { ?>
        <h1><?=ttext($pr_trad, "nome_not_found")?></h1>
        <script language='javascript'>
            setTimeout('nome.value="",cnpj.value="",2500);
        </script>
    </body>
</html>
<?		exit;
	}
}

if (pg_num_rows($res) > 0 ) {   ?>
        </p>
		<div style="background:transparent;height: 420px;width:100%;overflow:auto;float:left;">
        <table width='99%' cellpadding="0" cellspacing="0" border="0" class="display" id="modal_3">
        <thead>
        	<tr style="text-align:left;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;width:100%;">
        		<th width="15%"><?php echo traduz("cnpj.revenda",$con,$cook_idioma);?></th>
        		<th width="30%"><?php echo traduz('nome.revenda',$con,$cook_idioma);?></th>
        		<th width="20%"><?php echo traduz('bairro',$con,$cook_idioma);?></th>
        		<th width="20%"><?php echo traduz('cidade',$con,$cook_idioma);?></th>
        		<th width="15%"><?php echo traduz('estado',$con,$cook_idioma);?></th>
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
                <td><?php echo $cnpj;?></td>
                <td>
<?		if ($_GET['forma'] == 'reload') {
			$rev_id = ($usa_rev_fabrica) ? preg_replace('/\D/', '', $cnpj) : $revenda;
?>
                    <a href="javascript:opener.document.location=retorno+'?revenda=<?php echo $rev_id;?>';window.close();">
<?      
		}else{
?>                  
<a href="#" onclick="retorna_dados_revenda('<?php echo $nome;?>','<?php echo $cnpj;?>','<?php echo $nome_cidade;?>','<?php echo pg_fetch_result($res,$i,fone);?>','<?php echo pg_fetch_result($res,$i,endereco);?>','<?php echo pg_fetch_result($res,$i,numero);?>','<?php echo pg_fetch_result($res,$i,complemento);?>','<?php echo $bairro;?>','<?php echo pg_fetch_result($res,$i,cep);?>','<?php echo pg_fetch_result($res,$i,estado);?>','<?php echo pg_fetch_result($res,$i,email);?>')">
<?php
	}



echo $nome?></a>
                </td>
                <td><?php echo $bairro?></td>
                <td><?php echo $nome_cidade?></td>
                <td><?php echo $nome_estado?></td>
            </tr>
<?	
	}
	?>

	</tbody>
	</table>
	</div>

	<?php
}
?>
    </body>
</html>