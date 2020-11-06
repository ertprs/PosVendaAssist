<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";
include "autentica_admin.php";

include "funcoes.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	




	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if($_POST['baixar']) {

	$qtde = $_POST['qtde'];
	
	$res_os = pg_exec($con,"BEGIN TRANSACTION");
	for ($i=0; $i < $qtde; $i++) {
		
		$selecao = $_POST['selecao_'.$i];

		if (strlen($selecao)>0) {

			$selecionou = 'sim';

			$doc			= $_POST['comprovante_'.$i];
			$data_pagamento	= $_POST['data_pagamento_'.$i];
			$valor			= $_POST['valor_'.$i];
			$hd_chamado     = $_POST['hd_chamado_'.$i];
			if (strlen($doc)==0) {
				$linha = $i+1;
				$msg_erro2 = 'Digite o número do comprovante na linha: '.$linha."<br>";
				$linha_erro = $i;
			}

			if (strlen($data_pagamento)==0) {
				$linha = $i+1;
				$msg_erro2 = 'Digite a data de pagamento linha: '.$linha."<br>";
				$linha_erro = $i;
			} else {
				$data_pagamento2 = $data_pagamento;
				$data_pagamento = formata_data($data_pagamento);
			}

			if (strlen($valor)==0) {
				$linha = $i+1;
				$msg_erro2 = 'Digite o valor: '.$linha."<br>";
				$linha_erro = $i;
			} else {
				 $valor = number_format($valor,2,'.','.');
			}

			if (strlen($msg_erro2)==0) {
				$sql = "UPDATE tbl_hd_chamado_troca set numero_objeto='$doc', valor_corrigido = $valor, data_pagamento = '$data_pagamento', admin_ressarcimento = $login_admin where hd_chamado = $hd_chamado";
				$res = pg_exec($con,$sql);
				$msg_erro2 = pg_errormessage($con);

				$sqlins = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							comentario,
							admin,
							status_item )
							VALUES (
							$hd_chamado,
							'O ressarcimento foi efetivado:<br> <b>Numero comprovante:</b>$doc<br><b>Valor:</b>$valor<br><b>Data do Pagamento:</b>$data_pagamento2',
							$login_admin,
							'Resolvido'
							)";
					$resins = pg_exec($con,$sqlins);
					$msg_erro2 = pg_errormessage($con);

					$sql = "UPDATE tbl_hd_chamado set status = 'Resolvido' where hd_chamado = $hd_chamado";
					$res = pg_exec($con,$sql);
					$msg_erro2 = pg_errormessage($con);
				
			}
		}
	}
	
	if (strlen($selecionou)==0) {
		$msg_erro2 = 'Nenhum registro foi selecionado '."<br>";
		$linha_erro = 'nao';
	}

	if (strlen($msg_erro2)>0) {
		$res_os = pg_exec($con,"rollback");
		$_POST['btn_acao'] = 'Pesquisar';
	} else {
		$res_os = pg_exec($con,"commit");
		echo "<script>alert('Baixas efetuadas com sucesso'); window.location = 'relatorio_ressarcimento.php'; </script>";
	}
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE OS COM RESSARCIMENTO";
include "cabecalho.php";

?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>

<script language='javascript'>

	$(function(){
		$('.mask_date').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
		$('input[id*=data]').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
	});



$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
			
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});
})

</script>

<style type='text/css'>
	
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

.formulario{
background-color:#D9E2EF;
font:11px Arial;
text-align: left;
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

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

</style>
<div id='erro'>
</div>

<?php
	if( strlen( $msg_erro ) >0 )
	{
?>	
	<table width='700px' class='formulario' >
		<tr>
			<td class='msg_erro'><?=$msg_erro?></td>
		</tr>
	</table>
<?php
	
	}

?>
<FORM NAME="frm_pesquisa" METHOD="POST" ACTION="<?echo $PHP_SELF?>">
<br />
<TABLE width="700px" align="center"cellspacing='1' cellpadding='0' class='formulario'>

	<tr>
		<td class='titulo_tabela' colspan='6'>Parâmetros de Pesquisa</td>
	</tr>
<td width='15%'>&nbsp;</td>
	<tr>
		<td colspan='3'><span  style='margin-left:105px;'>Número da OS</span>

		</td>
		<td>Nome do Posto</td>
		</tr>
		
	<tr>
	<td width='15%'>&nbsp;</td>
		<td >
		<INPUT TYPE="TEXT" NAME="os" ID="os" SIZE="17" MAXLENGTH="20" VALUE="<? echo $os ?>" CLASS="frm" />
		</TD>
		<TD width='15%'>
		<?
		if (strlen($_POST['btn_acao'])>0) {
			if (strlen($_POST["ressarcimento_status"]) > 0) {
				$ressarcimento_status = $_POST["ressarcimento_status"];
			}
		}
		else {
				$ressarcimento_status = "abertos";
		}

		?>
		<select name='ressarcimento_status' id='ressarcimento_status' class='frm'>
		<option value='todos' <? if ($ressarcimento_status == "todos") echo "selected"; ?>>Todos</option>
		<option value='abertos' <? if ($ressarcimento_status == "abertos") echo "selected"; ?>>Abertos</option>
		<option value='baixados' <? if ($ressarcimento_status == "baixados") echo "selected"; ?>>Baixados</option>
		</TD>
		
		<td >
		<INPUT TYPE="TEXT" NAME="nome_posto" ID="nome_posto" SIZE="20" VALUE="<? echo $nome_posto ?>" CLASS="frm" />
		</TD>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td width='15%'>&nbsp;</td>
		<td>Data Inicial * </td>
		<td>Data Final * </td>
		<td>Código Posto</td>

	</tr>
	<tr>
	<td width='15%'>&nbsp;</td>
		<td nowrap>
		<INPUT TYPE="TEXT" NAME="data_inicial" ID="data_inicial" SIZE="11" MAXLENGTH="10" VALUE="<? echo $data_inicial ?>" class="frm">
		</td>
		
<td nowrap width='25%'>
		<INPUT TYPE="TEXT" NAME="data_final" ID="data_final" SIZE="11" MAXLENGTH="10" VALUE="<? echo $data_final ?>" CLASS="frm">
		</td>
		<td >
		<INPUT TYPE="TEXT" NAME="codigo_posto" ID="codigo_posto" SIZE="13" VALUE="<? echo $codigo_posto ?>" CLASS="frm">
		</TD>
		
	</tr>
<tr>
	<TD COLSPAN="6" ALIGN="CENTER">
		<BR/>
		<INPUT TYPE="HIDDEN" NAME="btn_acao" VALUE="">
		<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" ALT='Clique AQUI para pesquisar'>
	</TD>
</TR>
</TABLE>

<? 

	if (strlen($_POST['btn_acao'])>0) {
$data_incial	= $_POST['data_inicial'];
$data_final		= $_POST['data_final'];

//Início Validação de Datas
if($data_inicial){
$dat = explode ("/", $data_inicial );//tira a barra
$d = $dat[0];
$m = $dat[1];
$y = $dat[2];
if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
}
if($data_final){
$dat = explode ("/", $data_final );//tira a barra
$d = $dat[0];
$m = $dat[1];
$y = $dat[2];
if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
}
if(strlen($msg_erro)==0){
$d_ini = explode ("/", $data_inicial);//tira a barra
$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


$d_fim = explode ("/", $data_final);//tira a barra
$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

if(strtotime($nova_data_final) < strtotime($nova_data_inicial)){
$msg_erro = "Data Inválida.";
}

 if(strlen($nova_data_inicial) > 0 && strlen($nova_data_final) > 0 && strlen($msg_erro) == 0 ){
    $sql = "SELECT '$nova_data_inicial'::date + interval '1 months' > '$nova_data_final'";
    $res = pg_query($con,$sql);
    $periodo = pg_fetch_result($res,0,0);
    if($periodo == 'f')
        $msg_erro = "O intervalo entre as datas não pode ser maior que 1 mês.";
}

//Fim Validação de Datas
}
		$posto			= trim($_POST['codigo_posto']);
		$os				= trim($_POST['os']);
		$ressarcimento_status = trim($_POST['ressarcimento_status']);

		if (strlen($ressarcimento_status)) {
			switch($ressarcimento_status) {
				case 'todos':
					$sql_ressarcimento_status = "";
				break;

				case 'abertos':
					$sql_ressarcimento_status = " AND tbl_hd_chamado_troca.data_pagamento is null ";
				break;

				case 'baixados':
					$sql_ressarcimento_status = " AND tbl_hd_chamado_troca.data_pagamento is not null ";
				break;
			}
		}
	
		if (strlen($os)>0) {
			$sql_os = " AND os = $os";
		}

		if (strlen($posto)>0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if (pg_num_rows($res)>0) {
				$posto = pg_result($res,0,0);
				$sql_posto = " AND tbl_os.posto = $posto ";
			}
		}

		if (strlen($data_inicial)==0 or strlen($data_final)==0) {
			$msg_erro = 'Informe a data Inicial e Final';
		}
		
		if (strlen($msg_erro)==0) {
			$sql = "SELECT	tbl_hd_chamado.hd_chamado,
							tbl_os.os,
							data_pagamento,
							valor_produto,
							tbl_hd_chamado_extra.produto,
							admin_ressarcimento,
							agencia,
							contay,
							favorecido_conta,
							cpf_conta,
							tipo_conta,
							tbl_banco.nome,
							tbl_hd_chamado_troca.valor_corrigido AS valor_corrigido_banco,
							tbl_hd_chamado_troca.numero_objeto AS numero_objeto_banco
						FROM 
							tbl_hd_chamado
							JOIN tbl_hd_chamado_extra USING(hd_chamado)
							JOIN tbl_hd_chamado_troca USING(hd_chamado)
							JOIN tbl_hd_chamado_extra_banco USING(hd_chamado)
							JOIN tbl_banco USING(banco)
							JOIN tbl_os USING(os)
							JOIN tbl_os_troca USING(os)
						WHERE
							tbl_hd_chamado.fabrica = 81
							AND tbl_hd_chamado_troca.ressarcimento is true
							AND tbl_os_troca.ressarcimento is true
							$sql_ressarcimento_status
							AND tbl_os_troca.data between '$nova_data_inicial 00:00:00' and '$nova_data_final 23:59:59'
							$sql_posto
							$sql_os";
			
			$res = pg_exec($con,$sql);
			#echo(nl2br($sql));
			if (pg_num_rows($res)>0) {
				echo"<br />";			
				if (strlen($msg_erro2)>0) {
					echo "<table width='700px' align='center'>
								<tr>
									<td class='msg_erro'>$msg_erro2</td>
								</tr>
						  </table>";
					
					echo "<br>";
				}
				
				echo "<table width='1150px' align='center' cellpadding='1' cellspacing='1' class='formulario'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td>Selecione</td>";
				echo "<td>OS</td>";
				echo "<td>Nome Favorecido</td>";
				echo "<td>CPF Favorecido</td>";
				echo "<td>Tipo Conta</td>";
				echo "<td>Banco</td>";
				echo "<td>Agência</td>";
				echo "<td>Conta</td>";
				echo "<td>Valor</td>";
				echo "<td>Número Comprovante(DOC)</td>";
				echo "<td>Valor Pagamento</td>";
				echo "<td width='10%'  align='center'>Data Pagamento</td>";
				echo "</tr>";

			 for ($i=0; $i < pg_numrows($res); $i++) {
				
				$os = pg_result($res,$i,os);
				$hd_chamado = pg_result($res,$i,hd_chamado);
				$nome = pg_result($res,$i,favorecido_conta);
				$cpf = pg_result($res,$i,cpf_conta);
				$tipo_conta = pg_result($res,$i,tipo_conta);
				$banco = pg_result($res,$i,nome);
				$agencia = pg_result($res,$i,agencia);
				$conta = pg_result($res,$i,contay);
				$valor = pg_result($res,$i,valor_produto);
				$data_pagamento_banco = pg_result($res,$i,data_pagamento);
				$valor_corrigido_banco = number_format(pg_result($res,$i,valor_corrigido_banco), 2, ',', '.');
				$numero_objeto_banco = pg_result($res,$i,numero_objeto_banco);
				
				$valor = number_format($valor,2,',','.');

				$cores++;
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';
				if (strlen($msg_erro2)>0) {
					
					$selecao = $_POST['selecao_'.$i];
					
					if (strlen($selecao)>0) {
						$valor_pagamento = $_POST['valor_'.$i];
						$doc   = $_POST['comprovante_'.$i];
						$data_pagamento = $_POST['data_pagamento_'.$i];
						$checked = "CHECKED";
					} else {
						$checked = '';
					}

					if ($i == $linha_erro) {
						$cor = "#FF3300";
					}
				}
				echo"<input type='hidden' name='hd_chamado_$i' value='$hd_chamado'>";
				echo"<tr bgcolor='$cor'>";
				if (strlen($data_pagamento_banco) > 0) {
					echo "<td></td>";
				}
				else {
					echo"<td><input type='checkbox' $checked class='frm' name='selecao_$i' value='$os'></td>";
				}
				echo"<td><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$os</a></td>";
				echo"<td>$nome</td>";
				echo"<td>$cpf</td>";
				echo"<td>$tipo_conta</td>";
				echo"<td>$banco</td>";
				echo"<td>$agencia</td>";
				echo"<td>$conta</td>";
				echo"<td>R$ $valor</td>";

				if (strlen($data_pagamento_banco) > 0) {
					echo"<td>$numero_objeto_banco</td>";
					echo"<td nowrap>R$ $valor_corrigido_banco</td>";
					$data_pagamento_banco = implode("/", array_reverse(explode("-", $data_pagamento_banco)));
					echo"<td nowrap>$data_pagamento_banco</td>";
				}
				else {
					echo"<td><input type='text' size='20' class='frm' name='comprovante_$i' value='$doc'></td>";
					echo"<td nowrap>R$ <input type='text' size='10' class='frm' name='valor_$i' value='$valor_pagamento'></td>";
					echo"<td nowrap width='10%' align='center'><input type='text' size='10' class='frm' name='data_pagamento_$i' id='data_pagamento_$i' value='$data_pagamento'></td>";
				}
				echo"</tr>";
			 }

				echo"<tr>";

			 if ($ressarcimento_status == "baixados") {
				echo "<td colspan='13'></td>";
			 }
			 else {
				echo"<td colspan='13' align='center'><b><input type='submit' value='baixar selecionadas' name='baixar'><input type='hidden' name='qtde' id='qtde' value='$i'></td>";
			 }

				echo"</tr>";
				echo"</table>";
			}else{
				echo "<center>Nenhum registro encontrado </center>";
			}
		} else {
			$htmlErro = "<table align='center' width='700px'>
					<tr>
						<td class='msg_erro'>$msg_erro</td>
					</tr>
				  </table>";
				
				echo "
					<script type='text/javascript'>
						$('#erro').html( \"<table align='center' width='700px'><tr><td class='msg_erro'>$msg_erro</td></tr></table>\" );
					</script>";
		}

	}
?>
</form>
<?php

	include "rodape.php";
?>
