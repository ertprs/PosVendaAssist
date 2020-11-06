<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

$defeito = $_GET['defeito'];
$peca    = $_GET['peca'];
$btnacao = $_POST['btnacao'];

if (strlen($peca) > 0 and strlen($defeito) > 0) {

	$AuditorLog = new AuditorLog;
	$AuditorLog->retornaDadosSelect("select peca_defeito, peca, defeito, ativo from tbl_peca_defeito where peca = $peca");

	$sql = "UPDATE tbl_peca_defeito set ativo = 'f' where peca = $peca and defeito = $defeito";
	$res = pg_query($con,$sql);

	$AuditorLog->retornaDadosSelect()->EnviarLog('insert', 'tbl_peca_defeito',"$login_fabrica*$peca");

	header ("Location: peca_integridade.php");
	exit;
}

if (strlen($btnacao) > 0 && $btnacao == 'gravar') {

	$referencia    = $_POST['referencia'];
	$total_defeito = $_POST['total_defeito'];

	if (strlen($referencia) == 0) {

		$msg_erro = "Por favor selecione a peça";

	} else {

		$sql = "SELECT peca
				  FROM tbl_peca
				 WHERE fabrica    = $login_fabrica
				   AND referencia ='$referencia'";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$peca = pg_fetch_result($res, 0, 'peca');
		} else {
			$msg_erro = "Peça não encontrada";
		}

		$sql_defeitos = "SELECT peca_defeito, peca, defeito, ativo FROM tbl_peca_defeito WHERE peca = $peca ORDER BY defeito";
		$AuditorLog = new AuditorLog;
		$AuditorLog->retornaDadosSelect($sql_defeitos);
	}

	if (strlen($msg_erro) == 0) {

		$sql = "UPDATE tbl_peca_defeito set ativo = 'f' WHERE peca = $peca";
		$res = pg_query($con,$sql);

		for ($x = 0; $total_defeito > $x; $x++) {

			$defeito = $_POST['defeito_'.$x];

			if (strlen($defeito) > 0) {

				$sql = "SELECT peca from tbl_peca_defeito where peca = $peca and defeito = $defeito";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {

					$sql = "UPDATE tbl_peca_defeito set ativo = 't' WHERE peca = $peca and defeito = $defeito";
					$res = pg_query($con,$sql);
					$msg_exito = "Cadastrado com Sucesso!";

				} else {

					$sql = "INSERT into tbl_peca_defeito (peca,defeito,ativo) values ($peca,$defeito,'t')";
					$res = pg_query($con,$sql);
					$msg_exito = "Cadastrado com Sucesso!";

				}
			}
		}
	}
	$AuditorLog->retornaDadosSelect()
		->EnviarLog('update', 'tbl_peca_defeito', "$login_fabrica*$peca");
}

$layout_menu = "cadastro";
$title = "Cadastramento de Peças";
include 'cabecalho.php';

include_once '../js/js_css.php'; 
?>

<script language="JavaScript">

	$(function(){
        Shadowbox.init();

        Shadowbox.open({
			content: url,
			player: "iframe",
			height: 600,
			width: 800
		});
    });

	function fnc_pesquisa_produto (campo, campo2, tipo) {

		if (tipo == "referencia") {
			var xcampo = campo;
		}

		if (tipo == "descricao") {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.focus();
		} else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
		}

	}

	function fnc_pesquisa_peca (campo, campo2, tipo, forma) {

		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (forma == null) {
			forma = '';
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo +"&forma=" + forma ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno    = "<? echo $PHP_SELF ?>";
			janela.referencia = campo;
			janela.descricao  = campo2;
			janela.focus();
		}
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
		}

	}

</script>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{ 
	padding:0 0 0 100px;
}


	body {
		margin: 0px;
	}

	.exito {
		font-family: Verdana,sans;
		font-size: 12px;
		font-style: bold;
		color: white;
		background-color: green;
		width: 700px;
	}

	label {
		cursor:pointer;
	}
</style>

</head>

<body>

<div id="wrapper">

<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- formatando as mensagens de erro --><?php

if (strlen($msg_erro) > 0) {

	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}?>

	<div class='error'><?php
		if (strlen($msg_erro) > 0) {
			echo $msg_erro;
		}?>
	</div><?php

} else {

	# HD 51163 - separado mensagem de erro da de êxito
	echo "<center><div class='exito'>";
		echo $msg_exito;
	echo "</div></center>";

}

if (strlen($peca) > 0 AND ($login_fabrica == 50 or $login_fabrica == 5 or $login_fabrica == 24 or $login_fabrica == 15)) {

	$sqlx = "SELECT tbl_peca.referencia,
					tbl_peca.descricao
			   FROM tbl_peca
			  WHERE tbl_peca.fabrica = $login_fabrica
				AND tbl_peca.peca    = $peca";

	$resx = pg_query($con, $sqlx);

	if (pg_num_rows($resx) > 0) {

		$descricao  = pg_fetch_result($resx, 0, 'descricao');
		$referencia = pg_fetch_result($resx, 0, 'referencia');

	}

}?>

<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  class='formulario'>
	<tr class='titulo_tabela'><td colspan='100%'>Cadastro</td></tr>
	<tr>
		<td colspan="2" class='espaco' style="padding:20px 0 0px 100px;">Referência *</td>
		<td colspan="2" style="padding:20px 0 0px 0px;">Descrição Peça *</td>
	</tr>
	<tr>
		<td  colspan="2" class='espaco' style="padding:0px 0 20px 100px;">
			<input class='frm' type="text" name="referencia" value="<?=$referencia?>" size="15" maxlength="20" />
			<a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia', 'reload')" />
				<img src="imagens/lupa.png" />
			</a>
		</td>
		<td  colspan="2" style="padding:0px 0 20px 0px;">
			<input class='frm' type="text" name="descricao" value="<?=$descricao?>" size="30" maxlength="50" />
			<a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao', 'reload')">
				<img src="imagens/lupa.png" />
			</a>
		</td>
	</tr>
	<tr>
		<td class='subtitulo' colspan='100%'>
			Defeitos
		</td>
	</tr>
	<tr><?php

		$sql = "SELECT tbl_defeito.defeito,
					   tbl_defeito.descricao
				  FROM tbl_defeito
				 WHERE fabrica = $login_fabrica
				   AND tbl_defeito.ativo IS TRUE
				 ORDER BY tbl_defeito.descricao";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			for ($x = 0; $x < pg_num_rows($res); $x++) {

				$xdefeito   = pg_fetch_result($res, $x, 'defeito');
				$xdescricao = pg_fetch_result($res, $x, 'descricao');

				if (strlen($peca) > 0 AND ($login_fabrica == 50 or $login_fabrica == 5 or $login_fabrica == 24 or $login_fabrica == 50)) {

					$sqlx = "SELECT tbl_defeito.defeito
							   FROM tbl_peca_defeito
							   JOIN tbl_defeito using(defeito)
							   JOIN tbl_peca on tbl_peca.peca = tbl_peca_defeito.peca
								AND tbl_peca.fabrica = $login_fabrica
							  WHERE tbl_peca_defeito.ativo = 't'
								AND tbl_peca.peca          = $peca
								AND tbl_defeito.defeito    = $xdefeito
							  ORDER BY tbl_defeito.descricao";

					$resx = pg_query($con, $sqlx);

					if (pg_num_rows($resx) > 0) {

						for ($i = 0; $i < pg_num_rows($resx); $i++) {
							$defeito = pg_fetch_result($resx, $i, 'defeito');
						}

					}

				}

				if (($x % 2) == 0) {
					echo "</tr><tr>";
				}

				echo "<td width='50'>&nbsp;</td>";
				echo "<td  align='left'>";
					echo "<input type='checkbox' name='defeito_$x' id='defeito_$x' value='$xdefeito'"; if ($xdefeito == $defeito) { echo "checked";} echo "/> <label for='defeito_$x'>$xdescricao</label>";
				echo "</td>";

			}

		}?>

	</tr>

	<tr>
		<td align='center'  colspan='4'>
			<input type='hidden' name='total_defeito' value='<?=$x;?>' />
			<input type='hidden' name='btnacao' value='' />
			<input type='button' value='Gravar' onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
			<input type='button' value='Limpar' onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" border='0' style="cursor:pointer;">
		</td>
		
	</tr>
	<?php if($peca > 0){?>
	<tr>
		<td align='center' colspan="4" style="padding: 10px;">
			<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_peca_defeito&id=<?php echo $peca; ?>' name="btnAuditorLog">Visualizar Log Auditor</a>
		</td>
	</tr>
	<?php } ?>
</table>
</form>
<br /><br />

<form name="frm_pesquisa" action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<input type="hidden" name="btnacao" value='filtrar' />
	<input type="hidden" name="peca" value='<?=$peca?>' />
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1' class='formulario'>
		<tr class='titulo_tabela'>
			<td colspan="100%">
				Parâmetros de Pesquisa
			</td>
		</tr>
		<!--<tr>
			<td colspan="2">
				<p align="left" style="padding: 0 10px" class='texto_avulso'>
					- Para listar todas as peças, deixe os campos <b>"Produto Referência"</b> e <b>"Produto Descrição"</b> em branco e clique em <b>"Pesquisar"</b>; <br />
					- Para gerar arquivo do Excel (XLS) marque a caixa <b>"Gerar Arquivo em Excel"</b> <br />
					- Para listar as peças e defeitos de apenas um produto, preencha o produto e clique em <b>"Pesquisar"</b><br />
				</p>
			</td>
		</tr>-->
		<tr><td colspan='100%'>&nbsp;</td></tr>
		<tr>
			<td class='espaco'>Referência *</td>
			<td>Produto Descrição *</td>
		</tr>
		<tr>
			<td class='espaco'>
				<input class='frm' type="text" name="produto_referencia" value="<?=$produto_referencia ?>" size="15" maxlength="20" />
				<a href="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'referencia')">
					<img src="imagens/lupa.png" border="0" />
				</a>
			</td>
			<td>
				<input class='frm' type="text" name="produto_descricao" value="<?=$produto_descricao ?>" size="50" maxlength="50" />
				<a href="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'descricao')">
					<img src="imagens/lupa.png" border="0" />
				</a>
			</td>
		</tr>
	
		<tr>
			<td colspan="2" class='espaco' style='padding:20px 0 0 100px;'>
				<img src="imagens/help.png" alt="" style="cursor:help" title="A geração de arquivo XLS só será disponível no 'Exibir apenas peças do Produto'">
				<label for="gera_xls" style="">Gerar Arquivo em Excel</label>
				<input class='frm' type="checkbox" name="gera_xls" id="gera_xls" value="sim" />
			</td>
		</tr>
		<tr>
			<td colspan="100%" align='center'>
				<br />
				<input type="submit" value="Exibir Apenas Peças do Produto" />
				<input type='button' value='Listar Todas as Peças' onclick="window.location='<?php echo $PHP_SELF;?>?listar=todas'">
				<br />
				<br />
			</td>
		</tr>
	</table>
</form>

<br /><br />
<?php

if ($btnacao == 'filtrar' OR strlen($_GET['listar'])>0) {

	$peca               = $_POST['peca'];
	$peca_referencia    = $_POST['peca_referencia'];
	$produto_referencia = $_POST['produto_referencia'];

	if (!empty($peca_referencia)) {
		$where_peca = " AND tbl_peca.referencia = '$peca_referencia' ";
	}

	if (!empty($produto_referencia)) {
		$where_produto  = " AND tbl_produto.referencia = '$produto_referencia' ";
		$join_produto   = " JOIN tbl_lista_basica ON tbl_peca.peca       = tbl_lista_basica.peca    AND tbl_lista_basica.fabrica = $login_fabrica ";
		$join_produto  .= " JOIN tbl_produto      ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_lista_basica.fabrica = $login_fabrica ";
	}

	$sql = "SELECT tbl_peca.referencia                   ,
				   tbl_peca.peca                         ,
				   tbl_peca.descricao  as peca_descricao ,
				   tbl_defeito.descricao                 ,
				   tbl_defeito.defeito                   ,
				   tbl_peca_defeito.ativo
			  FROM tbl_peca_defeito
			  JOIN tbl_defeito ON tbl_defeito.defeito = tbl_peca_defeito.defeito
			  JOIN tbl_peca    ON tbl_peca.peca       = tbl_peca_defeito.peca
			  $join_produto
			 WHERE tbl_peca_defeito.ativo = 't'
			 $where_peca
			 $where_produto
			   AND tbl_peca.fabrica = $login_fabrica
			 ORDER BY tbl_peca.descricao, tbl_defeito.descricao ;";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		//HD 272225
		if (isset($_POST['gera_xls'])) {//GERA XLS - EXCEL

			$data = date ("d/m/Y H:i:s");

			$arquivo_nome = "relatorio-integridade-peca-$login_fabrica.xls";
			$path         = '/www/assist/www/admin/xls/';
			$path_tmp     = '/tmp/';

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen($arquivo_completo_tmp,"w");

			fputs($fp,"<html>");
			fputs($fp,"<head>");
				fputs($fp,"<title>RELATÓRIO DE PEÇA INTEGRIDADE - $data</title>");
				fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($fp,"</head>");
			fputs($fp,"<body>");

		}

		ob_start();//ATIVA BUFFER DE SAÍDA?>

		<table width='700' align='center' border='0' cellpadding='2' cellspacing='1' class='tabela'>
			<tr class='titulo_coluna'>
				<th>Peça</th>
				<th>Defeito</th>
				<th>&nbsp;</th>
			</tr><?php

			$vet = array();

			for ($x = 0; pg_num_rows($res) > $x; $x++) {

				$peca              = pg_fetch_result($res, $x, 'peca');
				$peca_referencia   = pg_fetch_result($res, $x, 'referencia');
				$peca_descricao    = pg_fetch_result($res, $x, 'peca_descricao');
				$defeito           = pg_fetch_result($res, $x, 'defeito');
				$defeito_descricao = pg_fetch_result($res, $x, 'descricao');
				$ativo             = pg_fetch_result($res, $x, 'ativo');

				if ($peca <> $peca_anterior) {
					if ($cor == "#F7F5F0") $cor = '#F1F4FA';
					else                   $cor = '#F7F5F0';
				}

				if($x%2==0)
					$bgcor = "#F7F5F0";
				else
					$bgcor = "#F1F4FA";

				$vet[$x]['peca_referencia']   = $peca_referencia;
				$vet[$x]['peca_descricao']    = $peca_descricao;
				$vet[$x]['defeito_descricao'] = $defeito_descricao;
				$vet[$x]['cor']               = $cor;

				echo "<tr bgcolor='$bgcor'>";
					if ($login_fabrica == 50) { //HD 80685
						echo "<td bgcolor='$cor' align='left'><a href='$PHP_SELF?peca=$peca'>$peca_referencia - $peca_descricao</a></td>";
					} else {
						echo "<td bgcolor='$cor' align='left'>$peca_referencia - $peca_descricao</td>";
					}
					echo "<td bgcolor='$cor'>$defeito_descricao</td>";
					echo "<td  bgcolor='$cor'>";
						echo "<a href='$PHP_SELF?defeito=$defeito&peca=$peca'>";
							echo "<img border='0' src='imagens_admin/btn_apagar.gif' alt='Apagar Integridade' />";
						echo "</a>";
					echo "</td>";
				echo "</tr>";

				$peca_anterior = $peca;

			}?>

		</table><?php

		ob_end_flush();//LIBERA BUFFER DE SAÍDA
		//ob_end_clean();
		
		//HD 272225
		if (isset($_POST['gera_xls'])) {//GERA XLS - EXCEL
			
			ob_start();

			echo "<table width='700' align='center' border='0' cellpadding='2' cellspacing='1' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
					echo "<th>Peça</th>";
					echo "<th>Defeito</th>";
				echo "</tr>";

			$total = count($vet);

			for ($i = 0; $i < $total; $i++) {
				if($i%2==0)
					$bgcor = "#F7F5F0";
				else
					$bgcor = "#F1F4FA";
				echo "<tr bgcolor='$bgcor'>";
					echo "<td bgcolor='{$vet[$i]['cor']}'>{$vet[$i]['peca_referencia']} - {$vet[$i]['peca_descricao']}</td>";
					echo "<td bgcolor='{$vet[$i]['cor']}'>{$vet[$i]['defeito_descricao']}</td>";
				echo "</tr>";
			}

			echo "</table>";

			$texto = ob_get_contents();//RECUPERA BUFFER DE SAÍDA

			fputs($fp,$texto);
			fclose($fp);

			ob_end_clean();//LIMPA O BUFFER DE SAÍDA

			echo ` cp $arquivo_completo_tmp $path `;
			?>
			<br />
			<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'>
				<tr>
					<td align='center'><input type='button' value='Download em Excel' onclick="javascript:window.location='xls/<? echo $arquivo_nome; ?>'" target='_blank'></td>
				</tr>
			</table>

		<?
		}

	} else {

		echo '<br />';
		echo '<h1>Nenhum registro encontrado!</h1>';

	}

}

include "rodape.php"; ?>
