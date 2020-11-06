<?
/*
O Relatorio que é enviado para MIAMI é um excel gerado pelo
perl /www/cgi_bin/blackedecker/six-sigma.pl (Geralmente é o Miguel e a Silvania que pede!!!
Não esquecer de alterar o range das datas....colocar 3 meses.
*/
#echo "temporariamente desativado";exit;
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,auditoria";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";


if (strtoupper($btnacao) == "GERAR") {

	$data_incial = $_POST['data_inicial'];
	$data_final  = $_POST['data_final'];

	//Início Validação de Datas
	if ($data_inicial) {
        if (!$nova_data_inicial = dateFormat($data_inicial, 'dmy', 'ISO'))
            $erro = 'Data inválida!';
	}
    if ($data_final) {
        if (!$nova_data_final = dateFormat($data_final, 'dmy', 'ISO'))
            $erro = 'Data inválida!';
	}
	if (strlen($erro)==0) {
		if ($nova_data_final < $nova_data_inicial) {
			$erro = 'Data Inválida.';
		}
	}



	$x_data_inicial = trim($_POST['data_inicial']);
	$x_data_final   = trim($_POST['data_final']);
	$arr_linhas = $_POST['linha'];
	$estado     = $_POST['estado'];
	$data_os    = $_POST['data_os'][0];
	$ordem      = $_POST['ordem'];
	$ordem1     = $_POST['ordem1'];

	if (strlen($x_data_inicial) == 0) $erro = 'Data Inválida';
	if (strlen($x_data_final) == 0)   $erro = 'Data Inválida';

	if (strlen($erro) == 0) {
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		$y_data_inicial = substr($x_data_inicial,9,2) . substr($x_data_inicial,6,2) . substr($x_data_inicial,1,4);
		$y_data_final = substr($x_data_final,9,2) . substr($x_data_final,6,2) . substr($x_data_final,1,4);

		if ($x_data_inicial != 'null') {
			$data_inicial = substr($x_data_inicial,9,2) . '/' . substr($x_data_inicial,6,2) . '/' . substr($x_data_inicial,1,4);
		}else{
			$data_inicial = '';
			$erro = 'Data Inválida';
		}

		if ($x_data_final != 'null') {
			$data_final = substr($x_data_final,9,2) . '/' . substr($x_data_final,6,2) . '/' . substr($x_data_final,1,4);
		}else{
			$data_final = '';
			$erro = 'Data Inválida';
		}
	}

	$xdata_i = str_replace("'",'',$x_data_inicial);
	$xdata_f = str_replace("'",'',$x_data_final);

	if (strlen($erro) > 0) {
		$msg = $erro;
	}else{
		$relatorio = "gerar";
	}
}

$layout_menu = "auditoria";
$title = "VISÃO GERAL POR PRODUTO";
include 'cabecalho_new.php';
$plugins = array(
	"datepicker",
	"mask",
    "multiselect"
	);

include("plugin_loader.php");
?>

<?
$sql = "select to_char(current_date - interval '1 day', 'dd/mm/yyyy');";
$res = pg_query ($con,$sql);

if (@pg_num_rows($res) > 0) {
	$data_fim= trim(pg_fetch_result($res,0,0));
}

$sqlMarca = "
    SELECT  marca,
            nome
    FROM    tbl_marca
    WHERE   fabrica = $login_fabrica;
";

$resMarca = pg_query($con,$sqlMarca);
$marcasRes = pg_fetch_all($resMarca);

foreach($marcasRes as $chave => $valor){
    $marcas[$valor['marca']] = $valor['nome'];
}

$sql = "SELECT   linha,
				 nome
		FROM     tbl_linha
		where    fabrica = $login_fabrica
		ORDER BY nome;";
$res = pg_query ($con,$sql);
$linhas= pg_fetch_all($res);
$linhaA = array();
foreach($linhas as $linK){
	$linhaA[$linK['linha']] = $linK['nome'];
}

$todas = $_POST["todas"];
if(empty($todas))
    $_RESULT['todas'] = 1;

$filtro_mo = array(
    '1' => 'Todas as O.S.',
    '7' => 'Apenas Produto de Locação',
    '2' => 'Exceto Mero Desgaste (só carvão)',
    '3' => 'Exceto Mero Desgaste (só carvão) e só Manutenção',
    '5' => 'Apenas Mero Desgaste (só carvão)',
    '6' => 'Apenas Só Manutenção',
);

$descricao_todas = in_array($todas, array_keys($filtro_mo)) ? $filtro_mo[$todas] : $filtro_mo[$todas = 1];

// switch ($todas) {
//     case 2: $descricao_todas = 'Exceto Mero Desgaste (só carvão)'; break;
//     case 3: $descricao_todas = 'Exceto Mero Desgaste (só carvão) e só Manutenção'; break;
//     case 5: $descricao_todas = 'Apenas Mero Desgaste (só carvão)'; break;
//     case 6: $descricao_todas = 'Apenas Só Manutenção'; break;
//     case 7: $descricao_todas = 'Apenas Produto de Locação'; break;
//     default:
//         $descricao_todas= 'Todas as O.S.';
// }

if(empty($data_os))
    $_RESULT['data_os'] = 'data_envio';

$form1 = array(
	'data_inicial' => array(
		'span'      => 3,
		'label'     => 'Data Início',
		'type'      => 'input/text',
		'width'     => 10,
		'required'  => true,
		'maxlength' => 10
	),
	'data_final' => array(
		'span'      => 3,
		'label'     => 'Data Final',
		'type'      => 'input/text',
		'width'     => 10,
		'required'  => true,
		'maxlength' => 10
	),
	'marca' => array(
		'span'    => 4,
		'label'   => 'Marca',
		'type'    => 'select',
		'width'   => 13,
		'options' => $marcas
	),
	'linha[]' => array(
		'id'    => 'linha',
		'span'  => 3,
		'label' => 'Linha',
		'type'  => 'select',
		'width' => 13,
		'extra' => array('multiple' => 'multiple'),
		'options'  => $linhaA
	),
	'estado' => array(
		'span'    => 3,
		'label'   => 'Estado',
		'type'    => 'select',
		'width'   => 10,
		'options' => $estadosBrasil
	),
	'data_os' => array(
		'span'   => 5,
		'label'  => 'Data OS',
		'type'   => 'checkbox',
		'checks' => array(
			'data_envio' => "OSs Enviadas Para o Financeiro",
		),
	),
	'todas' => array(
		'span'   => 5,
		'label'  => 'Filtro',
		'type'   => 'radio',
		'radios' => $filtro_mo,
	)
);


?>
<style type='text/css'>
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.titulo_tabela {
    background-color:#596d9b;
    font: bold 14px Verdana, Arial, Helvetica, sans;
    color:#FFFFFF;
    text-align:center;
}
#caption_tabela {
    background-color: white;
    color:black;
    font: 10pt arial ;
    text-align: center;
    opacity:0.85;
    filter: alpha(opacity=85);
    width:700px;
    margin:auto;
}
.titulo_coluna {
    background-color:#596d9b;
    font: bold 11px Verdana, Arial, Helvetica, sans;
    color:#FFFFFF;
    text-align:center;
    font-size: 13px;
}
.table thead th { /* sobreescreve o Bootstrap.css */
    background-color:#596d9b;
    font: bold 11px Verdana, Arial, Helvetica, sans;
    color:#FFFFFF;
    text-align:center;
    font-size: 13px;
    vertical-align: top;
}
.subtitulo {
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
table.tabela tr td {
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<script type="text/javascript">
	function Redirect(produto, data_i, data_f, mobra) {
		window.open('rel_new_visao_geral_peca.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&mobra=' + mobra,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
	function Redirect1(produto, data_i, data_f) {
		window.open('rel_new_visao_os.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&estado=<? echo $estado; ?>','1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}

	$(function(){
		$.datepickerLoad(Array("data_inicial"));
		$.datepickerLoad(Array("data_final"));

        $("#linha").multiselect({
            selectedText: "Selecionado # de #",
        });
	});

</script>

<p>

<? if (strlen($erro) > 0) { ?>
<table width="700px" border="0" cellpadding="2" cellspacing="0" align="center" class="msg_erro">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<? } ?>

<div class='alert'>
Relatório Visão Geral está disponível de 01/09/2006 até <?echo $data_fim;?>
</div>


<div class='alert'>
Apenas das OS em extratos enviados ao financeiro <br>
(exceto quando marcado o filtro "Apenas Produtos de Locação").
</div>

<div id="msg"  style="display:none;"></div>

<form method="POST" action="<?echo $PHP_SELF?>" name="frm_os_aprovada" class="form-search form-inline tc_formulario">
	<div class='titulo_tabela'>Parâmetros de Pesquisa</div>
	<? echo montaForm($form1,null);?>
	<p style="text-align:center"><br/>
        <button class="btn btn-default" name="btnacao" value='GERAR' type="submit">GERAR</button>
	<p><br/>


</form>
</div>
<!--
<H1>Programa em manutenção, <br> Favor conferir o resultado antes de aplicar. Aguardo sua confirmação. <br> Estamos criando o LINK com Postos x OS </H1>
-->

<?

//flush();
if ($relatorio == "gerar" ) {
	$arquivo = "/var/www/blackedecker/www/download/quebra_produto.csv";
	$fp = fopen ($arquivo,"w");

	//flush();

	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	//$linha          = trim($_POST["linha"]);
    $estado         = trim($_POST["estado"]);
	$marca         = trim($_POST["marca"]);

    $cond_linha     = "1=1";
	$cond_marca     = "1=1";
	if (count($arr_linhas) > 0) {
		if (in_array('347', $arr_linhas)) {
			$sql_join = " JOIN tbl_produto ON tmp_os_visao_geral.produto = tbl_produto.produto";
			$cond_linha = " tbl_produto.familia IN (" . implode(", ", $arr_linhas) . ")";
		}elseif(in_array('0006', $arr_linhas)){
			$sql_join = " JOIN tbl_produto ON tmp_os_visao_geral.produto = tbl_produto.produto";
			$cond_linha = " ( (tbl_produto.linha in (198,200,467) and tbl_produto.familia <>347) or tbl_produto.familia = 347 ) ";
		}else{
            $sql_join = " JOIN tbl_produto ON tmp_os_visao_geral.produto = tbl_produto.produto";
			$cond_linha = " tmp_os_visao_geral.linha IN (" . implode(", ", $arr_linhas) . ") AND tbl_produto.familia <> 347 ";
		}
	}
	
	if(strlen($marca) > 0){
        $sql_join = " JOIN tbl_produto ON tmp_os_visao_geral.produto = tbl_produto.produto";
        $cond_marca = " tbl_produto.marca = $marca";
	}

	$cond_estado    = "1=1";
	$cond_estado2   = "1=1";

	if (strlen ($estado) > 0) {
		$cond_estado  = " tmp_os_visao_geral.estado = '$estado' ";
		$cond_estado2 = " black_antigo_os.estado = '$estado' ";
	}

	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	$x_data_final   = fnc_formata_data_pg($x_data_final);

	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);

	$x_data_inicial = "'" .$x_data_inicial. " 00:00:00'";
	$x_data_final   = "'" .$x_data_final.   " 23:59:59'";

	$mes = substr ($x_data_final,6,2);
	$ano = substr ($x_data_final,1,4);
	/* hd 3024 takashi 18/07/2007 fim */


	$cond_radio = "1=1";

	#--------------------------------------------------------------------------------------
	#
	#    Acertos realizados em Maio de 2007
	#
	#--------------------------------------------------------------------------------------

	$tmp_table			= "tmp_black_unica";
	$tmp_index_table	= "tmp_black_index_unica";

	$tmp_table_garantias= "tmp_black_garantias_unica";

	$res = @pg_query ($con,"drop table $tmp_table;");
	#@pg_query ($con,"DELETE FROM $tmp_table WHERE admin = $login_admin;");
	//echo "drop table $tmp_table;";

	//$res = @pg_query ($con,"drop index $tmp_index_table ;");
	//echo "drop index $tmp_index_table ;";

	$res = @pg_query ($con," DROP TABLE $tmp_table_garantias");
	#pg_query ($con,"DELETE FROM $tmp_table_garantias WHERE admin = $login_admin;");
	//echo "drop index $tmp_table_garantias;";
	//$res = @pg_query ($con," DROP TABLE tmp_black_manut");
	//echo "drop index tmp_black_manut;";
	//$res = @pg_query ($con," DROP TABLE tmp_black_carvao");
	//echo "drop index tmp_black_carvao;";
	//$res = @pg_query ($con," DROP TABLE tmp_black_final");
	//echo "drop index tmp_black_final;";

	// APENAS PRODUTO DE LOCAÇÃO

	//verifica se a tabela existe...
	$res = pg_query("SELECT table_name FROM information_schema.tables WHERE table_name = '$tmp_table';");
	if(pg_num_rows($res) == 1){
		$into_tmp_table = "";
	}else{
		$into_tmp_table = " INTO TABLE {$tmp_table}";

	}

	if($todas == 7  ) {
		$sql = "
			SELECT DISTINCT produto_locador
			  INTO TEMP p
			  FROM tbl_pedido_item
			  JOIN tbl_pedido using(pedido)
			  JOIN tbl_locacao ON tbl_locacao.produto = tbl_pedido_item.produto_locador
			 WHERE fabrica = $login_fabrica;";

		//echo "<br> Todas 7:$sql<br> ";
		$res = pg_query ($con,$sql);

		if($x_data_inicial AND $x_data_final){
			$cond_data = " AND tbl_pedido.data BETWEEN $x_data_inicial AND $x_data_final ";
		}

		$cond_estado    = "1=1";
		$cond_estado2   = "1=1";
		if (strlen ($estado) > 0) {
			$cond_estado  = " tbl_posto_fabrica.contato_estado = '$estado' ";
		}

		$cond_linha     = "1=1";
		if (!empty($arr_linhas)) {
			$cond_linha = " tbl_produto.linha IN (" . implode(", ", $arr_linhas) . ")";
		}

		$sql = "DROP TABLE IF EXISTS tmp_black_unico_pedidos;";
		$res = pg_query($con,$sql);

		$sql = "SELECT DISTINCT tbl_pedido.pedido,
						tbl_pedido_item.produto_locador
					INTO tmp_black_unico_pedidos
					FROM tbl_pedido
					JOIN tbl_pedido_item   ON tbl_pedido.pedido               = tbl_pedido_item.pedido
					JOIN p                 ON tbl_pedido_item.produto_locador = p.produto_locador
					JOIN tbl_produto       ON tbl_pedido_item.produto_locador = tbl_produto.produto     AND tbl_produto.fabrica_i     = $login_fabrica
					JOIN tbl_linha         ON tbl_produto.linha               = tbl_linha.linha         AND tbl_linha.fabrica         = $login_fabrica
					JOIN tbl_posto         ON tbl_pedido.posto                = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto                 = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_pedido.fabrica = $login_fabrica
					AND   tipo_pedido = 94
					AND   tbl_pedido.status_pedido <> 14
					$cond_data
					AND $cond_linha
					AND $cond_marca
					AND $cond_estado";
		$res = pg_query ($con,$sql);
		//echo nl2br($sql);
	}else {

		//IGOR - ADD PARA LISTAR DEPOIS
		//$res = @pg_query ($con,"drop table $tmp_table"."_parametros;");
		@pg_query ("DELETE FROM $tmp_table"."_parametros WHERE admin = $login_admin;");
		if(empty($arr_linhas)){
			$linha = 'null';
        } else {
            $linha = 999;
        }

		$res = pg_query("SELECT table_name FROM information_schema.tables WHERE table_name = '{$tmp_table}_parametros';");
		if(pg_num_rows($res) == 1){
			$into_tmp_table_parametros = "";
		}else{
			$into_tmp_table_parametros = " INTO TABLE {$tmp_table}_parametros ";

		}

		$sql = "
			SELECT
				$todas::INT4             AS todas,
				$x_data_inicial::DATE    AS x_data_inicial,
				$x_data_final::DATE      AS x_data_final ,
				$linha::INT4             AS linha,
				'$estado'::CHAR(2)       AS estado,
				'$descricao_todas'::TEXT AS descricao_todas,
				$login_admin             AS admin
			{$into_tmp_table_parametros}";

		if(empty($into_tmp_table_parametros)){
			$sql = "INSERT INTO  {$tmp_table}_parametros ($sql);";
		}
		//echo $sql;
		$res = pg_query ($con,$sql);


		$sql = "
				SELECT tmp_os_visao_geral.* , $login_admin AS admin
				{$into_tmp_table}
				FROM tmp_os_visao_geral
				$sql_join
				$sql_join2
				WHERE $data_os BETWEEN $x_data_inicial AND $x_data_final
				  AND $cond_linha
				  AND $cond_marca
				  AND $cond_estado";

		if(empty($into_tmp_table)){
			$sql = "INSERT INTO $tmp_table ({$sql});";
		}
		$sql = @pg_query ($con,$sql);
		@pg_query("CREATE INDEX $tmp_index_table ON $tmp_table(os)");




	/*	$sql = "
		SELECT extrato INTO TEMP TABLE tmp_black_extrato
		FROM tbl_extrato
		JOIN tbl_extrato_financeiro USING (extrato)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato_financeiro.data_envio BETWEEN $x_data_inicial AND $x_data_final
		;

		CREATE INDEX tmp_black_extrato_extrato ON tmp_black_extrato (extrato);

		SELECT tbl_os_extra.os
		INTO TEMP TABLE tmp_black_os
		FROM tbl_os_extra
		JOIN tmp_black_extrato USING (extrato)
		;

		CREATE INDEX tmp_black_os_os ON tmp_black_os (os);




		SELECT *
		INTO TABLE $tmp_table
		FROM
			(
				SELECT	tbl_os.os,
						tbl_os.data_digitacao::date AS data,
						tbl_os.produto , tbl_produto.referencia_fabrica,
						tbl_produto.linha,
						tbl_posto.estado,
						tbl_os.pecas AS pecas ,
						tbl_os.mao_de_obra AS mao_de_obra
				FROM tmp_black_os
				JOIN tbl_os             ON tmp_black_os.os  = tbl_os.os
				JOIN tbl_produto		ON tbl_os.produto	= tbl_produto.produto
				JOIN tbl_posto			ON tbl_os.posto		= tbl_posto.posto
			) oss
		WHERE $cond_linha
		AND   $cond_estado;


		CREATE INDEX $tmp_index_table ON $tmp_table (os) ;


		UPDATE $tmp_table SET pecas       = 0 WHERE pecas       IS NULL ;
		UPDATE $tmp_table SET mao_de_obra = 0 WHERE mao_de_obra IS NULL ;

		";

		echo "<br> sql 1:$sql<br> ";
		$res = pg_query ($con,$sql);
	*/



		// AQUI VAI DEIXAR APENAS OS's QUE SAO MANUTEÇÃO
		if($todas == 3  ) {
			$sql = "
			/* Deletando OS SEM TROCA PECAS ou SEM PECAS */

			SELECT DISTINCT $tmp_table.os
			INTO TEMP TABLE x
			FROM $tmp_table
			JOIN tmp_os_item_visao_geral ON $tmp_table.os = tmp_os_item_visao_geral.os
			WHERE tmp_os_item_visao_geral.servico_realizado IN (62, 90,115);

			SELECT $tmp_table.*
			INTO TEMP TABLE x1
			FROM $tmp_table
			JOIN x ON $tmp_table.os = x.os ;

			/*DROP TABLE $tmp_table ;*/
			DELETE FROM $tmp_table WHERE admin = $login_admin;

			INSERT INTO $tmp_table
			SELECT *
			FROM x1 ;

			";

			//echo "<br> Todas 3:$sql<br> ";
			$res = pg_query ($con,$sql);
		}

		// * APENAS QUE SEJAM MANUTENÇÃO*/
		if( $todas == 6 ) {
			$sql = "

			SELECT DISTINCT $tmp_table.os
			INTO TEMP TABLE x
			FROM tmp_os_item_visao_geral
			JOIN $tmp_table     ON $tmp_table.os = tmp_os_item_visao_geral.os
			WHERE tmp_os_item_visao_geral.servico_realizado IN (62, 90,115);

			CREATE INDEX x_os_index ON x(os) ;

			/* APENAS QUE SEJAM MANUTENÇÃO*/
			SELECT DISTINCT $tmp_table.os
			INTO TEMP TABLE xM
			FROM tmp_os_item_visao_geral
			JOIN $tmp_table     ON $tmp_table.os = tmp_os_item_visao_geral.os
			WHERE tmp_os_item_visao_geral.servico_realizado NOT IN (62, 90,115);

			SELECT $tmp_table.*
			INTO TEMP TABLE x1
			FROM $tmp_table
			where $tmp_table.os not in (select os from x);

			/*DROP TABLE $tmp_table;*/
			DELETE FROM $tmp_table WHERE admin = $login_admin;

			INSERT INTO $tmp_table
			SELECT *
			FROM x1 ;

			";

			//echo "<br> Todas 6:$sql<br> ";
			$res = pg_query ($con,$sql);
		}

		 $sql = "
			/* Peças ENVIADAS em Garantia paga 10% */
			SELECT $tmp_table.os
			INTO TEMP TABLE y
			FROM $tmp_table
			JOIN tmp_os_item_visao_geral  ON $tmp_table.os = tmp_os_item_visao_geral.os
			WHERE tmp_os_item_visao_geral.servico_realizado IN (62);

			CREATE INDEX y_os_index ON y(os);

			UPDATE $tmp_table
			SET pecas = round ((pecas * 0.1)::numeric , 2)
			WHERE os IN (SELECT os FROM y);

			SELECT $tmp_table.os
			INTO TEMP TABLE yy
			FROM $tmp_table
			JOIN tmp_os_item_visao_geral  ON $tmp_table.os = tmp_os_item_visao_geral.os
			JOIN tbl_extrato ON $tmp_table.extrato = tbl_extrato.extrato
			WHERE tmp_os_item_visao_geral.servico_realizado IN (90)
			AND   tbl_extrato.data_geracao >= '2010-04-26 00:00:00';

			CREATE INDEX yy_os_index ON yy(os);

			UPDATE $tmp_table
			SET pecas = round (($tmp_table.pecas * case when data_geracao::date < '2012-10-06 ' then tmp_tipo_posto_black.tx_administrativa else tbl_tipo_posto.tx_administrativa end )::numeric , 2)
			FROM tbl_posto_fabrica
			JOIN tbl_tipo_posto         on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN tmp_tipo_posto_black on tbl_posto_fabrica.posto = tmp_tipo_posto_black.posto
			JOIN tbl_extrato ON tbl_posto_fabrica.posto = tbl_extrato.posto and tbl_extrato.fabrica = $login_fabrica
			WHERE os IN (SELECT os FROM yy)
			AND  tbl_posto_fabrica.posto   = $tmp_table.posto
			AND  $tmp_table.extrato = tbl_extrato.extrato
			AND tbl_posto_fabrica.fabrica = $login_fabrica;";
			$res = @pg_query ($con,$sql);
			//echo "<br> GARANTIA :$sql<br> ";
			//echo nl2br ($sql);
		if($todas == 1) {
			//TODAS OS


		}else{
			//Deleta mero desgaste - Atende a condição :exceto mero desgaste(só carvao)
			if ($todas == 2 OR $todas == 3 ) {
				$sql = "
				/* Pesquisa OS somente com troca de carvao */

				SELECT $tmp_table.os
				INTO TEMP TABLE z
				FROM $tmp_table
				JOIN tmp_os_item_visao_geral ON $tmp_table.os = tmp_os_item_visao_geral.os
				WHERE mero_desgaste IS TRUE

				EXCEPT

				SELECT $tmp_table.os
				FROM $tmp_table
				JOIN tmp_os_item_visao_geral ON $tmp_table.os = tmp_os_item_visao_geral.os
				WHERE mero_desgaste IS NOT TRUE;

				CREATE INDEX z_os_index ON z(os);

				DELETE FROM $tmp_table
				WHERE os IN (SELECT os FROM z);";

				//echo "<br> if(Todas==2 OR Todas==3) Todas:$todas:$sql<br> ";
				$res = pg_query ($con,$sql);
			}else{
				//Deleta o que não seja mero desgaste (só carvão)
				if($todas == 5){
					$sql = "
					/* Pesquisa OS somente com troca de carvao */
					SELECT $tmp_table.os
					INTO TEMP TABLE z
					FROM $tmp_table
					JOIN tmp_os_item_visao_geral ON $tmp_table.os = tmp_os_item_visao_geral.os
					WHERE mero_desgaste IS TRUE

					EXCEPT

					SELECT $tmp_table.os
					FROM $tmp_table
					JOIN tmp_os_item_visao_geral ON $tmp_table.os = tmp_os_item_visao_geral.os
					WHERE mero_desgaste IS NOT TRUE;

					CREATE INDEX z_os_index ON z(os);

					/* Diferente da condição acima - Aqui vai deletar tudo que não seja mero desgaste(só carvão)*/
					DELETE FROM $tmp_table
					WHERE os NOT IN (SELECT os FROM z);";
					$res = pg_query ($con,$sql);
					//echo "Todas 5 :<br>$sql";
				}
			}
		}
	}
//todo acertar a verificação
	/* if(pg_num_rows($res) < 1)
		echo '
			<table width="705px" border="0" cellspacing="0" align="center" class="msg_erro" id="ret_msg" style="display:none;">
				<tr>
					<td>Nenhum Resultado Encontrado.</td>
				</tr>
			</table>';
	else */
	  echo "<div id=\"ret_msg\" style=\"display:none;\" class=\"alert alert-success\" >

			Relatório Gerado com Sucesso!

			</div>";

	?>
		<script type="text/javascript">
			$('#ret_msg').appendTo('#msg').css("display","block");
			$("#msg").show();
		</script>
	<?

	$tmp_table			= "tmp_black_unica";
	$tmp_table_garantias= "tmp_black_garantias_unica";

	$sql = "
		SELECT
			to_char(x_data_inicial,'DDMMYYYY')AS x_data_inicial,
			to_char(x_data_final ,'DDMMYYYY')AS x_data_final
		FROM $tmp_table". "_parametros WHERE admin = $login_admin;";

	$res = @pg_query ($con,$sql);

  

  if ((strtoupper($btnacao) == "LISTAR" or strtoupper($btnacao) == "GERAR") and @pg_num_rows($res)>0)  {
	$todas = $_POST["todas"];

	$y_data_inicial = trim(pg_fetch_result($res,0,x_data_inicial));
	$y_data_final   = trim(pg_fetch_result($res,0,x_data_final));

	//echo "<br>sql4: $sql";
	/*// somente mero desgaste e so manutenção
	if($todas == 4) {
		$sql = "
		DROP TABLE $tmp_table ;
		SELECT *
		INTO $tmp_table
		FROM x UNION SELECT * FROM z;";
		$res = pg_query ($con,$sql);
	}*/
	if($todas <> 7){
	$sql = "


    /* Gera arquivos de integração com MFG */
    SELECT rpad(referencia_fabrica,18,' ') AS PRODUTO ,
            lpad(count(*)::text,8,'0') AS QTDE ,
            lpad(replace(sum(to_char(mao_de_obra,'99999999V99')::float)::text,'.','')::text,14,'0') AS MOBRA ,
            lpad(replace(sum(to_char(pecas ,'99999999V99')::float)::text,'.','')::text,14,'0') AS PECAS ,
            lpad(replace(sum(to_char(mao_de_obra + pecas,'99999999V99')::float)::text,'.','')::text,14,'0') AS TOTAL ,
            $y_data_inicial AS INICIO ,
            $y_data_final AS FINAL
    into temp table $tmp_table_garantias
    FROM $tmp_table
    WHERE $tmp_table.admin = $login_admin
    GROUP BY referencia_fabrica, data
    ORDER BY rpad(referencia_fabrica,18,' ');

	SELECT	tbl_produto.descricao AS nome,
			tbl_produto.code_convention,
			tbl_produto.origem,
			tbl_produto.voltagem  AS voltagem,
			tbl_linha.nome        AS linha_nome,
			tbl_produto.referencia_fabrica AS referencia ,
			SUM (xos.pecas)       AS pecas ,
			SUM (xos.mao_de_obra) AS mao_de_obra ,
			COUNT(*)              AS ocorrencia
	FROM $tmp_table xos
	JOIN tbl_produto 	ON xos.produto = tbl_produto.produto
	JOIN tbl_linha 		ON xos.linha = tbl_linha.linha
	WHERE xos.admin = $login_admin
	GROUP BY
			tbl_produto.descricao,
			tbl_produto.code_convention,
			tbl_produto.origem,
			tbl_produto.voltagem,
			tbl_linha.nome,
			tbl_produto.referencia_fabrica
	ORDER BY tbl_produto.referencia_fabrica

		";
	$res = pg_query ($con,$sql);
	} else {
		$sql = "SELECT DISTINCT tbl_produto.descricao AS nome,
						rpad(tbl_produto.referencia_fabrica,18,' ') as referencia,
						tbl_produto.code_convention,
						tbl_produto.origem,
						tbl_produto.voltagem,
						SUM(tbl_pedido_item.qtde * (tbl_pedido_item.preco + ((tbl_pedido_item.preco * tbl_peca.ipi) / 100))) AS pecas,
						tbl_pedido_item.produto_locador AS ocorrencia,
						tbl_linha.nome AS linha_nome,
						null AS mao_de_obra
					FROM tmp_black_unico_pedidos
					JOIN tbl_pedido_item ON tmp_black_unico_pedidos.pedido = tbl_pedido_item.pedido AND tbl_pedido_item.produto_locador = tmp_black_unico_pedidos.produto_locador
					JOIN tbl_produto ON tbl_pedido_item.produto_locador = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica  = $login_fabrica
					GROUP BY tbl_produto.descricao, tbl_produto.referencia_fabrica, tbl_produto.code_convention, tbl_produto.origem,tbl_produto.voltagem, tbl_linha.nome ,tbl_pedido_item.produto_locador
					ORDER BY tbl_produto.descricao";
		$res = pg_query ($con,$sql);
		//echo pg_errormessage($con);
	}
	#echo $sql; exit;


	#}
	/*if($ip == "201.26.18.238"){
		echo $sql;
		exit;
	}*/

	//echo "<br> sql 5:$sql<br> ";
//	flush();

//	flush();
	//echo `cat /tmp/blackedecker/garantia-mensal.txt | sed -e 's/#//g' > /tmp/blackedecker/garantia-mensal-$ano-$mes.txt`;
	if (@pg_num_rows($res) < 1) {
		echo '<div id="ret_msg"  class="alert alert-error">Não Foram encontrados Resultados para esta Consulta.</div>';
?>

<?
	}
	else
	if (@pg_num_rows($res) > 0) {
		echo "<table class='table table-striped table-bordered table-hover table-large' align='center' style='margin-top:5px;'>
		<caption>Relatório gerado para o período: $data_inicial -  $data_final</caption>
		<thead style='font-size:1.2em'>
			<tr>
				<th>Produto</th>
				<th>Referência</th>
				<th>Code Convention</th>
				<th>Classificação</th>
				<th>Ocorrência</th>
				<th>Total MO</th>
				<th>Total PC</th>
				<th>Total Geral</th>
				<th>%</th>
				<th>Linha</th>
			</tr>
		</thead>\n";

		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_fetch_result($res,$x,'ocorrencia');
			$total_mobra      = $total_mobra      + pg_fetch_result($res,$x,'mao_de_obra');
			$total_peca       = $total_peca       + pg_fetch_result($res,$x,'pecas');
			$total_geral      = $total_geral      + pg_fetch_result($res,$x,'mao_de_obra') + pg_fetch_result($res,$x,'pecas');
		}

		$total_final = $total_geral + $total_sedex + $total_avulso;
		echo "<tbody>";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$referencia      = pg_fetch_result($res,$x,'referencia');
			$code_convention = pg_fetch_result($res,$x,'code_convention');
			$origem          = pg_fetch_result($res,$x,'origem');
			$nome            = pg_fetch_result($res,$x,'nome');
			$voltagem        = pg_fetch_result($res,$x,'voltagem');
			$ocorrencia      = pg_fetch_result($res,$x,'ocorrencia');
			$soma_mobra      = pg_fetch_result($res,$x,'mao_de_obra');//esta pegando esse valor na tbl_os
			$soma_peca       = pg_fetch_result($res,$x,'pecas');//esta pegando esse valor na tbl_os_item
			$linha_nome      = pg_fetch_result($res,$x,'linha_nome');
			$soma_total      = $soma_mobra + $soma_peca;

			if ($soma_total > 0 AND $total_geral > 0) {
				$porcentagem = ($soma_total / $total_geral * 100);
			}

			switch ($origem) {
				case 'Imp':
					$origem = 'Importado';
					break;
				case 'Nac':
					$origem = 'Nacional';
					break;

				default:
					# code...
					break;
			}
			if($todas == 7) {
				$sqlo = "SELECT count(1),tbl_pedido_item.pedido, serie_locador
						   FROM tmp_black_unico_pedidos
						   JOIN tbl_pedido_item ON tmp_black_unico_pedidos.pedido = tbl_pedido_item.pedido
						  WHERE tbl_pedido_item.produto_locador=$ocorrencia
				       	  GROUP BY tbl_pedido_item.pedido, serie_locador";
				$reso = pg_query($con,$sqlo);
				$ocorrencia = pg_num_rows($reso);

			}


			echo "<tr>";

			echo "<td align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo substr(pg_fetch_result($res,$x,nome),0,45);
			echo "</font>";
			echo "</td>";

			$x_data_inicial = str_replace ("'","",$x_data_inicial);
			$x_data_final   = str_replace ("'","",$x_data_final);

			$parametro = ($todas == 7) ? "&opcao=$todas" : "";
			echo "<td  align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo "<a href='black_quebra_acumulado_pecas-prov.php?referencia=$referencia&nome=$nome&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado$parametro' target='_blank'>";
			echo $referencia ." - ". $voltagem ;
			echo "</a>";
			echo "</font>";
			echo "</td>";


			echo "<td align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo $code_convention ;
			echo "</font>";
			echo "</td>";

			echo "<td align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo $origem ;
			echo "</font>";
			echo "</td>";


			echo "<td align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo "<a href='black_quebra_acumulado_os-prov.php?referencia=$referencia&nome=$nome&voltagem=$voltagem&data_inicial=$x_data_inicial&data_final=$x_data_final&linha=$linha&estado=$estado&opcao=$todas' target='_blank'>";
			echo $ocorrencia;
			echo "</a>";
			echo "</font>";
			echo "</td>";

			echo "<td align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_mobra,2,",",".");
			echo "</td>";

			echo "<td align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_peca,2,",",".");
			echo "</font>";
			echo "</td>";

			echo "<td align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($soma_total,2,",",".");
			echo "</font>";
			echo "</td>";

			echo "<td align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($porcentagem,2,",",".");
			echo "</font>";
			echo "</td>";
			echo "<td align='center' nowrap >";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo $linha_nome;
			echo "</font>";
			echo "</td>";
			echo "</tr>";

		}
		echo "</tbody><tfoot>";
		echo "<tr class=\"subtitulo\" >";

		echo "<td align='left' colspan='4'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'><b>TOTAL</b></font>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'>$total_ocorrencia</font>";
		echo "</td>";

		echo "<td align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($total_mobra,2,",",".") ."</font>";
		echo "</td>";

		echo "<td align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($total_peca,2,",",".") ."</font>";
		echo "</td>";

		echo "<td align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($total_geral,2,",",".") ."</font>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' size='2'>100%</font>";
		echo "</td>";

		echo "</tr></tfoot>";
		echo "</table>";

		//######################################################################################
		#################### AQUI GERA O ARQUIVO TXT PARA SER FEITO O DOWNLOAD #################
		######################################################################################//

		$data = date ("d-m-Y-H-i");
		echo `mkdir -m 777 /tmp/assist`;
		echo `rm /tmp/assist/black_quebra_acumulado.txt`;
		echo `rm /tmp/assist/black_quebra_acumulado.zip`;
        $arq_zip = __DIR__ . '/../download/black_quebra_acumulado.zip';
		echo `rm {$arq_zip}`;
		$fp = fopen ("/tmp/assist/black_quebra_acumulado.txt","w");

		$dat_inicial = trim($_POST["data_inicial"]);
		$dat_final   = trim($_POST["data_final"]);

		$descricao_estado = $estado;
		if(strlen($estado) == 0){
			$descricao_estado = "Todos";
		}
		if(strlen($descricao_linha) == 0){
			$descricao_linha = "Todas";
		}
		fputs ($fp, "Visão Geral por Produto $dat_inicial - $dat_final  $descricao_todas\r\n");

		fputs ($fp, "$descricao_todas - Linha: $descricao_linha - Estado: $descricao_estado\r\n");
		fputs ($fp, "Produto\tReferência\tCode Convention\tOcorrência\tTotal MO\tTotal PC\tTotal GERAL\t % \r\n");


		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$nome		= substr(pg_fetch_result($res,$x,'nome'),0,45);
			$referencia = pg_fetch_result($res,$x,'referencia');
			$code_convention = pg_fetch_result($res,$x,'code_convention');
			$voltagem   = pg_fetch_result($res,$x,'voltagem');
			$ocorrencia = pg_fetch_result($res,$x,'ocorrencia');
			$soma_mobra = pg_fetch_result($res,$x,'mao_de_obra');//esta pegando esse valor na tbl_os
			$soma_peca  = pg_fetch_result($res,$x,'pecas');//esta pegando esse valor na tbl_os_item
			$linha_nome = pg_fetch_result($res,$x,'linha_nome');
			$soma_total = $soma_mobra + $soma_peca;

			if ($soma_total > 0 AND $total_geral > 0) {
				$porcentagem = ($soma_total / $total_geral * 100);
			}
			$x_soma_mobra   = number_format($soma_mobra,2,",",".");
			$x_soma_peca	= number_format($soma_peca,2,",",".");
			$x_soma_total	= number_format($soma_total,2,",",".");
			//$total_porcentagem	= $total_porcentagem + $porcentagem;
			$x_porcentagem	= number_format($porcentagem,2,",",".");

			fputs($fp,"$nome\t");
			fputs($fp,"$referencia  -  $voltagem\t");
			fputs($fp,"$code_convention\t");

			fputs($fp,"$ocorrencia\t");
			fputs($fp,"$x_soma_mobra\t");
			fputs($fp,"$x_soma_peca\t");
			fputs($fp,"$x_soma_total\t");
			fputs($fp,"$x_porcentagem\t");
			fputs($fp,"$linha_nome");
			fputs($fp,"\r\n");
		}

		$x_tot_mobra = number_format($total_mobra,2,",",".");
		$x_tot_peca  = number_format($total_peca,2,",",".");
		$x_tot_geral = number_format($total_geral,2,",",".");

		fputs ($fp, "TOTAL \t\t$total_ocorrencia \t $x_tot_mobra \t$x_tot_peca \t $x_tot_geral \t 100%\r\n");

		fclose ($fp);
		//flush();
		//gera o zip
		echo `cd /tmp/assist/; rm -rf black_quebra_acumulado.zip; zip -o black_quebra_acumulado.zip black_quebra_acumulado.txt > /dev/null`;

		//move o zip para "/var/www/assist/www/download/"
		echo `mv  /tmp/assist/black_quebra_acumulado.zip {$arq_zip}`;

		//echo `mv  /tmp/assist/black_quebra_acumulado.zip /home/ronald/public_html/assist/download/black_quebra_acumulado.zip`;

######################## FIM DA GERAÇÃO DO RELATÓRIO EM TXT ############################


	//######################################################################################
	################################# GERAÇÃO DO MFG #######################################
	######################################################################################//
			if(strlen($tmp_table_garantias)>0){
				$fp = fopen ("../download/garantia.txt","w");
				//flush();
				//echo $tmp_table_garantias;
				$sql = "SELECT * FROM $tmp_table_garantias ORDER BY produto";
				//echo $sql;
				$resX = pg_query ($con,$sql);
				for ($i = 0 ; $i < pg_num_rows($resX) ; $i++) {
					$produto = pg_fetch_result($resX,$i,'produto');
					$qtde    = pg_fetch_result($resX,$i,'qtde');
					$mobra   = pg_fetch_result($resX,$i,'mobra');
					$pecas   = pg_fetch_result($resX,$i,'pecas');
					$total   = pg_fetch_result($resX,$i,'total');
					$inicio  = pg_fetch_result($resX,$i,'inicio');
					$final   = pg_fetch_result($resX,$i,'final');
					fwrite ($fp,$produto);
					fwrite ($fp,$qtde);
					fwrite ($fp,$mobra);
					fwrite ($fp,$pecas);
					fwrite ($fp,$total);
					// HD 3051 - colocar a data inicial e final do relatório (data de envio do extrato ao financeiro
					fwrite ($fp,$y_data_inicial);
					fwrite ($fp,$y_data_final);
					// HD 3051
					fwrite ($fp,"\r\n");
				}
			}
			$x = fclose($fp);

			echo "<p><center></center></p>";
			//######################################################################################
			############################## FIM GERAÇÃO DO MFG ######################################
			######################################################################################//


			echo "<center>
				\"Relatório Visão Geral\" gerado no formato TXT (Colunas separadas com TABULAÇÃO)<br>
				<a href='../download/black_quebra_acumulado.zip' target='_blank'>Clique aqui </a>para fazer o download do arquivo.<br><br>";

			if($todas <> 7){
				echo "\"Garantia Geral\" (importação para o MFG) <br>
				<a href='../download/garantia.txt' target='_blank'>Clique aqui</a> com o botão direito do mouse para baixar o arquivo!</center>";
			}
		}

  }/*else{ //mensagem de erro q foi trocada de lugar, acima do formulario (~ linha 751)
		if(strtoupper($btnacao) == "LISTAR"){
			echo "<div style='position: absolute; top: 220px; right: 340px;opacity:0.85; filter: alpha(opacity=85); FONT: 10pt Arial ; BORDER-RIGHT: #6699CC 1px solid; BORDER-TOP: #6699CC 1px solid; BORDER-LEFT: #6699CC 1px solid; BORDER-BOTTOM: #6699CC 1px solid; FONT: 10pt Arial; COLOR:#6699CC;BACKGROUND-COLOR: #F2F7FF;' class='Chamados'><center>
			<table>
			<tr>
				<td align='left'><font color='red'><b>É necessário Gerar o Relatório antes de Listar!</b></font></td>
			</tr>
			</table>
			</div>";
		}
	}*/
}

/*
    Retirei no HD 2169244 - está sendo usado?

    $tmp_table_parametros= "$tmp_table". "_parametros";

	$sql = "SELECT
					to_char(x_data_inicial,'dd/mm/yyyy') as x_data_inicial,
					to_char(x_data_final,'dd/mm/yyyy') as x_data_final,
					tbl_linha.nome,
					estado,
					descricao_todas
			FROM $tmp_table_parametros
			WHERE admin = $login_admin
			LEFT JOIN tbl_linha using(linha);";

	$res = @pg_query ($con,$sql);
	if(@pg_num_rows($res)>0){

		$data_inicial   = pg_fetch_result ($res,0,x_data_inicial);
		$data_final     = pg_fetch_result ($res,0,x_data_final);
		$linha          = pg_fetch_result ($res,0,nome);
		$estado         = pg_fetch_result ($res,0,estado);
		$descricao_todas= pg_fetch_result ($res,0,descricao_todas);


	}

echo "<p>";

if (strlen($meu_grafico) > 0) {
	echo $meu_grafico;
}

echo "<p>";*/

include 'rodape.php';
?>
