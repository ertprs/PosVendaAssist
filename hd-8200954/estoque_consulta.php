<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";

include 'token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if(isset($_GET["id"])){
	$fabrica = $_GET["id"];
	$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
			tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica 
		FROM tbl_posto_fabrica 
		WHERE fabrica = $fabrica 
		AND posto     = $cook_posto";
	$res = pg_exec($sql);
	if(pg_numrows($res)>0){
		// setcookie ("cook_posto_fabrica");
		// setcookie ("cook_posto");
		// setcookie ("cook_fabrica");
		// setcookie ("cook_login_posto");
		// setcookie ("cook_login_nome");
		// setcookie ("cook_login_cnpj");
		// setcookie ("cook_login_fabrica");
		// setcookie ("cook_login_fabrica_nome");
		// setcookie ("cook_login_pede_peca_garantia");
		// setcookie ("cook_login_tipo_posto");
		// setcookie ("cook_login_e_distribuidor");
		// setcookie ("cook_login_distribuidor");
		// setcookie ("cook_pedido_via_distribuidor");

		// setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
		// setcookie ("cook_posto",pg_result ($res,0,posto));
		// setcookie ("cook_fabrica",pg_result ($res,0,fabrica));

		add_cookie($cookie_login,"cook_posto_fabrica");
		add_cookie($cookie_login,"cook_posto");
		add_cookie($cookie_login,"cook_fabrica");
		add_cookie($cookie_login,"cook_login_posto");
		add_cookie($cookie_login,"cook_login_nome");
		add_cookie($cookie_login,"cook_login_cnpj");
		add_cookie($cookie_login,"cook_login_fabrica");
		add_cookie($cookie_login,"cook_login_fabrica_nome");
		add_cookie($cookie_login,"cook_login_pede_peca_garantia");
		add_cookie($cookie_login,"cook_login_tipo_posto");
		add_cookie($cookie_login,"cook_login_e_distribuidor");
		add_cookie($cookie_login,"cook_login_distribuidor");
		add_cookie($cookie_login,"cook_pedido_via_distribuidor");

		add_cookie($cookie_login,"cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
		add_cookie($cookie_login,"cook_posto",pg_result ($res,0,posto));
		add_cookie($cookie_login,"cook_fabrica",pg_result ($res,0,fabrica));


		
		set_cookie_login($token_cookie,$cookie_login);

		if(strlen($os)>0) header("Location: os_item.php?os=$os");
		else              header("Location: login.php");
		exit;
	}
}

$aba=3;
include "estoque_cabecalho.php";

$peca = trim ($_GET['peca']);
$busca      = trim ($_GET['busca']);

if (strlen($peca)>0 AND strlen($busca)>0){

	$sql = "SELECT peca,referencia,descricao FROM tbl_peca WHERE peca = $peca";
	$res = pg_exec ($con,$sql);
	$peca      = pg_result ($res,0,peca);
	$descricao = pg_result ($res,0,descricao);

	
/*	echo "<html>";
	echo "<head>";
	echo "<title>Estoque de Pe�as</title>";
	echo '<link type="text/css" rel="stylesheet" href="css/css.css">';
	echo "</head>";
	echo "<body>";
*/

	echo "<span style='align:center'><h2>$referencia - $descricao</h2></span>";

	if ($busca == 'pedido_fabrica'){

		#echo "<p>Somente pedidos sem recebimento</p>"; # HD 13939
		#Comentado: HD 41813

		$sql = "SELECT tbl_pedido.pedido, 
						TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data    ,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
						tbl_pedido.status_pedido                          ,
						tbl_pedido_item.qtde                              ,
						tbl_pedido_item.qtde_faturada                     ,
						tbl_pedido_item.qtde_cancelada
			FROM tbl_pedido_item 
			JOIN tbl_pedido        USING (pedido) 
			JOIN tbl_tipo_pedido   USING (tipo_pedido)
			WHERE (
					( 
						tbl_pedido.posto = $login_posto 
						/*AND ( tbl_pedido.tipo_pedido = 2 OR tbl_pedido.tipo_pedido = 131 )
					) 
					
					OR
					
					(
						tbl_pedido.distribuidor = $login_posto 
						AND ( tbl_pedido.tipo_pedido = 3 OR tbl_pedido.tipo_pedido = 132 )*/
					)
			)
			AND   tbl_pedido.fabrica <> 0
			AND   tbl_pedido_item.peca = $peca
			AND   tbl_pedido.data > CURRENT_DATE - INTERVAL '600 days'
			AND     tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
			ORDER BY tbl_pedido.pedido DESC";
			//hd 36986
			//LIMIT 50 ";

		 # HD 13939
		 /*
		$sql = "SELECT DISTINCT 
						tbl_pedido.pedido, 
						TO_CHAR (tbl_pedido.data,'DD/MM/YYYY')  AS data, 
						tbl_tipo_pedido.descricao               AS tipo_pedido_descricao, 
						tbl_pedido.status_pedido, 
						tbl_pedido_item.qtde, 
						tbl_pedido_item.qtde_faturada, 
						tbl_pedido_item.qtde_cancelada
			FROM tbl_pedido_item 
			JOIN tbl_pedido USING (pedido) 
			JOIN tbl_tipo_pedido USING (tipo_pedido)
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_pedido_item.peca
			LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento 
			WHERE (
				(tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3)OR
				(tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 131) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 132)OR
				(tbl_pedido.fabrica = 10 AND (tbl_pedido.pedido_loja_virtual IS TRUE OR tbl_pedido.posto = 14076))
				)
			AND   tbl_pedido_item.peca = $peca
			AND   tbl_pedido.data > CURRENT_DATE - INTERVAL '600 days'
			AND   (tbl_faturamento_item.faturamento_item IS NULL OR (tbl_faturamento.posto = $login_posto AND  tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.conferencia IS NULL ))
			ORDER BY tbl_pedido.pedido DESC
			LIMIT 50 ";
*/

		#echo $sql ;
		flush();
		#exit;
		
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS REALIZADOS PARA A F�BRICA</center></b>";
			echo "<center><span class='vermelho'>N�o foi efetuado pedido para f�brica</span></center><br>";
		}else{
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='6' align='center'>Pedidos realizados para a F�brica</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde</td>";
			echo "<td>Cancelado</td>";
			echo "<td>Faturado</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='N�mero do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='right' title='Cancelado'>";
				echo pg_result ($res,$i,qtde_cancelada);
				echo "</td>";

				echo "<td align='right' title='Faturado'>";
				echo pg_result ($res,$i,qtde_faturada);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
			flush();
		}
		exit();
	}
	
	if ($busca == 'pedido_transportadora'){

		flush();
		$sql = "
			SELECT tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,peca, SUM (qtde) AS qtde_transp
			INTO TEMP tmp_transp_$login_posto
			FROM tbl_faturamento_item
			JOIN (
				SELECT faturamento,nota_fiscal,emissao
				FROM tbl_faturamento
				WHERE tbl_faturamento.posto   = $login_posto
				AND   tbl_faturamento.conferencia  IS NULL
				AND   tbl_faturamento.cancelada    IS NULL
				AND   tbl_faturamento.distribuidor IS NULL
			) fat ON tbl_faturamento_item.faturamento = fat.faturamento
			WHERE tbl_faturamento_item.peca = $peca
			GROUP BY tbl_faturamento_item.pedido,fat.nota_fiscal,fat.emissao,tbl_faturamento_item.peca;

			CREATE INDEX tmp_transp_peca_$login_posto ON tmp_transp_$login_posto(peca);

			SELECT	transp.nota_fiscal,
					TO_CHAR (transp.emissao,'DD/MM/YYYY')  AS emissao,
					transp.pedido,
					transp.qtde_transp, 
					TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data    ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
					tbl_pedido.status_pedido                          ,
					tbl_peca.peca                                     ,
					tbl_peca.referencia                               ,
					tbl_peca.descricao                                ,
					tbl_posto_estoque.qtde                            ,
					para.referencia                AS para_referencia ,
					para.descricao                 AS para_descricao  ,
					tbl_posto_estoque_localizacao.localizacao         ,
					(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco 
			FROM tmp_transp_$login_posto transp
			JOIN tbl_peca                 USING (peca)
			LEFT JOIN tbl_pedido          USING (pedido)
			LEFT JOIN tbl_tipo_pedido     USING (tipo_pedido)
			LEFT JOIN tbl_posto_estoque               ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao   ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			LEFT JOIN tbl_depara                      ON tbl_peca.peca        = tbl_depara.peca_de
			LEFT JOIN tbl_peca para                   ON tbl_depara.peca_para = para.peca
			WHERE (tbl_peca.peca = $peca OR para.peca = $peca)
			ORDER BY tbl_peca.descricao";
			#echo nl2br($sql);
			#exit;
		$res = pg_exec ($con,$sql);

		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS NA TRANSPORTADORA</center></b>";
			echo "<center><span class='vermelho'>N�o registro encontrado!</span></center><br>";
		}else{
			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='6' align='center'>Pedidos realizados para a F�brica</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>NF</td>";
			echo "<td>Data NF</td>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde Transp</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "#cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor'>";

				echo "<td title='Nota Fiscal'>";
				echo pg_result ($res,$i,nota_fiscal);
				echo "</td>";
				
				echo "<td title='Data da Nota Fiscal'>";
				echo pg_result ($res,$i,emissao);
				echo "</td>";
				
				echo "<td title='N�mero do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde_transp);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
			flush();
		}
		exit();
	}

	if ($busca=='pedido_postos'){

		echo "<p>Somente pedidos pendentes</p>";

		#---------- Postos que fizeram pedido desta pe�a e que est� pendente (n�o atendida e nem embarcada) ------------#
		$sql = "SELECT	tbl_posto_fabrica.codigo_posto, 
						tbl_posto.nome, 
						tbl_pedido.pedido, 
						TO_CHAR (tbl_pedido.data,'DD/MM/YYYY') AS data, 
						tbl_tipo_pedido.descricao              AS tipo_pedido_descricao, 
						tbl_pedido.status_pedido, 
						sum (tbl_pedido_item.qtde) as qtde, 
						sum(tbl_pedido_item.qtde_faturada_distribuidor) as qtde_faturada_distribuidor, 
						sum(tbl_pedido_item.qtde_cancelada) as qtde_cancelada
				FROM tbl_pedido_item 
				JOIN tbl_pedido        USING (pedido) 
				JOIN tbl_peca          USING (peca)
				JOIN tbl_tipo_pedido   USING (tipo_pedido)
				JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_pedido.fabrica = tbl_posto_fabrica.fabrica
				JOIN tbl_posto         ON tbl_pedido.posto = tbl_posto.posto
				WHERE tbl_pedido.distribuidor = $login_posto
				AND   tbl_pedido.fabrica <> 0
				AND   tbl_peca.peca = $peca
				GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_pedido.pedido, TO_CHAR (tbl_pedido.data,'DD/MM/YYYY'),tbl_tipo_pedido.descricao,tbl_pedido.status_pedido
				HAVING sum(qtde) > sum(qtde_cancelada) + sum(qtde_faturada_distribuidor)
				ORDER BY tbl_pedido.pedido DESC";
		//ADICIONEI: AND qtde > qtde_cancelada + qtde_faturada_distribuidor - HD  13939
		$res = pg_exec ($con,$sql);
		if(pg_numrows ($res)==0){
			echo "<br><center><b>PEDIDOS REALIZADOS PELOS POSTOS</center></b>";
			echo "<center><span class='vermelho'>N�o existe pedido pendente (Verifique se n�o existe pedido no embarque!)</span></center><br>";
		}else{

			echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td colspan='8' align='center'>Pedidos realizados pelos Postos</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
			echo "<td>C�digo</td>";
			echo "<td>Posto</td>";
			echo "<td>Pedido</td>";
			echo "<td>Data</td>";
			echo "<td>Tipo</td>";
			echo "<td>Qtde</td>";
			echo "<td>Cancelado</td>";
			echo "<td>Faturado</td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$cor = "cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor' style='font-size:11px'>";

				echo "<td title='C�digo do posto'>";
				echo pg_result ($res,$i,codigo_posto);
				echo "</td>";

				echo "<td title='Nome do posto'>";
				echo pg_result ($res,$i,nome);
				echo "</td>";

				echo "<td title='N�mero do pedido'>";
				echo pg_result ($res,$i,pedido);
				echo "</td>";

				echo "<td title='Data'>";
				echo pg_result ($res,$i,data);
				echo "</td>";

				echo "<td title='Tipo'>";
				echo pg_result ($res,$i,tipo_pedido_descricao);
				echo "</td>";

				echo "<td align='right' title='Quantidade'>";
				echo pg_result ($res,$i,qtde);
				echo "</td>";

				echo "<td align='right' title='Cancelado'>";
				echo pg_result ($res,$i,qtde_cancelada);
				echo "</td>";

				echo "<td align='right' title='Faturado'>";
				echo pg_result ($res,$i,qtde_faturada_distribuidor);
				echo "</td>";

				echo "</tr>";
			}

			echo "</table>";
		}
		exit();
	}
	exit();
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>



<? include "distrib/javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1] + " - " + row[2];
	}
	
	function formatResult(row) {
		return row[0];
	}


	$("#descricao").autocomplete("<?echo 'peca_consulta_ajax_teste.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[2];} 
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[1]) ;
		$("#descricao").val(data[2]) ;
	});

});


var ok = false;
function checkaTodos() {
	f = document.frm_estoque_lista;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}

function abrePopup(url,largura){
	data = new Date();
	window.open(url, 'Consulta'+data.getSeconds(), 'width='+largura+',height=500,toolbar=0,resizable=1,scrollbars=1');
}

</script>

<div id='dest'>
	Estoque de Pe�as
</div>
<p>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

<table>
<!--
	<tr>
		<td >F�brica</td>
		<td align='left' colspan='6'>
		<?
		echo "<select style='width:200px;' name='fabrica' id='fabrica' ";
		$fabrica = $_GET["fabrica"];
//		if(strlen($fabrica)>0) echo " disabled ";
//		else echo "onChange='window.location=\"$PHP_SELF?fabrica=\"+this.value'";
		echo ">";
		echo "<option value=''>Selecionar</option>";
			$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN (3,25,51) ORDER BY nome";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($x = 0; $x < pg_numrows($res);$x++) {
					$aux_fabrica = pg_result($res,$x,fabrica);
					$aux_nome    = pg_result($res,$x,nome);
					echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
				}
			}
		echo "</select>";
		?>
		</td>
	</tr>
	<tr>
-->
		<td>Refer�ncia da Pe�a</td>
		<td><input type='text' size='10' name='referencia' id='referencia' class="frm"></td>
		<td>Descri��o da Pe�a</td>
		<td><input type='text' size='20' name='descricao'   id='descricao' class="frm"></td>
		<td>Localiza��o</td>
		<td colspan='3'><input type='text' size='10' name='localizacao' class="frm"></td>
	</tr>
	<tr>
		<td align='center' colspan='4'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
	</tr>
</table>
<br>



</form>
</center>


<?

flush();

$referencia   = trim ($_POST['referencia']);
$descricao    = trim ($_POST['descricao']);
$localizacao  = trim ($_POST['localizacao']);

// FAZ A CONSULTA COM A PELA DESCRICAO, SOMENTE SE ELA TIVER + Q 2 STRING
/*
if (strlen ($descricao) > 2) {
	$sql = "SELECT	tbl_peca.referencia, 
			tbl_peca.descricao, 
			tbl_peca.ipi, 
			tbl_posto_estoque.qtde, 
			0 AS qtde_fabrica, 
			0 AS qtde_transp, 
			0 AS qtde_embarcada, 
			para.referencia AS para_referencia, 
			para.descricao AS para_descricao, 
			tbl_posto_estoque_localizacao.localizacao, 
			(
				SELECT tbl_tabela_item.preco
				FROM   tbl_tabela_item
				WHERE  tbl_tabela_item.peca = tbl_peca.peca
				AND    tbl_tabela_item.tabela IN (SELECT tabela_posto FROM tbl_posto_linha WHERE posto = $login_posto)
				 ORDER BY preco DESC LIMIT 1
			) AS preco
		FROM       tbl_peca PE
		LEFT JOIN tbl_posto_estoque             ON PE.peca = tbl_posto_estoque.peca             AND tbl_posto_estoque.posto = $login_posto
		LEFT JOIN tbl_posto_estoque_localizacao ON PE.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_depara                    ON PE.peca = tbl_depara.peca_de
		LEFT JOIN tbl_peca           para       ON tbl_depara.peca_para = para.peca
		WHERE ( PE.descricao LIKE '%$descricao%' OR para.descricao LIKE '%$descricao%' )
		AND     PE.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao";

	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res)==0){
		echo "<center><b><span class='vermelho'>$descricao </span>- NENHUM PRODUTO COM ESSA DESCRI��O FOI ENCONTRADO</center></b><br>";
	}
}
*/
//$fabrica = $_POST["fabrica"];
//if(strlen($fabrica)==0) $fabrica = $login_fabrica;


if (strlen ($referencia) > 2) {
	$sqlx = "SELECT peca,fabrica 
			FROM tbl_peca
			WHERE upper(referencia) = upper('$referencia')
			order by fabrica";

	$resx = pg_exec ($con,$sqlx);

	if(pg_numrows($resx)==0) {
		echo "Pe�a com a ref�ncia $referencia n�o encontrada";
		exit;
	}

	for ($x = 0; $x < pg_numrows($resx); $x++) {

		$peca    = pg_result($resx,$x,peca);
		$fabrica = pg_result($resx,$x,fabrica);

		//hd 36986 - comentei condi��es de posto
		$sql = "
			SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica 
			INTO TEMP tmp_ce1_$login_posto_$x
			FROM tbl_pedido_item 
			JOIN tbl_pedido USING (pedido)
			WHERE tbl_pedido.fabrica <> 0
			AND (
			(tbl_pedido.posto        = $login_posto /*AND tbl_pedido.tipo_pedido = 2) OR 
			(tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3) OR
			(tbl_pedido.posto        = $login_posto AND tbl_pedido.tipo_pedido = 131) OR 
			(tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 132*/) 
			)
			AND     tbl_pedido.fabrica   = $fabrica
			AND     tbl_pedido_item.peca = $peca
			AND     (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido IS NULL)
			AND     tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
			GROUP BY tbl_pedido_item.peca;

			CREATE INDEX tmp_ce1_peca_$login_posto_$x ON tmp_ce1_$login_posto_$x(peca);

			SELECT peca, SUM (qtde) AS qtde_transp
			INTO TEMP tmp_ce2_$login_posto_$x
			FROM tbl_faturamento_item
			JOIN (
				SELECT faturamento
				FROM tbl_faturamento
				WHERE tbl_faturamento.posto   = $login_posto
				AND   tbl_faturamento.fabrica = $fabrica
				AND   tbl_faturamento.conferencia  IS NULL
				AND   tbl_faturamento.cancelada    IS NULL
				AND   tbl_faturamento.distribuidor IS NULL
			) fat ON tbl_faturamento_item.faturamento = fat.faturamento
			WHERE tbl_faturamento_item.peca = $peca
			GROUP BY tbl_faturamento_item.peca;

			CREATE INDEX tmp_ce2_peca_$login_posto_$x ON tmp_ce2_$login_posto_$x(peca);

			SELECT peca, SUM (qtde) AS qtde_embarcada
			INTO TEMP tmp_ce3_$login_posto_$x
			FROM tbl_embarque_item
			JOIN tbl_embarque USING (embarque)
			WHERE tbl_embarque.faturar   IS NULL
			AND   tbl_embarque_item.peca = $peca
			GROUP BY tbl_embarque_item.peca;

			CREATE INDEX tmp_ce3_peca_$login_posto_$x ON tmp_ce3_$login_posto_$x(peca);

			SELECT	tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				tbl_posto_estoque.qtde, 
				fabrica.qtde_fabrica, 
				transp.qtde_transp, 
				embarque.qtde_embarcada, 
				para.referencia AS para_referencia, 
				para.descricao AS para_descricao, 
				tbl_posto_estoque_localizacao.localizacao, 
				tbl_fabrica.nome,
				tbl_peca.peca,
				(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco 
			FROM   tbl_peca 
			LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			LEFT JOIN tbl_depara                    ON tbl_peca.peca        = tbl_depara.peca_de
			LEFT JOIN tbl_peca para                 ON tbl_depara.peca_para = para.peca
			LEFT JOIN tmp_ce1_$login_posto_$x fabrica  ON tbl_peca.peca        = fabrica.peca
			LEFT JOIN tmp_ce2_$login_posto_$x transp   ON tbl_peca.peca        = transp.peca
			LEFT JOIN tmp_ce3_$login_posto_$x embarque ON tbl_peca.peca        = embarque.peca
			JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica
			WHERE (tbl_peca.peca = $peca OR para.peca = $peca)
			AND    tbl_peca.fabrica = $fabrica
			ORDER BY tbl_peca.descricao";
		//echo $sql; 
		$res = pg_exec ($con,$sql);
		flush();
		if(pg_numrows ($res)==0){
			echo "<center><b><span class='vermelho'>$referencia </span>- C�DIGO DE PE�A N�O CADASTRADO</center></b><br>";
			exit;
		}
	}
}

// FAZ A CONSULTA COM A PELA LOCALIZACAO, SOMENTE SE ELA TIVER + QUE 2 STRING
// colocado por Fabio 22/11/2006
if (strlen ($localizacao) > 2) {

	if (strlen($localizacao)==4 AND strtoupper($localizacao{0})=='P' AND strtoupper($localizacao{1})=='T'){
		$localizacao = '%'.$localizacao{2}.$localizacao{3};
	}
	else{
		$localizacao = "%$localizacao%";
	}
	$sql = "SELECT	tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_posto_estoque.qtde, 
					0 AS qtde_fabrica, 
					0 AS qtde_transp, 
					0 AS qtde_embarcada, 
					para.referencia AS para_referencia, 
					para.descricao AS para_descricao, 
					tbl_posto_estoque_localizacao.localizacao, 
					tbl_fabrica.nome,
					(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco
		FROM   tbl_peca
		LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
		LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
		JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica
		WHERE  (tbl_posto_estoque_localizacao.localizacao ILIKE '$localizacao')
		/*AND    tbl_peca.fabrica = $fabrica*/
		ORDER BY tbl_peca.descricao"; 
	//echo $sql;
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res)==0){
		echo "<center><b><span class='vermelho'>$localizacao </span>- NENHUM PRODUTO COM ESSA DESCRI��O FOI ENCONTRADO</center></b><br>";
	}
}

if($_GET['eti']==1){
	echo "<br><center>";
	echo "<form name='frm_lista' action='estoque_consulta_imprimir.php' method='POST' target='_blank'>";
	echo "<input type='hidden' name='lista' value='sim'>";
	echo "<br><label id='lista_referencias'>Digite as Etiquetas a Serem Impressas</label><br><textarea name='lista_referencias' cols='10' rows='10'></textarea>";
	echo "<br><img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick=\"javascript: document.frm_lista.submit();\" style='cursor: hand' alt='Clique aqui p/ localizar o n�mero de s�rie'>";
	echo "</form></center>";
} else {
	echo "<br><center><b><a href='$PHP_SELF?eti=1' >IMPRIMIR ETIQUETAS INDIVIDUAIS</a></b></center><br>";
}

if (strlen ($descricao) > 2 or strlen ($referencia) > 2 or strlen($localizacao)>2) {
	if (strlen ($localizacao) > 2 AND (pg_numrows ($res)>0)){
		$localizacao = strtoupper($localizacao);
		echo "<center><b><span class='vermelho'>$localizacao</span> - <a href='javascript:document.frm_estoque_lista.submit()' >CLIQUE AQUI PARA ABRIR A TELA DE IMPRESS�O</a></b></center><br>";
	}
	if (strlen ($localizacao) > 2){
		echo "<form name='frm_estoque_lista' action='estoque_consulta_imprimir.php' method='post' target='_blank'>";
	}
	
	echo "<br><table align='center' border='0' cellspacing='1' cellpadding='5'>";
	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:12px' align='center'>";

	if (strlen ($localizacao) > 2){
		echo "<td align='center'><a href='javascript:checkaTodos()'>Todos</td>";
	}
	
	echo "<td>Fabrica</td>";
	echo "<td>Refer�ncia</td>";
	echo "<td>Descri��o</td>";
	echo "<td>Dispon�vel</td>";
	echo "<td>Embarcado</td>";
	echo "<td>F�brica</td>";
	echo "<td>Transp.</td>";
	echo "<td>Localiza��o</td>";
	echo "<td>Pre�o</td>";
	echo "</tr>";

	for ($xx = 0; $xx < pg_numrows($res); $xx++) {
	
		$cor = "cccccc";
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#eeeeee';
		
		echo "<tr bgcolor='$cor'>";

		if (strlen ($localizacao) > 2){
			echo "<td align='center'><input type='checkbox' name='pecas_$i' value='".trim (pg_result ($res,$i,referencia))."'></td>";
		}

		echo "<td>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,referencia);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,descricao);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_embarcada);
		echo "</td>";

		$qtde_fabrica = pg_result ($res,$i,qtde_fabrica);
		if ($qtde_fabrica < 0) $qtde_fabrica = 0;

		echo "<td align='right'>&nbsp;";
		echo $qtde_fabrica;
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_transp);
		echo "</td>";

		echo "<td align='center'>&nbsp;";
		echo (strlen(pg_result ($res,$i,localizacao)) == 0) ? "SL" : pg_result ($res,$i,localizacao);
		echo "</td>";

		$preco = pg_result ($res,$i,preco) * (1 + (pg_result ($res,$i,ipi) / 100)) ;
		echo "<td align='left'>&nbsp;";
		echo number_format ($preco,2,",",".");
		echo "</td>";

		echo "</tr>";
	}


	echo "</table>";
	if (strlen ($localizacao) > 2){
		echo "<input type='hidden' name='qtde' value='$i'>";
		echo "</form>";
	}
	
}
flush();
?>


<?
#---------- Pedidos desta pe�a para a F�brica ------------#

if (strlen ($referencia) > 2) {
	#Fabio: fiz esta separa��o para

	echo "<br><br><center><span><a  href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_fabrica',600)\" targe='_blank'>Pedidos realizados para a F�brica</a></span></center><br>";

	echo "<br><center><span><a  href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_transportadora',600)\" targe='_blank'>Pedidos na Transportadora</a></span></center><br>";

	echo "<br><center><span><a href=\"javascript:abrePopup('$PHP_SELF?peca=$peca&busca=pedido_postos',750)\" >Postos que fizeram pedido desta pe�a, e que n�o est�o atendida e nem embarcada</a></span></center><br><br>";

}

if (isset($_POST['btn_acao']) AND (strlen ($descricao) < 3 AND strlen ($referencia) < 3 AND strlen ($localizacao) < 3)) {
	echo "<br><br><center><b class='vermelho'>DIGITE NO M�NIMO 3 CARACTERES PARA A BUSCA!</center></b>";
}


?>


<? include "login_unico_rodape.php"; ?>