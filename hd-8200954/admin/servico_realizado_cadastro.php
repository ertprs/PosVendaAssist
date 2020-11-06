<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$msg_sucesso = $_GET['msg'];
if (strlen($_GET["servico_realizado"]) > 0) $servico_realizado = trim($_GET["servico_realizado"]);
if (strlen($_POST["servico_realizado"]) > 0) $servico_realizado = trim($_POST["servico_realizado"]);
if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($servico_realizado) > 0 ) {
	if($servico_realizado == '62' or $servico_realizado == '90' ){
		$msg_erro = "Estes serviços não podem ser alterados ou apagados porque são utilizados na função de cálculo do item da OS";
	}
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_servico_realizado
			WHERE  fabrica = $login_fabrica
			AND    servico_realizado = $servico_realizado;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'servico_realizado_fk') > 0) $msg_erro = "Este serviço não pode ser excluído";

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$descricao     = $_POST["descricao"];
		$ativo         = $_POST["ativo"];
		$linha         = $_POST["linha"];
		$ressarcimento = $_POST["ressarcimento"];
		$troca_produto = $_POST["troca_produto"];
		$solucao       = $_POST["solucao"];
		$peca_estoque  = $_POST["peca_estoque"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if($servico_realizado == '62' or $servico_realizado == '90' ){
		$msg_erro = "Estes serviços não podem ser alterados ou apagados porque são utilizados na função de cálculo do item da OS";
	}
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
		}else{
			$msg_erro = "Favor informar a descrição do serviço realizado.";
		}
	}

	if (strlen($_POST["linha"]) == 0) $aux_linha = 'null';
	else                              $aux_linha = "'".$_POST["linha"]."'";

	$ativo = $_POST['ativo'];
	$aux_ativo = $ativo;
	if (strlen ($ativo) == 0)
		$aux_ativo = "f";

	$ressarcimento = $_POST['ressarcimento'];
	$aux_ressarcimento = $ressarcimento;
	if (strlen ($ressarcimento) == 0)
		$aux_ressarcimento = "f";

	$troca_produto = $_POST['troca_produto'];
	$aux_troca_produto = $troca_produto;
	if (strlen ($troca_produto) == 0)
		$aux_troca_produto = "f";

	$peca_estoque  = $_POST["peca_estoque"];
	$aux_peca_estoque = $peca_estoque;
	if(strlen($peca_estoque)==0){$aux_peca_estoque = "f";}

	$solucao = $_POST['solucao'];
	$aux_solucao = $solucao;
	if (strlen ($solucao) == 0)
		$aux_solucao = "f";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($servico_realizado) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_servico_realizado (
						descricao     ,
						fabrica       ,
						ativo         ,
						ressarcimento ,
						troca_produto ,
						solucao       ,
						peca_estoque  ,
						linha
					) VALUES (
						$aux_descricao        ,
						$login_fabrica        ,
						'$aux_ativo'          ,
						'$aux_ressarcimento'  ,
						'$aux_troca_produto'  ,
						'$aux_solucao'        ,
						'$aux_peca_estoque'   ,
						$aux_linha
					);";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_servico_realizado')");
			$x_servico_realizado  = pg_result ($res,0,0);

			//PARA BLACK, SERVIÇOS RELACIONADOS A TROCA INFLUENCIAM DIRETAMENTE NO EXTRATO
			//POR ISTO É DISPARADO UM EMAIL PARA SUPORTE ANALISAR SE DEVE SER SETADO "TROCA_DE_PECA" PARA ESTE DEFEITO

		// IGOR HD 3065 LIBERAR PARA TODAS AS FÁBRICAS QUE TEM TROCA_DE_PECA = TRUE E GERA_PEDIDO = TRUE
			if ( strlen($msg_erro) == 0 and strpos(strtoupper($aux_descricao),"TROCA") and ($login_fabrica == 1 or $login_fabrica == 2 or $login_fabrica == 3 or $login_fabrica == 5 or $login_fabrica == 6 or $login_fabrica == 7 or $login_fabrica == 8 or $login_fabrica == 10 or $login_fabrica == 11 or $login_fabrica == 14 or $login_fabrica == 15 or $login_fabrica ==  20 or $login_fabrica == 24 or $login_fabrica == 0)) {
				$remetente    = "CADASTRO DE SERVIÇO REALIZADO <helpdesk@telecontrol.com.br>";
				$destinatario = "helpdesk@telecontrol.com.br";
				$assunto      = "CADASTRO DE SERVIÇO REALIZADO *TROCA* ";
				$mensagem     = "Foi cadastrado um serviço realizado que contém a palavra *TROCA* na descrição pela fabrica: $login_fabrica, admin: $login_admin, serviço_realizado: $x_servico_realizado - $aux_descricao. Verificar a necessidade de validar no cálculo do extrato (verificar se é necessário setar TROCA_DE_PECA) e também verificar se é necessário setar  GERA_PEDIDO. O serviço troca de peça também não deve ser alterado a descrição.";
				$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
				//echo "teste de email: $mensagem";
				@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
			}

		}else{
			###ALTERA REGISTRO
			if($servico_realizado == '62' or $servico_realizado == '90' ){
				$msg_erro = "Estes serviços não podem ser alterados ou apagados porque são utilizados na função de cálculo do item da OS";
			}
			$sql = "UPDATE  tbl_servico_realizado SET
							descricao     = $aux_descricao         ,
							ativo         = '$aux_ativo'           ,
							ressarcimento = '$aux_ressarcimento'   ,
							troca_produto = '$aux_troca_produto'   ,
							solucao       = '$aux_solucao'         ,
							peca_estoque  = '$aux_peca_estoque'    ,
							linha         = $aux_linha
					WHERE   fabrica = $login_fabrica
					AND     servico_realizado = $servico_realizado;";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$x_servico_realizado = $servico_realizado;

		}


		if($login_fabrica==20){

			$sql = "SELECT * FROM tbl_servico_realizado_idioma WHERE servico_realizado = $x_servico_realizado AND idioma = 'ES'";
			$res = @pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$x_defeito_constatado  = trim(pg_result($res,0,servico_realizado));
				$sql2 = "UPDATE tbl_servico_realizado_idioma SET descricao = '$descricao_es'
					WHERE servico_realizado = $x_servico_realizado
					AND   idioma            = 'ES' ; ";
			}else{

				$sql2 = "INSERT INTO tbl_servico_realizado_idioma (
							servico_realizado  ,
							descricao           ,
							idioma
						) VALUES (
							$x_servico_realizado   ,
							'$descricao_es',
							'ES'
						);";
			}

			$res = @pg_exec ($con,$sql2);
			$msg_erro = pg_errormessage($con);

		}

	}

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$descricao = $_POST["descricao"];
		$ativo     = $_POST["ativo"];
		$linha     = $_POST["linha"];
		$solucao    = $_POST["solucao"];

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($servico_realizado) > 0) {
	$sql = "SELECT  descricao, ativo, linha, ressarcimento,troca_produto,solucao, peca_estoque, garantia_acessorio
			FROM    tbl_servico_realizado
			WHERE   fabrica = $login_fabrica
			AND     servico_realizado = $servico_realizado;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$descricao     = trim(pg_result($res,0,descricao));
		$ativo         = trim(pg_result($res,0,ativo));
		$ressarcimento = trim(pg_result($res,0,ressarcimento));
		$troca_produto = trim(pg_result($res,0,troca_produto));
		$linha         = trim(pg_result($res,0,linha));
		$solucao       = trim(pg_result($res,0,solucao));
		$peca_estoque  = trim(pg_result($res,0,peca_estoque));
		$garantia_acessorio = pg_result($res,0,garantia_acessorio);

		$sql2 = "SELECT  descricao
			FROM    tbl_servico_realizado_idioma
			WHERE   servico_realizado = $servico_realizado
			AND     idioma = 'ES'  ";
		$res2 = @pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) $descricao_es   = trim(pg_result($res2,0,descricao));

	}
}

$layout_menu = "cadastro";
$title = "CADASTRO DE SERVIÇOS";
include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
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
width: 700px;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
align: center;
}
</style>

<form name="frm_servico_realizado" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="servico_realizado" value="<? echo $servico_realizado ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table class='msg_erro' width='700px' align='center'>
	<tr>
		<td> 
			<? echo $msg_erro; ?>
		</td>
	</tr>
</table>
<? } ?>
<? if (strlen($msg_sucesso) > 0 && strlen( $msg_erro ) === 0 ) { ?>
<table class='msg_sucesso' width='700px' align='center'>
	<tr>
		<td> 
			<? echo $msg_sucesso; ?>
		</td>
	</tr>
</table>
	
</div>
<? }
# 113042 SOMENTE TELECONTROL PODE ALTERAR 
if ($login_fabrica<>10){
?>

<table width='700px' align="center" class="formulario" style="border:solid 1px #596d9b;">
<tr>
	<td style="font:bold 14px Arial;">O Cadastro de "Serviços" são feitos pela Telecontrol.</td>
</tr>
</table>
<br>
<?
//exit;
}

?>
<table width='700px' border='0' class='formulario' cellpadding='2' cellspacing='1' align='center'>
	<tr class='titulo_coluna'>
		<td>Serviço</b></td>
		<td>Ativo</td>
		<?if($login_fabrica==20){?>
		<td>Garantia de Acessório</td>
		<td>Solução</td>
		<?}?>
		<td>Ressarcimento</td>
		<td>Baixa Estoque</td>
		<td >Troca de Produto</td>
		<td>Linha</td>
	</tr>
	<tr>
	<?
	# 113042 SOMENTE TELECONTROL PODE ALTERAR 
	if($login_fabrica<>10) $disabled = "DISABLED";
	?>
		<td align='left'><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50" <?=$disabled?>></td>
		<td align='center'><input type="checkbox" class="frm" name="ativo" <? if ($ativo == 't' ) echo " checked " ?> value='t' <?=$disabled?>></td>
		<?if($login_fabrica==20){?>
		<td align='center'><input type="checkbox" class="frm" name="solucao" <? if ($garantia_acessorio == 't' ) echo " checked " ?> value='t'  <?=$disabled?>></td>
		<td align='center'><input type="checkbox" class="frm" name="solucao" <? if ($solucao == 't' ) echo " checked " ?> value='t'  <?=$disabled?>></td>
		<?}?>
		<td align='center'><input type="checkbox" class="frm" name="ressarcimento" <? if ($ressarcimento == 't' ) echo " checked " ?> value='t' <?=$disabled?>></td>
		<td align='center'><input type="checkbox" class="frm" name="peca_estoque" <? if ($peca_estoque == 't' ) echo " checked " ?> value='t' <?=$disabled?>></td>
		<td align='center'><input type="checkbox" class="frm" name="troca_produto" <? if ($troca_produto == 't' ) echo " checked " ?> value='t' <?=$disabled?>></td>
		<td>
		<select name='linha' class='frm' <?=$disabled?>>
			<option value=''>Escolha</option>
		<?
		$sql =	"SELECT linha, nome
				FROM tbl_linha
				WHERE fabrica = $login_fabrica
				ORDER BY nome;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$aux_linha = trim(pg_result($res,$i,linha));
				$aux_nome  = trim(pg_result($res,$i,nome));
				echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
			}
		}
		?>
		</select>
		</td>
	</tr>
<?
echo "</tr>";
if($login_fabrica==20){
	echo "<tr>";
	echo "<td align='left'>Descrição Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
	echo "</tr>";
}
?>
<tr><td>&nbsp;</td></tr>
<?
# 113042 SOMENTE TELECONTROL PODE ALTERAR 
if($login_fabrica==10){
?>

<tr>
	<td align='center' colspan='6'>
		<input type='hidden' name='btnacao' value=''>
		<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_servico_realizado.btnacao.value == '' ) { document.frm_servico_realizado.btnacao.value='gravar' ; document.frm_servico_realizado.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
		<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_servico_realizado.btnacao.value == '' ) { document.frm_servico_realizado.btnacao.value='deletar' ; document.frm_servico_realizado.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' style="cursor:pointer;">
		<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="window.location.href='<? echo $PHP_SELF ?>'" ALT="Limpar campos" border='0' style="cursor:pointer;">
	</td>
</tr>

</form>

<?
}?>
<tr><td>&nbsp;</td></tr>
</table>


</form>
<br />
<?

	

$sql =	"SELECT DISTINCT
				tbl_linha.linha ,
				tbl_linha.nome
		FROM     tbl_linha
		WHERE    tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.nome;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$linha      = trim(pg_result($res,$i,linha));
		$linha_nome = trim(pg_result($res,$i,nome));

		$sql2 =	"SELECT  servico_realizado ,
						 descricao         ,
						 gera_pedido	   ,
						 ativo
				FROM     tbl_servico_realizado
				WHERE    fabrica = $login_fabrica
				AND      linha = $linha
				AND      ativo
				ORDER BY ativo, descricao;";
		$res2 = pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) {
			for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
				$servico_realizado = trim(pg_result($res2,$j,servico_realizado));
				$descricao         = trim(pg_result($res2,$j,descricao));
				$ativo             = trim(pg_result($res2,$j,ativo));
				$gera_pedido       = trim(pg_result($res2,$j,gera_pedido));

				if ($linha_nome <> $linha_nome_anterior) {
					echo "<table width='700' border='0' class='formulario' cellpadding='2' cellspacing='1' align='center'>\n";
					echo "<tr class='titulo_tabela'><td colspan='3'>Relação de Serviços Realizados</td></tr>";
					echo "<tr class='titulo_coluna'>\n";
					echo "<td colspan='1'>$linha_nome</td>\n";
					echo "<td colspan='1' width='90px'>Status</td>\n";
					echo "<td colspan='1' width='90px'>Gera Pedido</td>\n";
					echo "</tr>\n";
				}

				$cor = ($j % 2 == 0) ? "#F1F4FA": '#F7F5F0';

				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='left'>";
				echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
				echo "</td>\n";
				echo "<td>";
				if ($ativo == 't') echo "Ativo";
				else               echo "Inativo";
				echo "<td>";
				if ($gera_pedido == 't') echo "Sim";
				else               echo "Não";
				echo "</td>\n";
				echo "</td>\n";
				echo "</tr>\n";

				$linha_nome = $linha_nome_anterior;
			}
			
			echo "</table>\n";
			echo "<br>\n";
		}
	}
}

$sql = "SELECT  servico_realizado ,
				descricao         ,
				gera_pedido       ,
				CASE WHEN ativo = 't' THEN '0' ELSE '1' END as ordem,
				ativo             ,
				solucao,
				garantia_acessorio
		FROM    tbl_servico_realizado
		WHERE   fabrica = $login_fabrica
		AND      linha IS NULL
		AND     ativo
		ORDER BY ordem, descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='700' border='0' class='formulario' cellpadding='2' cellspacing='1' align='center'>\n";
	echo "<tr class='titulo_coluna'>\n";
	echo "<td colspan='1'>Geral</td>\n";
	echo "<td colspan='1' width='90px'>Status</td>\n";
	echo "<td colspan='1' width='90px'>Gera Pedido</td>\n";
	if($login_fabrica==20){
		echo "<td align='left'>Garantia de Acessório</td>";
		echo "<td align='left'>Solução</td>";
		echo "<td align='left'>Espanhol</td>";
	}
	echo "</tr>\n";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$servico_realizado = trim(pg_result($res,$i,servico_realizado));
		$descricao         = trim(pg_result($res,$i,descricao));
		$gera_pedido       = trim(pg_result($res,$i,gera_pedido));
		$ativo             = trim(pg_result($res,$i,ativo));
		$solucao           = trim(pg_result($res,$i,solucao));
		$garantia_acessorio = pg_result($res,$i,garantia_acessorio);

		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
		echo "</td>\n";
		echo "<td>";
		if ($ativo == 't') echo "Ativo";
		else               echo "Inativo";
		echo "</td>\n";
		//hd 22250
		echo "<td>";
			if ($gera_pedido == 't') echo "Sim";
			else               echo "Não";
		echo "</td>\n";
		if($login_fabrica==20){
			echo "<td>";
			if ($garantia_acessorio == 't') echo "Sim";
			else               echo "Não";
			echo "</td>\n";

			echo "<td>";
			if ($solucao == 't') echo "Solução";
			else                 echo "&nbsp;";
			echo "</td>\n";


			$sql2 = "SELECT  descricao
				FROM    tbl_servico_realizado_idioma
				WHERE   servico_realizado = $servico_realizado
				AND     idioma = 'ES'  ";
			$res2 = @pg_exec ($con,$sql2);

			if (pg_numrows($res2) > 0)  echo "<td align='left'>".trim(pg_result($res2,0,descricao))."</td>";
		}
		echo "</tr>\n";
	}
	echo "<tr class='subtitulo'><td colspan='3'>Para efetuar alterações, clique na descrição do serviço realizado</td></tr>";
	echo "</table>\n";
	echo "<br>\n";
}


####  INATIVOS #####
ECHO "<BR><BR>";


$sql =	"SELECT DISTINCT
				tbl_linha.linha ,
				tbl_linha.nome
		FROM     tbl_linha
		WHERE    tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.nome;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$linha      = trim(pg_result($res,$i,linha));
		$linha_nome = trim(pg_result($res,$i,nome));

		$sql2 =	"SELECT  servico_realizado ,
						 descricao         ,
						 gera_pedido	   ,
						 ativo
				FROM     tbl_servico_realizado
				WHERE    fabrica = $login_fabrica
				AND      linha = $linha
				AND      ativo = FALSE
				ORDER BY ativo, descricao;";
		$res2 = pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) {
			for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
				$servico_realizado = trim(pg_result($res2,$j,servico_realizado));
				$descricao         = trim(pg_result($res2,$j,descricao));
				$ativo             = trim(pg_result($res2,$j,ativo));
				$gera_pedido       = trim(pg_result($res2,$f,gera_pedido));

				if ($linha_nome <> $linha_nome_anterior) {
					echo "<table width='300' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
					echo "<tr bgcolor='#D9E2EF'>\n";
					echo "<td colspan='2'><b>$linha_nome</b></td>\n";
					echo "<td colspan='1'><b>Gera Pedido</b></td>\n";
					echo "</tr>\n";
				}

				$cor = ($j % 2 == 0) ? "#F1F4FA": '#FFFFFF';

				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='left'>";
				echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
				echo "</td>\n";
				echo "<td>";
				if ($ativo == 't') echo "Ativo";
				else               echo "Inativo";
				echo "<td>";
				if ($gera_pedido == 't') echo "Sim";
				else               echo "Não";
				echo "</td>\n";
				echo "</td>\n";
				echo "</tr>\n";

				$linha_nome = $linha_nome_anterior;
			}
			echo "</table>\n";
			echo "<br>\n";
		}
	}
}

$sql = "SELECT  servico_realizado ,
				descricao         ,
				gera_pedido       ,
				CASE WHEN ativo = 't' THEN '0' ELSE '1' END as ordem,
				ativo             ,
				solucao
		FROM    tbl_servico_realizado
		WHERE   fabrica = $login_fabrica
		AND      linha IS NULL
		AND     ativo = FALSE
		ORDER BY ordem, descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	ECHO ">>> INATIVOS <<<";
	echo "<table width='700' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
	echo "<tr bgcolor='#D9E2EF'>\n";
	echo "<td colspan='2'><b>Geral</b></td>\n";
	echo "<td colspan='1'><b>Gera Pedido</b></td>\n";
	if($login_fabrica==20){
		echo "<td align='left'>Solução</td>";
		echo "<td align='left'>Espanhol</td>";
	}
	echo "</tr>\n";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$servico_realizado = trim(pg_result($res,$i,servico_realizado));
		$descricao         = trim(pg_result($res,$i,descricao));
		$gera_pedido       = trim(pg_result($res,$i,gera_pedido));
		$ativo             = trim(pg_result($res,$i,ativo));
		$solucao           = trim(pg_result($res,$i,solucao));

		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
		echo "</td>\n";
		echo "<td>";
		if ($ativo == 't') echo "Ativo";
		else               echo "Inativo";
		echo "</td>\n";
		//hd 22250
		echo "<td>";
			if ($gera_pedido == 't') echo "Sim";
			else               echo "Não";
		echo "</td>\n";
		if($login_fabrica==20){
			echo "<td>";
			if ($solucao == 't') echo "Solução";
			else                 echo "&nbsp;";
			echo "</td>\n";


			$sql2 = "SELECT  descricao
				FROM    tbl_servico_realizado_idioma
				WHERE   servico_realizado = $servico_realizado
				AND     idioma = 'ES'  ";
			$res2 = @pg_exec ($con,$sql2);

			if (pg_numrows($res2) > 0)  echo "<td align='left'>".trim(pg_result($res2,0,descricao))."</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}



include "rodape.php";
?>
