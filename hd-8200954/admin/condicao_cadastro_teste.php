<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_GET["condicao"]) > 0)  $condicao1 = trim($_GET["condicao"]);
if (strlen($_POST["condicao"]) > 0) $condicao = trim($_POST["condicao"]);

if (strlen($_POST["acrescimo_financeiro"]) > 0) $acrescimo_financeiro = trim($_POST["acrescimo_financeiro"]);

#Foi retirada as opções de gravação/alteração pois a Rúbia já cadastrou 3 vezes erradamente 
#e tivemos que fazer o inverso na mão. Retirado a pedido do Samuel 07/11/2007 Ex.: HD7200
if ($btnacao == "gravar" AND $login_fabrica <> 1) {
	$codigo_condicao = trim($_POST["codigo_condicao"]);
	$descricao       = trim($_POST["descricao"]);
	$visivel         = trim($_POST["visivel"]);
	$tabela          = trim($_POST["tabela"]);
	$promocao        = trim($_POST["promocao"]);
	$limite_minimo   = trim($_POST["limite_minimo"]);
	$tipo_posto      = trim($_POST["tipo_posto"]);

	if(strlen($promocao)>0){
		$xpromocao = "'$promocao'";
	}else{
		$xpromocao = "'f'";
	}

	if(strlen($limite_minimo)>0){
		$xlimite_minimo = "$limite_minimo";
//		$xlimite_minimo = str_replace(",",".",$xlimite_minimo);
		$xlimite_minimo = str_replace(",",".",$xlimite_minimo);
//		echo "$xlimite_minimo";
	}else{
		$xlimite_minimo = 'null';
	}

	if ($acrescimo_financeiro == 't') {
		$acrescimo = trim($_POST["acrescimo"]);
		if (strlen($acrescimo) == 0) $msg_erro = "Favor informar o percentual de acréscimo financeiro para esta condição de pagamento";
		if (strpos($acrescimo,",") > 0 OR strpos($acrescimo,".") > 0) {
			$xacrescimo = str_replace(".","",$acrescimo);
			$xacrescimo = str_replace(",","",$xacrescimo);
			$xacrescimo = ($xacrescimo / 1000) + 1;
			$xacrescimo = str_replace(",",".",$xacrescimo);
		}else{
			$xacrescimo = ($acrescimo / 100) + 1;
			$xacrescimo = str_replace(",",".",$xacrescimo);
		}
	}
	
	if (strlen($codigo_condicao) == 0) $msg_erro = "Digite o código da condição de pagamento";
	if (strlen($descricao) == 0)       $msg_erro = "Digite a descrição da condição de pagamento";
	
	if (strlen($tabela) == 0) {
		$tabela = 'null';
	}
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	if(strlen($msg_erro) == 0){
		if (strlen($condicao) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_condicao (
						fabrica        ,
						codigo_condicao,
						descricao      ,
						visivel        ,
						limite_minimo  ,
						tabela ";
			
			if ($acrescimo_financeiro == 't') $sql .= ", acrescimo_financeiro ";
			if(strlen($promocao)>0){$sql .= ", promocao ";}
			$sql .= ") VALUES (
						$login_fabrica    ,
						'$codigo_condicao',
						'$descricao'      ,
						'$visivel'        ,
						$xlimite_minimo   ,
						$tabela ";
			
			if ($acrescimo_financeiro == 't') $sql .= ", $xacrescimo ";
			if(strlen($promocao)>0){$sql .= ", $xpromocao ";}
			
			$sql .= ")";
			
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_condicao SET
							codigo_condicao = '$codigo_condicao',
							descricao       = '$descricao'      ,
							visivel         = '$visivel'        ,
							tabela          = $tabela           ,
							limite_minimo   = $xlimite_minimo   ,
							promocao        = $xpromocao ";
			
			if ($acrescimo_financeiro == 't') $sql .= ", acrescimo_financeiro = $xacrescimo ";
			$sql .= "WHERE tbl_condicao.condicao = $condicao
					AND    tbl_condicao.fabrica  = $login_fabrica;";

//			echo $sql;
		}
//		if($login_fabrica<>1){//retirado alteracao para black, pois Rúbia fez bastante cagada takashi27/09 hd 5042
			$res = pg_exec ($con,$sql);
//		}
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if(!empty($msg_erro))
			header ("Location: $PHP_SELF?msg=$msg_erro");
		else{
			$sql = "SELECT condicao FROM tbl_condicao WHERE descricao = '$descricao' and fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			$codigo_condicao = pg_result($res,0,condicao);
			$sql1 = "SELECT fn_tipo_posto_condicao_teste($login_fabrica,$codigo_condicao,$tipo_posto,$tabela,'$visivel')";
			echo $sql1; exit;
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		}
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index") > 0) $msg_erro = "Condição de Pagamento já cadastrada nesta tabela.";
		
		$codigo_condicao = trim($_POST["codigo_condicao"]);
		$descricao       = trim($_POST["descricao"]);
		$visivel         = trim($_POST["visivel"]);
		$tabela          = trim($_POST["tabela"]);
		$promocao        = trim($_POST["promocao"]);
		$limite_minimo   = trim($_POST["limite_minimo"]);
		$tipo_posto      = trim($_POST["tipo_posto"]);
		
		if ($acrescimo_financeiro == 't') $acrescimo = trim($_POST["acrescimo"]);
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}else{
	/*30556 - PARA A BLACK NÃO PODE CADASTRAR, TEM QUE ALTERAR A FUNÇÃO DE FINALIZA PEDIDO - ESTÁ FIXO*/
	if($login_fabrica == 1 AND $btnacao == "gravar"){

		$email_origem  = "helpdesk@telecontrol.com.br";
		$email_destino = "helpdesk@telecontrol.com.br, igor@telecontrol.com.br";
		$assunto       = "URGENTE - CADASTRO CONDIÇÃO";

		$corpo.="<br>O admin $login_admin- está tentando cadastrar uma nova condição de pagamento para a Blackedecker, favor entrar em contato o mais rápido possível e proceder da seguinte forma:\n\n";
		$corpo.="<br> 1) Verificar a nova condição de pagamento \n<BR>
		<BR>2) Inserir esta condição manualmente\n
		<BR>3) Alterar a função funcao_finaliza_pedido.sql<BR>
		<BR> Condição: $codigo_condicao  - 	$descricao       \n";

		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
			
		}
	}
}

#Foi retirada as opções de gravação/alteração pois a Rúbia já cadastrou 3 vezes erradamente 
#e tivemos que fazer o inverso na mão. Retirado a pedido do Samuel 07/11/2007 Ex.: HD7200
if ($btnacao == "deletar" and strlen($condicao) > 0 AND $login_fabrica <> 1) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_condicao WHERE condicao = $condicao AND fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if(strlen($msg_erro)>0){
		$msg_erro = "Condição Sendo Usada no Sistema!";
	}
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?condicao=$condicao&msg=Apagado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$codigo_condicao = trim($_POST["codigo_condicao"]);
		$descricao       = trim($_POST["descricao"]);
		$visivel         = trim($_POST["visivel"]);
		$tabela          = trim($_POST["tabela"]);
		$promocao        = trim($_POST["promocao"]);
		$limite_minimo   = trim($_POST["limite_minimo"]);

		if ($acrescimo_financeiro == 't') $acrescimo = trim($_POST["acrescimo"]);
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($condicao) > 0) {
	$sql = "SELECT  tbl_condicao.codigo_condicao     ,
					tbl_condicao.descricao           ,
					tbl_condicao.visivel             ,
					tbl_condicao.tabela              ,
					tbl_condicao.acrescimo_financeiro,
					tbl_condicao.promocao            ,
					tbl_condicao.limite_minimo       ,
					tbl_tabela.tabela
			FROM    tbl_condicao
			LEFT JOIN tbl_tabela ON tbl_condicao.tabela = tbl_tabela.tabela
			WHERE   tbl_condicao.condicao    = $condicao
			AND     tbl_condicao.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo_condicao = trim(pg_result($res,0,codigo_condicao));
		$descricao       = trim(pg_result($res,0,descricao));
		$visivel         = trim(pg_result($res,0,visivel));
		$acrescimo       = trim(pg_result($res,0,acrescimo_financeiro));
		$tabela          = trim(pg_result($res,0,tabela));
		$promocao        = trim(pg_result($res,0,promocao));
		$limite_minimo   = trim(pg_result($res,0,limite_minimo));
	}
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE CONDIÇÃO DE PAGAMENTO";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;

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

table.tabela tr td{
	font-family: verdana; 
	font-size: 11px; 
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
</style>

<br />
<FORM METHOD=POST NAME="frm_condicao" ACTION="<? echo $PHP_SELF; ?>">
<input type='hidden' name='condicao' value='<? echo $condicao; ?>'>
<input type='hidden' name='acrescimo_financeiro' value='<? echo $acrescimo_financeiro; ?>'>
<input type='hidden' name='btnacao' value=''>

<table width="700px" class="formulario" cellpadding="0" align='center' cellspacing="1" border="0">
	<? if(strlen($msg_erro) > 0){ ?>
		<TR class="msg_erro">
			<TD colspan="2"><? echo $msg_erro; ?></TD>
		</TR>
	<? } ?>

	<? if(strlen($msg) > 0){ ?>
		<TR class="sucesso">
			<TD colspan="2"><? echo $msg; ?></TD>
		</TR>
	<? } ?>

	<tr class="titulo_tabela">
		<td colspan="4">Cadastro</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<TR align="left">
		<TD style="width:280px;"><p style="margin-left:40%" >Código&nbsp;<input type="text" name="codigo_condicao" value="<? echo $codigo_condicao ?>" size="10" maxlength="10" class="frm"></p></TD>
		<TD>Descrição&nbsp;<input type="text" name="descricao" value="<? echo $descricao?>" size="40" maxlength="20" class="frm"></TD>
	</TR>
</table>

<table width="700px" class="formulario" cellpadding="0" align='center' cellspacing="1" border="0">
	<TR>
		<td width="10%">&nbsp;</td>
		<TD align="right" style="width:85px;">
			Visível &nbsp;
			<?php
				if($login_fabrica != 1){
						echo '
							<td align="left" style="width:110px;">
								<SELECT NAME="visivel" class="frm">';
									if($visivel == 't'){
										echo "<option value='t' selected>Sim</option><option value='f'>Não</option>";
									}else{
										echo "<option value='t'>Sim</option><option value='f' selected>Não</option>";
									}
						echo '	</SELECT>
							 </td>';
					}
			?>
		</TD>
			<?
				if($login_fabrica==1 or $login_fabrica==5 or $login_fabrica==72 or $login_fabrica==30 or $login_fabrica>87 )
					echo "<td align='left' style='width:66px;'>Valor Mínimo</td>\n";

				if($login_fabrica==1)
					echo "<td align='left' style='width:120px;'>Condição de Promoção?</td>\n";

				if ($acrescimo_financeiro == 't') {

					echo "<td align='left'>Acréscimo Financeiro</td>\n</tr>";
					echo '<tr>'; // layout black&decker

					if (strlen($acrescimo) > 0){
						$xacrescimo_financeiro = ($acrescimo - 1) * 100;
						$xacrescimo_financeiro = str_replace(".",",",$xacrescimo_financeiro);
					}
					if($login_fabrica == 1){
						echo '<td width="10%">&nbsp;</td>
							<td colspan="">
								<SELECT NAME="visivel" class="frm">';
									if($visivel == 't'){
										echo "<option value='t' selected>Sim</option><option value='f'>Não</option>";
									}else{
										echo "<option value='t'>Sim</option><option value='f' selected>Não</option>";
									}
						echo '	</SELECT>
							 </td>';
					}
					if($login_fabrica==1 or $login_fabrica==5 or $login_fabrica==72 or $login_fabrica==30 or $login_fabrica>87){
						echo "<td nowrap align='left'><INPUT TYPE='text' class='frm' align = 'right' size='5' NAME='limite_minimo' value='$limite_minimo' ></td>";
					}
					echo "<td nowrap align='center' style='padding-right:30px;'><input type='checkbox' name='promocao'";
					if ($promocao == 't' ){ echo " checked "; }
					echo " value='t'> Sim</td>\n";
					echo "<td align='left' nowrap style='width:110px;'><input class='frm' type='text' name='acrescimo' value='$xacrescimo_financeiro' size='5' maxlength=''> %</td>\n";
				}
				if($login_fabrica==30){
						echo "<td nowrap align='left' colspan='2'><INPUT TYPE='text' class='frm' align = 'left' size='5' NAME='limite_minimo' value='$limite_minimo' ></td>";
					}
			?>

	</tr>
	<tr>
		<td width="10%">&nbsp;</td>
		<td>
			Tipo Posto
		</td>
		<td>
			<select name="tipo_posto" class="frm">
				<option selected></option>
				<?
				$sql = "SELECT * 
						FROM tbl_tipo_posto 
						WHERE fabrica = $login_fabrica
						ORDER BY descricao ASC";
				$res = @pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					for($i=0; $i<pg_numrows($res); $i++){
						echo "<option value='".pg_result($res,$i,tipo_posto)."' ";
						if (pg_result($res,$i,tipo_posto) == $tipo_posto) echo " selected";
						echo ">".pg_result($res,$i,descricao)."</option>";
					}
				}
				?>
			</select>
		</td>

	<?	if($login_fabrica<>1){?>

		<TD align="rigth" colspan="2" style='width:66px;'><p>Tabela</td>
		<td align='left'>
		<SELECT NAME="tabela" class="frm">
			
			<option value='' selected>Selecione</option>
				<?

					if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica > 87) {
						$sql_and = 'AND ativa is true';
					}
						
					$sql = "SELECT  tabela, 
									sigla_tabela,
									descricao
							FROM	tbl_tabela
							WHERE	fabrica = $login_fabrica
							$sql_and
							ORDER BY tabela";
					$res = @pg_exec ($con,$sql);
					
					for ($i=0; $i < pg_numrows($res); $i++) {
						$tabelaT      = trim(pg_result($res,$i,tabela));
						$sigla_tabela = trim(pg_result($res,$i,sigla_tabela));
						$descricao    = trim(pg_result($res,$i,descricao));
						
						if($tabela == $tabelaT)
							$sel = "selected";
						else
							$sel = "";
						echo "<option value='$tabelaT' $sel>";if ($login_fabrica == 14 or $login_fabrica == 66) echo $sigla_tabela.' - '; echo "$descricao</option>";
					}
				?>
			</SELECT></p>
		</TD>
		<?}?>
	</tr>
</table>
<table width="700px" class="formulario" cellpadding="3" align='center' cellspacing="2" border="0">
<TR>

<TD align="center"><input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_condicao.btnacao.value == '' ) { document.frm_condicao.btnacao.value='gravar' ; document.frm_condicao.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0'>
<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: if (document.frm_condicao.btnacao.value == '' ) { document.frm_condicao.btnacao.value='deletar' ; document.frm_condicao.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar" border='0'>
<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0'></TD>

</TR>
</table>

<BR>

<table width="700px" class="tabela" cellpadding="2" align='center' cellspacing="1" border="0">
	<TR class="titulo_coluna">
		<TD>Código</TD>
		<TD>Descrição</TD>
		<TD>Acréscimo Financeiro</TD>
		<TD>Promoção</TD>
		<td>Limite Mínimo</TD>
	</TR>
<?
/*
 * Query estava sendo sobrescrita logo abaixo.
	$sql = "SELECT  tbl_condicao.condicao       ,
					tbl_condicao.codigo_condicao,
					tbl_condicao.descricao
			FROM    tbl_condicao
			WHERE   tbl_condicao.fabrica = $login_fabrica
			ORDER BY lpad(codigo_condicao::char(10),10,0);";
*/

	$sql = "SELECT  tbl_condicao.condicao       ,
					tbl_condicao.codigo_condicao,
					tbl_condicao.descricao           ,
					tbl_condicao.visivel             ,
					tbl_condicao.tabela              ,
					tbl_condicao.acrescimo_financeiro,
					tbl_condicao.promocao            ,
					tbl_condicao.limite_minimo       ,
					tbl_tabela.tabela
			FROM    tbl_condicao
			LEFT JOIN tbl_tabela ON tbl_condicao.tabela = tbl_tabela.tabela
			WHERE   tbl_condicao.fabrica = $login_fabrica
			ORDER BY lpad((codigo_condicao::char(10))::text,10,'0');";

	$res = @pg_exec ($con,$sql);
	
	for ($i=0; $i < pg_numrows($res); $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		$condicao			  = trim(pg_result($res,$i,condicao));
		$codigo_condicao	  = trim(pg_result($res,$i,codigo_condicao));
		$descricao			  = trim(pg_result($res,$i,descricao));
		if ($acrescimo_financeiro == 't') {
			$xacrescimo_financeiro = trim(pg_result($res,$i,acrescimo_financeiro));
			$xacrescimo_financeiro = ($xacrescimo_financeiro - 1) * 100;
		}
		$promocao   		  = trim(pg_result($res,$i,promocao));
		$limite_minimo			= trim(pg_result($res,$i,limite_minimo));
		echo "<TR  bgcolor='$cor' style='font:bold 12px Arial;'>
		<TD nowrap>$codigo_condicao</TD>
		<TD align=left nowrap><a href='$PHP_SELF?condicao=$condicao'>$descricao</a></TD>
		<td nowrap align=right>$xacrescimo_financeiro%</td><td>";
		if($promocao=='t'){
			echo "promoção";
		}else{
			?>&nbsp;<?
		}
		echo "</td><td nowrap align=right>".number_format($limite_minimo,2,',','.')."</td></TR>";

	}
?>
</table>

</FORM>
<br />
<? if(strlen($condicao1) > 0){ ?>
	<table width='700' align='center' class='tabela' cellspacing='1'>
		<tr class='titulo_tabela'><th colspan='2'>Tipo de Postos para Condição de Pagamento<th></tr>
		<tr class='titulo_coluna'><th>Código</th><th>Condição</th><th>Tipo Posto</th>
		<?
			$sql = " SELECT distinct condicao, descricao,
							tipo_posto
							from tbl_posto_condicao
							join tbl_posto_fabrica on tbl_posto_condicao.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
							join tbl_tabela on tbl_posto_condicao.tabela = tbl_tabela.tabela
							where tbl_posto_fabrica.fabrica = $login_fabrica
							and tbl_posto_condicao.condicao = $condicao1";
			
			$res = pg_exec($con,$sql);
			$total = pg_numrows($res);
			for($i=0;$i<$total;$i++){
				$condicao_cod   = pg_result($res,$i,condicao);
				$condicao_desc  = pg_result($res,$i,descricao);
				$posto_tipo    = pg_result($res,$i,tipo_posto);
				$cor = ($i % 2 ) ? "#F7F5F0" : '#F1F4FA';  
				
				echo "<tr bgcolor='$cor'>";
				echo "<td>$condicao_cod</td><td>$condicao_desc</td> <td>$posto_tipo</td>";
				echo "</tr>";
			}
		?>
	</table>
<? } ?>
<?	include "rodape.php"; ?>
