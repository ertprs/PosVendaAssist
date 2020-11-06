<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$msg_erro = "";

$btn_acao   = $_POST['btn_acao'];
$despesas   = $_POST['despesas'];
$controle   = $_POST['controle'];
$observacao = $_POST['observacao'];
if(strlen($_POST['os']) > 0) $os         = $_POST['os'];
if(strlen($_GET['os']) > 0) $os         = $_GET['os'];


if(strlen($despesas) > 0){
	$despesas = str_replace(',', '.', $despesas);
}

##### A Ç Ã O   G R A V A R #####
if ($btn_acao == 'gravar') {

	if(strlen($despesas) == 0 OR $despesas > 6){
		if(strlen($despesas) == 0) $msg_erro = 'Entre com o valor da despesa';
		if($despesas > 6) $msg_erro = 'O valor ultrapassa o máximo permitido. R$6,00';
	}

	if(strlen($controle) == 0){
		$msg_erro = 'O campo Controle esta vazio';
	}else{
		#### VALIDAÇÕES DO NÚMERO DE RASTREAMENTO DO OBJETO DOS CORREIOS. EX: RB123456789BR
		$controle_1  = substr($controle,11,2);
		$controle_2 = substr($controle,0,1);
		if($controle_1<>'BR'){
			$msg_erro = 'Verifique o final do número de rastreamento da Carta Registrada';
		}

		if(strlen($controle) != 13){
			$msg_erro = 'Verifique o tamanho do número de rastreamento da Carta Registrada';
		}
	}

	if(strlen($os) > 0){
		$sql = "SELECT  tbl_os_sedex.finalizada    
					FROM tbl_os_sedex 
				WHERE tbl_os_sedex.fabrica        = $login_fabrica
				AND   tbl_os_sedex.posto_origem   = $login_posto
				AND   tbl_os_sedex.sua_os_destino = 'CR'
				AND   tbl_os_sedex.sua_os_origem  = '$os'; ";
		$res = pg_exec($con, $sql);

//		if(pg_numrows($res) > 0) $msg_erro = 'As informações já foram gravadas.';
		
		$sql = "SELECT data_abertura FROM tbl_os WHERE fabrica = $login_fabrica AND os = '$os'; ";
		$res = pg_exec($con,$sql);

		$data_abertura = pg_result($res,0,data_abertura);

		if(strlen($data_abertura) > 0){
			$sql = "SELECT SUM(current_date - data_abertura)as final FROM tbl_os WHERE os = '$os' ;";
			$res = pg_exec($con,$sql);
			$qtde_dias = pg_result($res,0,'final');

			if($qtde_dias < 16) $msg_erro = 'A OS foi aberta há apenas '. $qtde_dias;

		}else{
			$msg_erro = 'OS não finalizada';
		}
	}

	if(strlen($msg_erro) == 0){

		$sql = "SELECT os_sedex,sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = '$os' AND fabrica = $login_fabrica ;";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res) > 0){
			$os_sedex = pg_result($res,0,os_sedex);
			$sql = "UPDATE tbl_os_sedex 
						set finalizada = current_timestamp ,
							controle   = '$controle'       ,
							despesas   = '$despesas'       ,
							obs        = '$observacao'
					WHERE os_sedex = $os_sedex AND sua_os_origem = '$os' AND fabrica = $login_fabrica";

			$res = pg_exec($con,$sql);
		}else{
			$sql = "INSERT INTO tbl_os_sedex (
								fabrica           ,
								posto_origem      ,
								posto_destino     ,
								finalizada        ,
								data              ,
								despesas          ,
								controle          ,
								sua_os_origem     ,
								sua_os_destino    ,
								admin             ,
								obs
							) VALUES (
								$login_fabrica    ,
								$login_posto      ,
								'6901'            ,
								current_timestamp ,
								current_date      ,
								'$despesas'       ,
								'$controle'       ,
								$os               ,
								'CR'              ,
								'635'             ,
								'$observacao'
							)";
			$res = pg_exec($con,$sql);
			$msg_erro = 'Gravado com sucesso!';
		}

			// adicionado por Fernando - somente para ver se deu erro na hora de gravar as inf da CR
			$email_origem  = "fernando@telecontrol.com.br";
			$email_destino = "fernando@telecontrol.com.br";
			$assunto       = "CADASTRO - CARTA REGISTRADA";
			$corpo.="<br>Login posto: $login_posto";
			$corpo.="<br>Despesas: $despesas ";
			$corpo.="<br>Observação: $observacao";
			$corpo.="<br>Controle : $controle";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ); 
			// fim
	}
}


$title     = "Carta Registrada";
$cabecalho = "Carta Registrada";
$layout_menu = 'os';

include "cabecalho.php";
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.Tabela{
	border:1px solid #D5D7D9;
	font-weight: bold;
}


</style>

<? 
if (strlen ($msg_erro) > 0) {//mensagem de erro
?>
	<br>
	<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
	<tr>
		<td valign="middle" align="center" class='error'>
	<? 
		echo $msg_erro;
	?>
		</td>
	</tr>
	</table>
	<br>
<? } 
?>

<TABLE border='1' align='center' bordercolor='000000' cellspacing='0' width='500' style='font-family: verdana; font-size: 12px;'>
<TR>
	<TD align='center' style='font-weight: bold; color:#FFFFFF;' bgcolor='#9DB6FF'>INFORMATIVO</TD>
</TR>
<TR>
	<TD align='center' ><B>
		Se o cliente foi avisado que seu produto já está pronto para a retirada e após quinze dias ainda não foi retirá-lo 
		deve-se enviar uma carta registrada, formalizando para o consumidor que seu produto já foi consertado e está pronto 
		para retirada. Gentileza informar os dados abaixo para que possa ser ressarcido desse custo. 
		<br><br>*<u>Lembrando que, é necessário o envio do comprovante dos correios assim que essa despesa for aprovada no extrato de serviços.</u></B>
	</TD>
</TR>
</TABLE>

<?
if(strlen($os) > 0 ){

	$sql = "SELECT  finalizada    ,
					despesas      ,
					controle      ,
					obs
				FROM tbl_os_sedex
			WHERE fabrica        = $login_fabrica
			AND   posto_origem   = $login_posto
			AND   sua_os_destino = 'CR'
			AND   sua_os_origem  = '$os'; ";
	$res = pg_exec($con, $sql);
	
	if(pg_numrows($res) > 0 ){
		$despesas   = pg_result($res,0,despesas);
		$controle   = pg_result($res,0,controle);
		$observacao = pg_result($res,0,obs);
		$finalizada = pg_result($res,0,finalizada);
	}

	$sql = "SELECT  data_abertura            ,
					sua_os                   ,
					upper(consumidor_nome) as consumidor_nome     ,
					consumidor_estado        ,
					upper(consumidor_cidade) as consumidor_cidade ,
					consumidor_fone          ,
					consumidor_endereco      ,
					consumidor_cep           ,
					consumidor_numero        ,
					consumidor_bairro        ,
					consumidor_complemento   ,
					revenda_nome             ,
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY')    AS data_abertura    ,
					TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY')    AS data_fechamento
				FROM tbl_os
			WHERE os = '$os'
			AND fabrica = $login_fabrica ;";
	$res = pg_exec($con, $sql);

	

	if(pg_numrows($res) > 0){
		$data_abertura          = pg_result($res,0,data_abertura);
		$sua_os                 = pg_result($res,0,sua_os);
		$consumidor_nome        = pg_result($res,0,consumidor_nome);
		$consumidor_estado      = pg_result($res,0,consumidor_estado);
		$consumidor_cidade      = pg_result($res,0,consumidor_cidade);
		$consumidor_fone        = pg_result($res,0,consumidor_fone);
		$consumidor_endereco    = strtoupper(pg_result($res,0,consumidor_endereco));
		$consumidor_cep         = pg_result($res,0,consumidor_cep);
		$consumidor_numero      = pg_result($res,0,consumidor_numero);
		$consumidor_complemento = pg_result($res,0,consumidor_complemento);
		$consumidor_bairro      = strtoupper(pg_result($res,0,consumidor_bairro));
		$data_fechamento        = pg_result($res,0,data_fechamento);
		$revenda_nome           = strtoupper(pg_result($res,0,revenda_nome));
		$data_abertura          = pg_result($res,0,data_abertura);
		$data_fechamento        = pg_result($res,0,data_fechamento);

		$data_abertura = substr ($data_abertura,8,2) . "/" . substr ($data_abertura,5,2) . "/" . substr ($data_abertura,0,4) ;

		if(strlen($data_fechamento)     == 0){$data_fechamento = 'Aberta';}
		if(strlen($consumidor_cep)      == 0){$consumidor_cep = '-';}
		if(strlen($consumidor_fone)     == 0){$consumidor_fone = '-';}
		if(strlen($consumidor_endereco) == 0){$consumidor_endereco = '-';}
		if(strlen($consumidor_bairro)   == 0){$consumidor_bairro = '-';}
		if(strlen($consumidor_cidade)   == 0){$consumidor_cidade = '-';}
		if(strlen($consumidor_estado)   == 0){$consumidor_estado = '-';}

		//echo "$data_abertura - $sua_os - $consumidor_nome - $consumidor_estado - $consumidor_cidade - $consumidor_fone - $consumidor_endereco - $consumidor_numero - $consumidor_bairro";

		$cor_top = '#596D9B';//cor do titulo
//		echo $finalizada;
		if(strlen($finalizada) > 0 ) $readonly = 'readonly';

		echo "<form name='frmdespesa' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='os' value='$os'>";

		echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center'>";
		echo "<tr style='font-family: verdana; font-size: 10px; color:#FF0000;'>";
			echo "<td align='center'><FONT SIZE=\"2\" COLOR=\"#676767\"><B>Digite o número do objeto(controle) e as despesas com a Carta Registrada.</B></FONT><br>*As informações depois de gravadas, não poderão ser modificadas.</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";

		echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center'>";
		echo "<tr class='menu_top' style='font-weight: bold;'>";
			echo "<td>Controle de objeto</td>";
			echo "<td>Despesas</td>";
		echo "</tr>";
		echo "<tr class='table_line'>";
			echo "<td align='center' class='tabela'>";
				echo "<input type='text' name='controle' value='$controle' size=16 $readonly >\n";
			echo "</td>";
			echo "<td align='center' class='tabela'> R$ ";
				echo "<input type='text' name='despesas' value='$despesas' size=10 $readonly>\n";
			echo "</td>";
		echo "</tr>";
		echo "<tr class='menu_top' align='center' style='font-weight: bold;'>";
			echo "<td colspan='2'>Observação <i>(opcional)</i></td>";
		echo "</tr>";

		echo "<tr>";
			echo "<td align='center' colspan='2' class='tabela'>";
			echo "<input type='text' name='observacao' value='$observacao' size = '100' $readonly>";
			echo "</td>";
		echo "</tr>";
		if(strlen($finalizada) == 0){
			echo "<tr align='center'>";
				echo "<input type='hidden' name='btn_acao' value='0'>";
				?><td colspan='2'><br><img src='imagens/btn_gravar.gif' style='cursor: hand;' onclick="javascript: if ( document.frmdespesa.btn_acao.value == '0' ) { document.frmdespesa.btn_acao.value='gravar'; document.frmdespesa.submit() ; } else { alert ('Aguarde submissão...'); }"></td><?
			echo "</tr>";
		}else{
			echo "<tr>";
				echo "<td align='center' colspan='2' style='font-family: verdana; font-size: 10px; color: #AE4F51'><br>Informações já gravadas</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br><br>";

		echo "<TABLE border='0' align='center' cellspacing='1' cellpadding='0' width='600'>";

		echo "<TR align='center' class='menu_top' style='font-family: verdana; font-size: 14px;' bgcolor='$cor_top' >";
			echo" <TD colspan='4'><B>INFORMAÇÕES SOBRE A OS</B></TD>";
		echo "</TR>";
		
		echo "<TR style='font-family: verdana; font-size: 12px; ' align='center' class='menu_top'>";
			echo "<TD>OS</TD>";
			echo "<TD>Data Abertura</TD>";
			echo "<TD>Data Finalizada</TD>";
			echo "<TD>Revenda</TD>";
		echo "</TR>";

		echo "<tr style='font-family: verdana; font-size: 12px;' align='center'>";
			echo "<td class='tabela'>$sua_os</td>";
			echo "<td class='tabela'>$data_abertura</td>";
			echo "<td class='tabela'>$data_fechamento</td>";
			echo "<td class='tabela'>$revenda_nome</td>";
		echo "</tr>";

		echo "<tr style='font-family: verdana; font-size: 12px;' align='center' class='menu_top'> ";
			echo "<td colspan='2'>Consumidor</td>";
			echo "<td>Telefone</td>";
			echo "<td>CEP</td>";
		echo "</tr>";
		
		echo "<tr style='font-family: verdana; font-size: 12px;' align='center'>";
			echo "<td colspan='2' class='tabela'>$consumidor_nome</td>";
			echo "<td class='tabela'>$consumidor_fone</td>";
			echo "<td class='tabela'>$consumidor_cep</td>";
		echo "</tr>";

		echo "<tr style='font-family: verdana; font-size: 12px;' align='center' class='menu_top'> ";
			echo "<td colspan='2'>Endereco</td>";
			echo "<td>Bairro</td>";
			echo "<td>Cidade</td>";
		echo "</tr>";

		echo "<tr style='font-family: verdana; font-size: 12px;' align='center'>";
			echo "<td colspan='2' class='tabela'>$consumidor_endereco $consumidor_complemento</td>";
			echo "<td class='tabela'>$consumidor_bairro</td>";
			echo "<td class='tabela'>$consumidor_cidade - $consumidor_estado</td>";
		echo "</tr>";
	}
	echo "</TABLE>";
	echo "</FORM>";
	echo "<br><br>";
}
?>

<?include "rodape.php";?>
