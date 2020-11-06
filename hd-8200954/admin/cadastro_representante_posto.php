<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios = "cadastros";
$layout_menu       = "cadastro";
$title             = "CADASTRO DE REPRESENTANTE POR POSTO";

if (!empty($_POST) || !empty($_GET)) {//NÃO PODE VERIFICAR COM REQUEST, POIS JÁ VEM ALGUMAS VAR

	$btn_acao      = $_REQUEST['btn_acao'];
	$cod_posto     = $_REQUEST['cod_posto'];
	$representante = $_REQUEST['representante'];
	$nome_posto = $_POST['nome_posto'];
	$nome_representante = $_POST['nome_representante'];
	$cod_representante = $_POST['cod_representante'];
	

if($btn_acao == 'gravar'){
	if (!empty($_POST)) {
		$where = " AND codigo_posto = '$cod_posto'";
	} else {
		$where = " AND posto = $cod_posto";
	}

	if(strlen($cod_posto)==0){
		$msg_erro = "Insira o Posto";
	}

	if(strlen($cod_representante)==0 and strlen($msg_erro)==0){
		$msg_erro = "Insira o Representante";
	}

	if (strlen($msg_erro)==0) {

	$sql = "SELECT posto
			  FROM tbl_posto_fabrica
			 WHERE fabrica = $login_fabrica
			$where;";

	$res = @pg_exec($con, $sql);

	if (@pg_numrows($res) > 0) {

		$posto = trim(@pg_result($res, 0, 'posto'));

		
			

			$sql = "INSERT INTO tbl_posto_fabrica_representante (fabrica
																	, posto
																	, representante
																) VALUES (
																	$login_fabrica
																	, $posto
																	, $representante
																);";

			$res      = @pg_exec($con, $sql);
			$msg_erro = @pg_errormessage();

			if (strlen($msg_erro) == 0) {
				$msg = 'Gravado com Sucesso!';
			} else {
				$msg_erro = 'Esta Informação já foi Cadastrada';
			}

		
	} else {

		$msg_erro = 'Posto não encontrado!';

	}
}
}
if ($btn_acao == 'ativa') {

	$ativo = $_GET['ativa'];
	$sql = "UPDATE tbl_posto_fabrica_representante set ativo = '$ativo'
			WHERE tbl_posto_fabrica_representante.posto = $cod_posto
			AND tbl_posto_fabrica_representante.representante = $representante
			AND tbl_posto_fabrica_representante.fabrica = $login_fabrica;";

	//echo $sql; exit;
	$res      = @pg_exec($con, $sql);

}

}

include 'cabecalho.php';?>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script> <!-- Para bloquear números ou letras -->
<script type="text/javascript">

	function fnc_pesquisa_posto(tipo) {

		if (tipo == "codigo") {
			var xcampo = document.getElementById('cod_posto');
		}

		if (tipo == "nome") {
			var xcampo = document.getElementById('nome_posto');
		}

		if (xcampo.value != '') {

			var url = '';

			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
			janela.retorno = "<?=$_SERVER['PHP_SELF']; ?>";
			janela.nome    = document.getElementById('nome_posto');
			janela.codigo  = document.getElementById('cod_posto');
			janela.focus();

		} else {

			alert('Preencha toda ou parte da informação para realizar a pesquisa!');

		}

	}

	function fnc_pesquisa_representante(tipo) {

		if (tipo == "codigo") {
			var xcampo = document.getElementById('cod_representante');
		}

		if (tipo == "nome") {
			var xcampo = document.getElementById('nome_representante');
		}

		if (xcampo.value != '') {

			var url    = '';
			var janela = new Object();

			url    = "representante_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");

			janela.nome          = document.getElementById('nome_representante');
			janela.codigo        = document.getElementById('cod_representante');
			janela.representante = document.getElementById('representante');
			janela.focus();

		} else {

			alert('Preencha toda ou parte da informação para realizar a pesquisa!');

		}

	}

</script>

<style type="text/css">
	.titulo_tabela{
		background-color:#596D9B;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.texto_avulso{
		font: 14px Arial;
		color: rgb(89, 109, 155);
		background-color: #D9E2EF;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596D9B;
	}

    .titulo_coluna{
        background-color:#596d9b;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .subtitulo{
        color: #7092BE
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

</style>

<form name="frm_representante_posto" method="post" action="">
<table width='700' align='center' border='0' cellpadding="1" cellspacing="1" class="formulario">
	<? if (strlen ($msg) > 0) { ?>
		<tr class="sucesso">
			<td colspan="4"> <? echo $msg; ?></td>
		</tr>
	<? } ?>

	<? if (strlen ($msg_erro) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="4"> <? echo $msg_erro; ?></td>
		</tr>
	<? } ?>

	<tr>
		<td colspan="4" class="titulo_tabela">
			Informações Cadastrais
		</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>
		<td>Cod. Posto</td>
		<td colspan="2">Nome do Posto</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input class="frm" type="text" name="cod_posto" id="cod_posto" size="20" maxlength="18" value="<? echo $cod_posto; ?>" />&nbsp;
			<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto('codigo')" />
		</td>
		<td colspan="2">
			<input class="frm" type="text" name="nome_posto" id="nome_posto" size="50" maxlength="60" value="<? echo $nome_posto; ?>" />&nbsp;
			<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto('nome')" />
			
		</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>
		<td>Cod. Representante</td>
		<td colspan ='2'>Representante</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input class="frm" type="hidden" name="representante" id="representante" value="" />
			<input class="frm" type="text" name="cod_representante" id="cod_representante" size="20" maxlength="18" value="<? echo $cod_representante; ?>" />&nbsp;
			<img src='../imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_representante('codigo')" />
		</td>
		<td colspan="2">
			<input  class="frm" type="text" name="nome_representante" id="nome_representante" size="50" maxlength="60" value="<? echo $nome_representante; ?>">&nbsp;
			<img src="../imagens/lupa.png" style="cursor:pointer" border="0" align="absmiddle" onclick="fnc_pesquisa_representante('nome')" />
		</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type='hidden' name='btn_acao' value='' />
			<input type="button" value="Gravar" onclick=" if (document.frm_representante_posto.btn_acao.value == '' ) { document.frm_representante_posto.btn_acao.value='gravar' ; document.frm_representante_posto.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' />
		</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
</form>

<br />
<br />

<table width="700" border="0" cellpadding="2" cellspacing="1" class="tabela" align="center">
	<tr class='titulo_tabela'>
		<th class='titulo_coluna'>Posto</th>
		<th class='titulo_coluna'>Representante</th>
		<th class='titulo_coluna'>Status</th>
		<th class='titulo_coluna'>Ação</th>
		
	</tr><?php

	$sql = "SELECT tbl_posto.posto as cod_posto                         ,
				   tbl_posto.nome as posto                              ,
				   tbl_representante.representante as cod_representante ,
				   tbl_representante.nome as representante,
				   tbl_posto_fabrica_representante.ativo as status
			  FROM tbl_posto_fabrica_representante
			  JOIN tbl_representante ON tbl_posto_fabrica_representante.representante = tbl_representante.representante
			  JOIN tbl_posto         ON tbl_posto_fabrica_representante.posto         = tbl_posto.posto
			 WHERE tbl_posto_fabrica_representante.fabrica = $login_fabrica
			 ORDER BY tbl_posto_fabrica_representante.ativo DESC";

	$res   = @pg_exec($con, $sql);
	$total = @pg_numrows($res);

	for ($i = 0; $i < $total; $i++) {

		$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

		$cod_posto         = @trim(pg_result($res, $i, 'cod_posto'));
		$posto             = @trim(pg_result($res, $i, 'posto'));
		$cod_representante = @trim(pg_result($res, $i, 'cod_representante'));
		$representante     = @trim(pg_result($res, $i, 'representante'));
		$status			   = @trim(pg_result($res, $i, 'status'));

		echo "<tr bgcolor='$cor' class='Label'>";
			echo "<td align='left'>$posto</td>";
			echo "<td align='left'>$representante</td>";
			if($status == 't'){
				echo "<td width='18' align='center'><img src='imagens/status_verde' width='10' /></td>";
				echo "<td align='center'>";
				echo "<input type='button' value='Inativar' onclick=\"window.location='?btn_acao=ativa&cod_posto=$cod_posto&representante=$cod_representante&ativa=f'\">";
				echo "</td>";
				
			}
			else{
				echo "<td width='18' align='center'><img src='imagens/status_vermelho' width='10' /></td>";
				echo "<td align='center'>";
				echo "<input type='button' value='Ativar' onclick=\"window.location='?btn_acao=ativa&cod_posto=$cod_posto&representante=$cod_representante&ativa=t'\">";
				echo "</td>";

				
			}

			
		echo '</tr>';

	}?>

</table>

<? include "rodape.php"; ?>