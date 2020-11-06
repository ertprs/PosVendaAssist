<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

$distrib_lote = $_POST['distrib_lote'];
if (strlen($distrib_lote) == 0) $distrib_lote = $_GET['distrib_lote'];

$distrib_lote_salton = $_POST['distrib_lote_salton'];
if (strlen($distrib_lote_salton) == 0) $distrib_lote_salton = $_GET['distrib_lote_salton'];

$lote = (strlen($distrib_lote) > 0) ? $distrib_lote : $distrib_lote_salton;

$alterar = $_POST['alterar'];

if($alterar == 'sim') {
	$codigo_posto = $_POST['posto'];
	$nf           = $_POST['nf'];
	$lote         = $_POST['lote'];
	$valor        = trim($_POST['valor']);
	$valor        = str_replace(".","",$valor);
	$valor        = str_replace(",",".",$valor);
	$tipo         = $_POST['tipo'];

	if(empty($valor)) {
		$msg_erro = "Preenche informação para ser alterado";
	}else{
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $lote";
		$res = pg_exec ($con,$sql);
		$fabrica = pg_result ($res,0,0);

		$sql = " UPDATE tbl_distrib_lote_posto
				SET $tipo = '$valor'
			FROM  tbl_distrib_lote
			WHERE tbl_distrib_lote_posto.distrib_lote = $lote
			AND   tbl_distrib_lote.distrib_lote = tbl_distrib_lote_posto.distrib_lote
			AND tbl_distrib_lote_posto.posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo_posto' AND fabrica=$fabrica)
			AND tbl_distrib_lote_posto.nf_mobra = '$nf'
			AND tbl_distrib_lote.fechamento ISNULL ";
		$res = pg_query($con,$sql);
		$qtde_alterada = pg_affected_rows($res);
		$msg_erro = pg_last_error($con);
		if(empty($msg_erro)) {
			if($qtde_alterada == 0) {
				$msg_erro = "Nenhum registro alterado";
			}elseif($qtde_alterada == 1){
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
	echo (empty($msg_erro)) ? "OK" : $msg_erro;
	die;
}


$nf_mobra = $_POST['nf_mobra'];
if (strlen($nf_mobra) == 0) $nf_mobra = $_GET['nf_mobra'];


$excluir = $_GET['excluir'];

if (strlen ($lote) > 0) {
	$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $lote";
	$res = pg_exec ($con,$sql);
	$fabrica = pg_result ($res,0,0);
}

if (strlen($excluir) > 0) {

	$res = pg_exec ($con,"BEGIN;");
	$sql = "DELETE FROM tbl_distrib_lote_posto
			WHERE distrib_lote = $lote
			AND posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica)
			AND nf_mobra = '$nf_mobra'";
	$res = pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$sql = " SELECT DISTINCT extrato
				 FROM tbl_os_extra
				 WHERE os IN (
					SELECT os
					FROM tbl_distrib_lote_os
					WHERE nota_fiscal_mo='$nf_mobra'
					AND   distrib_lote=$lote);";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			
		}
		$sql = "DELETE FROM tbl_distrib_lote_os
				WHERE distrib_lote = $lote
				AND os IN (SELECT os FROM tbl_os WHERE posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$excluir' AND fabrica=$fabrica) 
				AND fabrica=$fabrica)
				AND nota_fiscal_mo='$nf_mobra'";
		$res = pg_exec ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK;");
		echo "$msg_erro";
	} else {
		$res = pg_exec ($con,"COMMIT;");
	}
}
?>
<? include 'menu.php' ?>
<body>

<script type="text/javascript" src="../javascripts/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='js/jquery-1.6.1.min.js'></script>
<script language='javascript' src='../admin/js/jquery.editable-1.3.3.js'></script>
<script language='javascript'>

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaPosto(http) {
	var f= document.getElementById('f1');
	f.style.display='inline';
	if (http.readyState == 1) {
		f.innerHTML = "<CENTER><BR><BR><BR><BR><BR>&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' ></CENTER>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					f.innerHTML = results[1];
				}else{
					f.innerHTML = "<h4>Ocorreu um erro</h4>"+results[1] +"teste -"+results[0] ;
				}
			}else{
				alert ('Posto nao processado');
			}
		}
	}
}

function exibirPosto() {
	var codigo_posto= document.getElementById('codigo_posto').value;
	url = "lote_conferencia_retorna_posto_ajax.php?ajax=sim&codigo_posto="+escape(codigo_posto) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPosto(http) ; } ;
	http.send(null);
}

function alteraLote(posto,nf,lote,valor,tipo,tag,anterior){
	$.post(
		'<?=$PHP_SELF?>',
		{
			posto:posto,
			nf:nf,
			lote:lote,
			valor:valor,
			tipo:tipo,
			alterar:'sim'
		},
		function(data){
			if (data == 'OK'){
			
				$("div.mensagem").css('background-color','#00ff00').html('Alterado Com Sucesso').show('slow').delay(2000).hide('3000');
			}else{
				if (!$.browser.msie){
					$("div.mensagem").css('position','fixed')
				}
				
				$("div.mensagem").css('background-color','red').html(data).show('slow').delay(2000).hide('3000');
				$(tag).html(anterior);
			}
		}
	)
}

$(document).ready(function() {
	$("div[rel='total_sedex']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		editClass:'{required:true,minlength:3}',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'total_sedex',this,valor.previous);

		}
	});

	$("div[rel='identificador_objeto']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		editClass:'{required:true,minlength:3}',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'identificador_objeto',this,valor.previous);

		}
	});
	
	$("div[rel='recebimento_lote']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'data_recebimento_lote',this,valor.previous);
		}
	});

	$("div[rel='nf_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'nf_mobra',this,valor.previous);
		}
	});

	$("div[rel='data_nf_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'data_nf_mobra',this,valor.previous);
		}
	});

	$("div[rel='total_nota_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'valor_mobra',this,valor.previous);
		}
	});


})

</script>

<style type="text/css">
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}


* html div.mensagem{
	position:expression(eval(document.compatMode && document.compatMode!='CSS1Compat') ? 'absolute':'fixed' );
	top:expression(eval(document.compatMode && document.compatMode!='CSS1Compat') ? document.body.scrollTop:'30px'  );
	color: white;
}
</style>

<?
echo "<div class='mensagem'></div>";

echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";

$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		WHERE  tbl_distrib_lote.fabrica not in (7,81) 
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);

echo "<table class ='table_line' width='100%' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'>
		<thead>
		<tr bgcolor='#aaaadd'  background='../admin/imagens_admin/azul.gif'>
			<td colspan='100%'> RELATÓRIO DE LOTES</td>
		</tr>
		</thead>
";
echo "<tr>";
echo "<td nowrap>Conferência por Lote Gama Italy e Britânia<br>";

echo "<select name='distrib_lote' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Imprimir Lote'>\n";
echo "</td>";
echo "<td nowrap>Conferência por Lote Salton<br>";
$sql = "SELECT distrib_lote, LPAD (lote::text,6,'0') AS lote, TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
		FROM tbl_distrib_lote
		WHERE  tbl_distrib_lote.fabrica =81
		ORDER BY distrib_lote DESC";
$res = pg_exec ($con,$sql);


echo "<select name='distrib_lote_salton' size='1'>\n";
echo "<option></option>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
}
echo "</select>\n";
echo "<input type='submit' name='btn_acao' value='Imprimir Lote'>\n";
echo "</td>";
echo "</tr>";
echo "</form>";

if (strlen ($lote) > 0 ) {


$sql = "SELECT tbl_os.posto, 
			tbl_os.os,
			tbl_os.produto,
			tbl_distrib_lote_os.nota_fiscal_mo,
			tbl_distrib_lote_os.distrib_lote,
			tbl_os_extra.extrato
			into temp table t_1
			FROM tbl_os
			JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			WHERE tbl_distrib_lote_os.distrib_lote = $lote
			AND tbl_os.posto <> 6359;

			CREATE INDEX t_1_posto_index ON t_1(posto);
			CREATE INDEX t_1_os_index ON t_1(os);
			CREATE INDEX t_1_produto_index ON t_1(produto);
			CREATE INDEX t_1_extrato_index ON t_1(extrato);

			SELECT t_1.distrib_lote, t_1.posto, 
			t_1.nota_fiscal_mo,
			tbl_produto.mao_de_obra, 
			SUM (tbl_produto.mao_de_obra) AS mobra_total, 
			t_1.extrato,
			COUNT (t_1.os) AS qtde_os
			INTO TEMP TABLE tmp_tab1
			FROM t_1
			JOIN tbl_produto ON tbl_produto.produto = t_1.produto 
			GROUP BY t_1.distrib_lote, t_1.posto , t_1.nota_fiscal_mo, tbl_produto.mao_de_obra,t_1.extrato;

			CREATE INDEX tmp_tab1_posto_index ON tmp_tab1(posto);

			SELECT t_1.distrib_lote, t_1.posto, t_1.nota_fiscal_mo, COUNT (t_1.os) AS med_qtde_os, 
			t_1.extrato
			into temp table tmp_tab2
			FROM t_1
			GROUP BY t_1.distrib_lote, t_1.posto, t_1.nota_fiscal_mo, 
			t_1.extrato;

			CREATE INDEX tmp_tab2_posto_index ON tmp_tab2(posto);


			SELECT t_1.posto, t_1.nota_fiscal_mo, SUM (tbl_os_item.qtde) AS med_qtde_pecas,t_1.extrato
			into temp table tmp_tab3
			FROM t_1
			LEFT JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			GROUP BY t_1.posto, t_1.nota_fiscal_mo, 
			t_1.extrato;

			CREATE INDEX tmp_tab3_posto_index ON tmp_tab3(posto);

			SELECT t_1.posto, t_1.nota_fiscal_mo, SUM (tbl_os_item.qtde * tbl_tabela_item.preco) AS med_custo, 
			t_1.extrato
			into temp table tmp_tab4
			FROM t_1
			LEFT JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_produto ON t_1.produto = tbl_produto.produto
			LEFT JOIN tbl_posto_linha ON t_1.posto = tbl_posto_linha.posto AND tbl_produto.linha = tbl_posto_linha.linha
			LEFT JOIN tbl_tabela_item ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
			GROUP BY t_1.posto, t_1.nota_fiscal_mo, 
			t_1.extrato;

			CREATE INDEX tmp_tab4_posto_index ON tmp_tab4(posto);

			SELECT distinct on (tbl_posto.nome, tmp_tab1.nota_fiscal_mo,tmp_tab1.extrato) 
			tmp_tab2.distrib_lote,
			tbl_posto_fabrica.codigo_posto ,
			tbl_posto.nome ,
			tbl_posto_fabrica.banco ,
			tbl_posto_fabrica.agencia ,
			tbl_posto_fabrica.conta ,
			tmp_tab2.med_qtde_os ,
			tmp_tab3.med_qtde_pecas ,
			tmp_tab4.med_custo ,
			tmp_tab1.extrato,
			tmp_tab1.qtde_os ,
			tmp_tab1.mao_de_obra ,
			tmp_tab1.mobra_total ,
			tmp_tab1.nota_fiscal_mo
			into temp table tmp_tab5
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN tmp_tab1 ON tbl_posto.posto = tmp_tab1.posto 
			JOIN tmp_tab2 ON tbl_posto.posto = tmp_tab2.posto and tmp_tab2.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab2.extrato
			JOIN tmp_tab3 ON tbl_posto.posto = tmp_tab3.posto and tmp_tab3.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab3.extrato
			JOIN tmp_tab4 ON tbl_posto.posto = tmp_tab4.posto and tmp_tab4.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab4.extrato;

			/*ALTER TABLE tmp_tab5 add column extrato integer;

			update tmp_tab5 set extrato = tbl_os_extra.extrato
			FROM tbl_os_extra
			JOIN tbl_os using(os)
			JOIN tbl_distrib_lote_os ON tbl_distrib_lote_os.os = tbl_os_extra.os
			JOIN tbl_posto_fabrica   ON tbl_os.posto = tbl_posto_fabrica.posto
			WHERE tbl_distrib_lote_os.distrib_lote = tmp_tab5.distrib_lote
			AND tmp_tab5.codigo_posto   = tbl_posto_fabrica.codigo_posto
			AND tmp_tab5.nota_fiscal_mo = tbl_distrib_lote_os.nota_fiscal_mo;*/

			SELECT * from tmp_tab5 order by distrib_lote, nome, extrato, nota_fiscal_mo;";

	#echo nl2br($sql); 
	#exit;
	$res = pg_exec ($con,$sql);
	//echo "sql: $sql";


	$sql = "SELECT LPAD (lote::text,6,'0') AS lote , TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento FROM tbl_distrib_lote WHERE distrib_lote = $lote";
	$resX = pg_exec ($con,$sql);

	echo "<center><h1>Lote " . pg_result ($resX,0,lote) . " de " . pg_result ($resX,0,fechamento) . "</h1></center>";
	echo "<br>";
	echo "<table border='1' cellspacing='0' cellpadding='2'>";
	echo "<tr align='center' bgcolor='#eeeeee'>";
	echo "<td nowrap><b>Código</b></td>";
	echo "<td nowrap><b>Nome</b></td>";

	#HD 243022
	echo "<td nowrap><b>Banco</b></td>";
	echo "<td nowrap><b>Conta</b></td>";
	echo "<td nowrap><b>Agência</b></td>";

	echo "<td nowrap><b>Peças</b></td>";
	echo "<td nowrap><b>Custo</b></td>";
	


	$sql = "SELECT t_1.distrib_lote, t_1.posto, 
			t_1.nota_fiscal_mo,
			tbl_produto.mao_de_obra, 
			SUM (tbl_produto.mao_de_obra) AS mobra_total, 
			COUNT (t_1.os) AS qtde_os
			INTO TEMP TABLE tmp_tab1
			FROM t_1
			JOIN tbl_produto ON tbl_produto.produto = t_1.produto 
			GROUP BY t_1.distrib_lote, t_1.posto , t_1.nota_fiscal_mo, tbl_produto.mao_de_obra;";

	$sql = "SELECT DISTINCT tbl_produto.mao_de_obra
			FROM (
				SELECT tbl_os.produto FROM tbl_os JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
				WHERE tbl_distrib_lote_os.distrib_lote = $lote
			) xprod 
			JOIN tbl_produto ON tbl_produto.produto = xprod.produto ";

	$resX = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
		echo "<td nowrap><b>" . number_format (pg_result ($resX,$i,mao_de_obra),2,",",".") . "</b></td>";
		$array_mo[$i]= pg_result ($resX,$i,mao_de_obra) ;
	}
	$qtde_cab = $i;

	echo "<td nowrap><b>TOTAL</b></td>";
	echo "<td nowrap><b>EXTRATO/DATA</b></td>";
	echo "<td><b>TOTAL AVULSO</b></td>";
	echo "<td><b>TOTAL SEDEX</b></td>";
	echo "<td nowrap><b>NF/DATA</b></td>";
	echo "<td><b>TOTAL NOTA</b></td>";
	echo "<td nowrap><b>RECEB. LOTE</b></td>";
	echo "<td><b>Número Objeto</b></td>";
	echo "<td nowrap>&nbsp;</td>";
	echo "</tr>";

	$qtde_total_os = 0 ;
	$mobra_total   = 0 ;
	$mobra_posto   = 0 ;
	$total_total   = 0 ;


	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {
		if ($i == pg_numrows ($res) ) $codigo_posto = "*";
		
			$codigo_posto   = pg_result ($res,$i,codigo_posto);
			$nome           = pg_result ($res,$i,nome);
			$banco          = pg_result ($res,$i,banco);
			$conta          = pg_result ($res,$i,conta);
			$agencia        = pg_result ($res,$i,agencia);
			$nota_fiscal_mo = pg_result ($res,$i,nota_fiscal_mo);
			if (pg_result ($res,$i,med_qtde_os) > 0) {
				$media_pecas = pg_result ($res,$i,med_qtde_pecas) / pg_result ($res,$i,med_qtde_os);
				$custo       = pg_result ($res,$i,med_custo)      / pg_result ($res,$i,med_qtde_os);
			}else{
				$media_pecas = 0;
				$custo       = 0;
			}
			$extrato = pg_result($res,$i,extrato);
			$mobra_posto = 0 ;
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			$qtde_os     = pg_result ($res,$i,qtde_os);

			for ($x = 0 ; $x < $qtde_cab ; $x++) {
				if ($mao_de_obra == $array_mo [$x][1]) {
					$array_mo [$x][2] = $qtde_os ;
				}
			}
			echo "<tr style='font-size:10px'>";
			echo "<input type='hidden' name='codigo_posto' rel='codigo_posto' value='$codigo_posto'>";
			echo "<input type='hidden' name='nota_fiscal_mo' rel='nota_fiscal_mo' value='$nota_fiscal_mo'>";
			echo "<input type='hidden' name='lote' rel='lote' value='$lote'>";
			echo "<td nowrap>";
			echo $codigo_posto;
			echo $mobra;
			echo "</td>";

			echo "<td nowrap>";
			echo substr($nome,0,30);
			echo "</td>";

			echo "<td nowrap align='center'>";
			#HD 243022
			if(strlen($banco)>0){
				$sqlB = "SELECT codigo, nome from tbl_banco where codigo = '$banco'";
				$resB = pg_exec ($con,$sqlB);

				if(pg_numrows($resB)>0){
					$codigo     = pg_result ($resB,0,codigo);
					$banco_nome = pg_result ($resB,0,nome);

					echo "$banco - $banco_nome";
				}
			}
			echo "&nbsp;";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo $conta;
			echo "&nbsp;";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo $agencia;
			echo "&nbsp;";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($media_pecas,1,",",".");
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo number_format ($custo,2,",",".");
			echo "</td>";

			for ($x = 0 ; $x < $qtde_cab ; $x++) {
				echo "<td align='right'>";
				$valor_mao_de_obra = $array_mo[$x];
				$sql_qtde = "SELECT count(xprod.produto) as qtde 
									FROM ( 
										SELECT tbl_os.produto 
										FROM tbl_os 
										JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
										JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
										WHERE tbl_distrib_lote_os.distrib_lote = $lote
										AND   extrato= $extrato
										and posto in (select posto from tbl_posto_fabrica where fabrica = $fabrica and codigo_posto = '$codigo_posto')) xprod 
									JOIN tbl_produto ON tbl_produto.produto = xprod.produto
									where tbl_produto.mao_de_obra = $valor_mao_de_obra;";
				$res_qtde = pg_exec ($con,$sql_qtde);
				//echo $sql_qtde;
				$qtde_os = pg_result($res_qtde,0,qtde);
				$total_qtde_os[$x] = $total_qtde_os[$x] + $qtde_os;
				if ($qtde_os > 0) {
					echo $qtde_os ;
					$mobra_posto = $mobra_posto + ($qtde_os * $valor_mao_de_obra) ;
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
			}
			
			echo "<td align='right'><b>";
			echo number_format ($mobra_posto,2,",",".");
			$total_total += $mobra_posto ;
			echo "</b></td>";

			$sql2 = "SELECT DISTINCT nf_mobra, to_char(data_nf_mobra,'dd/mm/yyyy') as data_nf_mobra, valor_mobra, to_char(data_recebimento_lote,'dd/mm/yyyy') as data_recebimento_lote, tbl_distrib_lote_posto.total_sedex ,identificador_objeto
						FROM tbl_distrib_lote_posto 
						JOIN tbl_distrib_lote USING(distrib_lote) 
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_distrib_lote_posto.posto AND tbl_posto_fabrica.fabrica = tbl_distrib_lote.fabrica
						JOIN tbl_distrib_lote_os ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_os.distrib_lote
						JOIN tbl_os_extra USING(os)
						WHERE tbl_distrib_lote.distrib_lote = $lote
						AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
						AND tbl_distrib_lote_posto.nf_mobra = '$nota_fiscal_mo'
						";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$total_sedex        = pg_result($res2,0,total_sedex);
				$nota_mobra         = pg_result($res2,0,nf_mobra);
				$data_nota_mobra    = pg_result($res2,0,data_nf_mobra);
				$total_nota_mobra   = pg_result($res2,0,valor_mobra);
				$recebimento_lote   = pg_result($res2,0,data_recebimento_lote);
				$identificador_objeto   = pg_result($res2,0,identificador_objeto);
				
				if(strlen($codigo_posto_ant) > 0 and $codigo_posto_ant == $codigo_posto and $nota_fiscal_mo_ant ==$nota_fiscal_mo) {
					$total_sedex = 0;
					$total_nota_mobra = 0;
				}
				$total_total_sedex  = $total_total_sedex + $total_sedex;
				$total_total_mobra  = $total_total_mobra + $total_nota_mobra;
			}else{
				$total_sedex        = "";
				$nota_mobra         = "";
				$data_nota_mobra    = "";
				$total_nota_mobra   = "";
				$recebimento_lote   = "";
				$identificador_objeto   = "";
			}

			$sql2 = "SELECT CASE WHEN SUM(tbl_extrato_lancamento.valor) IS NULL THEN 0 else SUM(tbl_extrato_lancamento.valor) END as total_avulso
						FROM tbl_extrato_lancamento
						WHERE extrato = $extrato;";
			#echo "$sql2";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$total_extrato_avulso = pg_result($res2,0,total_avulso);
				$total_total_extrato_avulso = $total_total_extrato_avulso + $total_extrato_avulso;
			}

			$sql2 = "	SELECT DISTINCT to_char(data_geracao, 'dd/mm/yyyy') as data_geracao
						FROM tbl_extrato WHERE extrato  = $extrato
						;";
			#echo "$sql2";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$data_geracao = pg_result($res2,0,data_geracao);
			}
			$relacao_nome = "";
			$sqladmin = "select distinct tbl_admin.nome_completo 
							from tbl_distrib_lote_os 
							left join tbl_admin using(admin) 
							left join tbl_os_extra using(os) 
							where extrato = $extrato
							and admin is not null;";
			$resadmin = pg_exec($con,$sqladmin);
			$qtdadmin = pg_numrows($resadmin);
			if($qtdadmin > 0){
				for ($j = 0 ; $j < $qtdadmin ; $j++) {
					$relacao_nome .= " ".pg_result($resadmin,$j,nome_completo);
				}
			}
			echo "<td align='right' title='$relacao_nome' nowrap><b>";
			echo $extrato . " - " . $data_geracao;
			echo "</b></td>";

			echo "<td align='right'><b>";
			echo number_format ($total_extrato_avulso,2,",",".");
			echo "</b></td>";

			echo "<td align='right'>";
			echo "<div rel='total_sedex' style='font-weight:bold'>";
			echo number_format ($total_sedex,2,",",".");
			echo "</td>";

			echo "<td align='right' nowrap >";
			echo  $nota_mobra . "-" ;
			echo $data_nota_mobra;
			echo "</td>";

			echo "<td align='right'>";
			echo "<div rel='total_nota_mobra' style='font-weight:bold'>";
			echo number_format ($total_nota_mobra,2,",",".");
			echo "</div></td>";
			$total_nota_mobra = '0';

			echo "<td align='right'>";
			echo "<div rel='recebimento_lote' style='font-weight:bold'>";
			echo $recebimento_lote;
			echo "</div></td>";

			echo "<td align='right'>";
			echo "<div rel='identificador_objeto' style='font-weight:bold'>";
			echo $identificador_objeto;
			echo "</div></td>";

			echo "<td>";
			echo "<a href=\"javascript: if (confirm('Deseja realmente excluir do lote o posto $codigo_posto - $nome?') == true) { window.location='$PHP_SELF?excluir=$codigo_posto&distrib_lote=$lote&nf_mobra=$nota_fiscal_mo'; } \">Excluir</A>";
			echo "</td>";

		for ($x = 0 ; $x < $qtde_cab ; $x++) {
			if ($mao_de_obra == $array_mo [$x][1]) {
				$array_mo [$x][2] = $qtde_os ;
			}
		}
		$codigo_posto_ant = $codigo_posto;
		$nota_fiscal_mo_ant = $nota_fiscal_mo;
	}

	echo "<tr align='center' bgcolor='#eeeeee'>";
	echo "<td colspan='2'><b>Qtde Total de OS</b></td>";

	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";

	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";

	for ($x = 0 ; $x < $qtde_cab ; $x++) {
		echo "<td align='right'>";
		/*
		$valor_mao_de_obra = $array_mo[$x];
		$sql_qtde = "SELECT count(xprod.produto) as qtde 
						FROM ( 
							SELECT tbl_os.produto 
							FROM tbl_os 
							JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
							WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote) xprod 
						JOIN tbl_produto ON tbl_produto.produto = xprod.produto
						where tbl_produto.mao_de_obra = $valor_mao_de_obra;";
		$sql_qtde = "SELECT count(xprod.produto) as qtde 
						FROM ( 
							SELECT tbl_os.produto 
							FROM tbl_os 
							JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os 
							WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote) xprod 
						JOIN tbl_produto ON tbl_produto.produto = xprod.produto
						where tbl_produto.mao_de_obra = $valor_mao_de_obra;";

		#echo $sql_qtde;
		$res_qtde = pg_exec ($con,$sql_qtde);
		$qtde_os = pg_result($res_qtde,0,qtde);
		echo $qtde_os;*/
		echo $total_qtde_os[$x];
		echo "</td>";
	}

	echo "<td align='right'><b>" . number_format ($total_total,2,",",".") . "</b></td>";
	echo "<td align='right'><b></b></td>";
	echo "<td align='right'><b>" . number_format ($total_total_extrato_avulso,2,",",".") . "</b></td>";
	echo "<td align='right'><b>" . number_format ($total_total_sedex,2,",",".") . "</b></td>";
	echo "<td align='right'><b></b></td>";
	echo "<td align='right'><b>" . number_format ($total_total_mobra,2,",",".") . "</b></td>";
	echo "<td colspan='3'>&nbsp;</td>";
	echo "</tr>";

	echo "</table>";

}

?>

<? #include "rodape.php"; ?>

</body>

</html>
