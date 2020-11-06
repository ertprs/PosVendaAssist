<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'ajax_cabecalho.php';
include 'autentica_usuario_empresa.php';

$conta_receber	= trim($_GET["receber"]);
$cliente		= trim($_GET["cliente"]); //para mostrar
$enviarEmail   = trim($_GET['enviarEmail']);// popup para enviar email de cobrança


$msg_erro = '';


// AJAX PARA MOSTRAR O BOLETO
#########################################################################################
if (strlen($conta_receber)>0){
	$sql="SELECT
				tbl_contas_receber.contas_receber,
				tbl_contas_receber.documento,
				to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') as emissao,
				to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') as vencimento,
				tbl_contas_receber.vencimento as vencimento_bd,
				tbl_contas_receber.valor,
				tbl_contas_receber.valor_dias_atraso,
				to_char(tbl_contas_receber.recebimento,'DD/MM/YYYY') as recebimento,
				current_date - tbl_contas_receber.vencimento as dias_vencido,
				tbl_contas_receber.valor_recebido,
				tbl_contas_receber.status,
				tbl_contas_receber.faturamento_fatura,
				tbl_contas_receber.obs,
				tbl_contas_receber.posto,
				tbl_contas_receber.fabrica,
				tbl_contas_receber.cliente,
				tbl_contas_receber.distribuidor,
				tbl_contas_receber.obs          ,
				tbl_pessoa.nome,
				tbl_posto.nome as cedente,
				tbl_caixa_banco.agencia,
				tbl_caixa_banco.banco,
				tbl_caixa_banco.conta,
				tbl_contas_receber.caixa_banco
	FROM tbl_contas_receber 
	JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
	LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
	JOIN tbl_caixa_banco on tbl_caixa_banco.caixa_banco = tbl_contas_receber.caixa_banco
	WHERE  tbl_contas_receber.contas_receber=$conta_receber";
		//		echo $sql;
	$res	= pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$receber_receber		= trim(pg_result($res, 0, contas_receber));
		$receber_documento		= trim(pg_result($res, 0, documento));
		$receber_emissao		= trim(pg_result($res, 0, emissao));
		$receber_vencimento		= trim(pg_result($res, 0, vencimento));
		$receber_vencimento_bd	= trim(pg_result($res, 0, vencimento_bd));
		$receber_dias_vencido	= trim(pg_result($res, 0, dias_vencido));
		$receber_valor			= trim(pg_result($res, 0, valor));
		$receber_valor_dias_atraso= trim(pg_result($res, 0, valor_dias_atraso));
		$receber_recebimento	= trim(pg_result($res, 0, recebimento));
		$receber_valor_recebido	= trim(pg_result($res, 0, valor_recebido));
		$receber_status			= trim(pg_result($res, 0, status));
		$receber_faturamento	= trim(pg_result($res, 0, faturamento_fatura));
		$receber_obs			= trim(pg_result($res, 0, obs));
		$receber_posto			= trim(pg_result($res, 0, posto));
		$receber_fabrica		= trim(pg_result($res, 0, fabrica));
		$receber_cliente		= trim(pg_result($res, 0, cliente));
		$receber_distribuidor   = trim(pg_result($res, 0, distribuidor));
		
		$receber_cliente_nome	= trim(pg_result($res, 0, nome));
		$receber_valor			= number_format($receber_valor,2,'.','');
		$mora_multa				= number_format($mora_multa,2,'.','');
		$valor_reajustado		= number_format($valor_reajustado,2,'.','');
		$valor_multa			= number_format($valor_multa,2,'.','');
		$valor_juros_dia		= number_format($valor_juros_dia,2,'.','');
		$valor_desconto			= number_format($valor_desconto,2,'.','');
		$obs                    = trim(pg_result($res, 0, obs));
		$caixa_banco            = trim(pg_result($res, 0, caixa_banco));
		$agencia  = trim(pg_result($res, 0, agencia));
		$banco    = trim(pg_result($res, 0, banco));
		$conta    = trim(pg_result($res, 0, conta));

		$valor_reajustado = $receber_valor;
		$mora_multa = 0;
	
if($receber_distribuidor<>0 and strlen($receber_distribuidor)>0){
			$sql = "SELECT nome, cidade, estado, endereco, numero, cnpj
					FROM tbl_posto
					where posto = $receber_distribuidor";

			$xsql = "SELECT	nome ,
							cnpj,
							endereco,
							numero,
							cidade,
							estado,
							cep
					FROM tbl_posto
					where posto = $receber_posto";
							//echo $xsql;
			$xres = @pg_exec($con,$xsql);
			if(pg_numrows($res)>0){
				$sacado_nome     = pg_result($xres,0,nome);
				$sacado_cidade   = pg_result($xres,0,cidade);
				$sacado_estado   = pg_result($xres,0,estado);
				$sacado_endereco = pg_result($xres,0,endereco);
				$sacado_numero   = pg_result($xres,0,numero);
				$sacado_cnpj     = pg_result($xres,0,cnpj);
				$sacado_cep      = pg_result($xres,0,cep);
			}


		}else{
			$sql = "SELECT nome ,cidade, estado, endereco,'' as numero, cnpj
					from tbl_fabrica 
					where fabrica = $receber_fabrica";
			

			$xsql = "SELECT	nome ,
							cnpj,
							endereco,
							numero,
							cidade,
							estado,
							cep
					FROM tbl_pessoa 
					where pessoa = $receber_cliente";
							//echo $xsql;
			$xres = @pg_exec($con,$xsql);
			if(pg_numrows($res)>0){
				$sacado_nome     = pg_result($xres,0,nome);
				$sacado_cidade   = pg_result($xres,0,cidade);
				$sacado_estado   = pg_result($xres,0,estado);
				$sacado_endereco = pg_result($xres,0,endereco);
				$sacado_numero   = pg_result($xres,0,numero);
				$sacado_cnpj     = pg_result($xres,0,cnpj);
				$sacado_cep      = pg_result($xres,0,cep);
			}

		}
//		echo $sql;
		$res = @pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$cedente_nome     = pg_result($res,0,nome);
			$cedente_cidade   = pg_result($res,0,cidade);
			$cedente_estado   = pg_result($res,0,estado);
			$cedente_endereco = pg_result($res,0,endereco);
			$cedente_numero   = pg_result($res,0,numero);
			$cedente_cnpj     = pg_result($res,0,cnpj);
		}
		if ($receber_dias_vencido>0){
			$valor_reajustado += $valor_multa;
			$valor_reajustado += $valor_juros_dia*$receber_dias_vencido;
			$valor_reajustado += $valor_custas_cartorio;
			$mora_multa += $receber_valor_dias_atraso*$receber_dias_vencido;
			$mora_multa += $valor_multa;
		}

		$valor_total = $receber_valor+$mora_multa;

		$mora_multa			= number_format($mora_multa,2,'.','');
		$valor_reajustado	= number_format($valor_reajustado,2,'.','');
		$valor				= number_format($valor,2,'.','');

		$valor_multa		= number_format($valor_multa,2,'.','');
		$valor_juros_dia	= number_format($valor_juros_dia,2,'.','');
		$valor_desconto		= number_format($valor_desconto,2,'.','');

		$valor_total		= number_format($valor_total,2,'.','');

		if(strpos($receber_documento, "-")>0){
			$nf = substr($receber_documento, 0, strpos($receber_documento, "-"));
			$doc= substr($receber_documento, (strpos($receber_documento, "-")+1), strlen($receber_documento));
		}else{
			$nf= $receber_documento;
		}
	}
	?>
	<form method='POST' action="<? $PHP_SELF ?>" name='frm_receber'  id='frm_receber'>
	<input type='hidden' name='baixar_conta' value='<? echo $conta_receber; ?>'>

	<table id='boleto' border="1" cellspacing="0" width="700" align='center' style="border-collapse: collapse; border: 1px solid #000000;">

	 <tr>
	  <td colspan="6" width="472">
		<strong><big>Contas a Receber</big></strong>
	  </td>

	  <td>
		<span class='topo'>Vencimento</span><br>
		<span class='campo'>
			<? echo $receber_vencimento; ?> 
		</span>
	  </td>
	 </tr>

	 <tr>
	  <td width="472" colspan="6">
		<span class='topo'>Cedente</span><br>
				<span class='campoL' >
					<? echo $cedente_nome; ?> 
				</span>
	  </td>
	  <td width="168">
		<span class='topo'>Agência/Conta Corrente</span><br>
		<span class='campo'><?echo "$agencia - $conta";?></span>
	  </td>
	 </tr>
	 <tr>
	  <td width="95">
		<span class='topo'>Data Documento</span><br>
		<span class='campo'>
			<span id='data_digitacao'> <? echo $receber_emissao; ?></span>
	  </td>
	  <td width="134" colspan="2">
		<span class='topo'>Documento/Nota Fiscal</span><br>
		<span class='campo'>
					<? echo $receber_documento; ?>
		</span>
	  </td>
	  <td width="80">
		<span class='topo'>Boleto</span><br>
		<span class='campo'>
				<? echo $receber_documento; ?>	
		</span>
	  </td>
	  <td width="38">
		<span class='topo'>Aceite</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	  <td width="109">
		<span class='topo'>Data</span><br>
		<span class='campo'>
			<? echo $receber_emissao; ?>
		</span>
	  </td>
	  <td width="168">
		<span class='topo'>Nosso Número</span><br>
		<span class='campo'>
			<div id='doc_final' style='padding:4px; background-color:#ffffff; width:200px; height:20px;'></div>
		</span>
	  </td>
	 </tr>
	 
	 <tr>
	  <td width="95">
		<span class='topo'>Uso do Banco</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	  <td width="85">
		<span class='topo'>Carteira</span><BR>
		<span class='campo'></span>
	  </td>
	  <td width="29">
		<span class='topo'>Espécie</span><br>
		<span class='campo'>R$</span>
	  </td>
	  <td width="90" colspan="2">
		<span class='topo'>Quantidade</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	  <td width="115">
		<span class='topo'>(x) Valor</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	  <td width="168">
		<span class='topo'>(=) Valor do Documento</span><br>
		<span class='campo'>
					<? echo $receber_valor; ?>
		</span>
	  </td>
	 </tr>

	 <tr>
	  <td width="472" colspan="6" rowspan="6" valign="top">
		<span class='topo'>Instruções (texto de responsabilidade do cedente)</span><br>


	<table cellspacing="5" cellpadding='5' style='font-size:10px'>
	<tr>
	<td valign='top'>
	<!--
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Multa</div>
		<label name='tipo_multa'>
		<input type='radio' name='tipo_multa' onclick="$('multa_valor').disabled=true; $('multa_p').disabled=false" >  %  &nbsp;</label>
		<input type='text' name='multa_p' id='multa_p' value="" size='6' class="frm" maxlength='6' onkeyup="javascript: $('multa_valor').value=this.value.replace(',','.') * $('valor').value.replace(',','.') /100; "  disabled>
		<br>
		<label name='tipo_multa'>
		<input type='radio' name='tipo_multa' onclick="$('multa_valor').disabled=false; $('multa_p').disabled=true" checked> R$ </label><input type='text' name='multa_valor' id='multa_valor' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)"> 
	-->
		<!--  <br><i style='font-size:9px;color:gray'>Pagamento após o vencimento</i> -->
	</td>

	<td valign='top'><!--
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Juros Mora ao Dia</div>
		<label name='tipo_juros'>
		<input type='radio' name='tipo_juros' onclick="$('juros_mora_p').disabled=false; $('juros_mora_valor').disabled=true" > %  &nbsp;</label>
		<input type='text' id='juros_mora_p' name='juros_mora_p' value="" size='6' maxlength='6' class="frm" onkeyup="javascript: $('juros_mora_valor').value=this.value.replace(',','.') * $('valor').value.replace(',','.') /100; " disabled>
		<br>
		<label name='tipo_juros'>
		<input type='radio' name='tipo_juros' onclick="$('juros_mora_valor').disabled=false; $('juros_mora_p').disabled=true" checked> R$ </label><input type='text' id='juros_mora_valor' name='juros_mora_valor' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)"> -->
		<!--  <br><i style='font-size:9px;color:gray'>Pagamento após o vencimento</i> -->
	</td>

	<td valign='top'>
	<!--
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Desconto</div>
		<label name='tipo_desconto'>
		</label>
		<label name='tipo_desconto'>
		<input type='radio' name='tipo_desconto' onclick="$('desconto').disabled=true; $('desconto_p').disabled=false" > %  &nbsp;</label><input type='text' id='desconto_p' name='desconto_p' value="" size='6' maxlength='6' class="frm" onkeyup="javascript: $('desconto').value=this.value.replace(',','.') * $('valor').value.replace(',','.') /100; " disabled>

		<br>
		<label name='tipo_desconto'>
		<input type='radio' name='tipo_desconto' onclick="$('desconto_p').disabled=true; $('desconto').disabled=false" checked> R$ </label><input type='text' id='desconto' name='desconto' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)"><br>
		<input type='checkbox' name='desconto_pontualidade' value='t' id='desconto_pontualidade'><b style='font-size:10px;font-weight:normal' >Desconto Pontualidade</b>-->
		<!-- <br><i style='font-size:9px;color:gray'>Pgto antes do vencimento</i>  -->

	</td>

	</tr>

	<tr>
	<td><!--
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Protesto</div>
					<select name='protestar' id='protestar' onchange="javascript:if(this.value==0) { $('valor_custas_cartorio').value=''; $('valor_custas_cartorio').disabled=true;} else{$('valor_custas_cartorio').disabled=false;}">
						<option value='0' selected>-</option>
						<option value='1'>1 dias</option>
						<option value='2'>2 dias</option>
						<option value='3'>3 dias</option>
						<option value='4'>4 dias</option>
						<option value='5'>5 dias</option>
						<option value='6'>6 dias</option>
						<option value='7'>7 dias</option>
						<option value='8'>8 dias</option>
						<option value='9'>9 dias</option>
						<option value='10'>10 dias</option>
					</select>
					-->
	</td>
	<td>
	<!--
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Custos Cartório</div>
					R$  <input type='text' id='valor_custas_cartorio' name='valor_custas_cartorio' value="" size='10' maxlength='20' class="frm" onblur="javascript:checarNumero(this)">
		-->
	</td>
	<td>
	</td>
	</tr>
	<tr>
	<td colspan='3'>
					Observações<br>
					<TEXTAREA type='text' id='obs' COLS='60' ROWS='3' NAME="obs" value='<?echo $obs;?>'><?echo $obs;?></TEXTAREA>
	</td>
	</tr>
	</table>


	  </td>
	  <td width="168">
		<span class='topo'>(-) Descontos/Abatimentos</span><br>
		<span class='campo' id='descontos_abatimentos'></span>
	  </td>
	 </tr>
	 <tr>
	  <td width="168">
		<span class='topo'>(-) Outras Deduções</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	 </tr>
	 <tr>
	  <td width="168">
		<span class='topo'>(+) Mora/Multa</span><br>
		<span class='campo'><? echo $valor_multa; ?></span>
	  </td>
	 </tr>
	 <tr>
	  <td width="168">
		<span class='topo'>(+) Outros Acréscimos</span><br>
		<span class='campo'>&nbsp;</span>
	  </td>
	 </tr>
	 <tr>
	  <td width="168">
		<span class='topo'>(=) Valor Cobrado</span><br>
		<span class='campo' ><? echo $receber_valor; ?></span>
	  </td>
	 </tr>
	 <tr>
	  <td width="168">
		<span class='topo'>(=) Valor Pago</span><br>
		<span class='campo'><? echo $receber_valor_recebido; ?></span>
	  </td>
	 </tr>

	 <tr>
	  <td width="640" colspan="7">

	<input type='hidden' id='faturamentoID' value='' >

	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	   <tr>
		<td width="8%" valign='top'>
			<span class='topo'>Sacado</span>
			
		</td>
		<td width="28%" colspan="2">
			<span class='campoL' >
					<? echo $sacado_nome; ?> 
				</span>
			
			
		</td>
		<td width="34%" colspan="2"><span class='campoL'>-</span></td>
	   </tr>
	   <tr>
		<td width="3%"></td>
		<td width="28%" colspan="2"><span class='campoL'></span></td>
		<td width="22%"><span class='campoL'></span></td>
		<td width="32%"></td>
	   </tr>
	   <tr>
		<td width="2%"></td>
		<td width="10%"><span class='campoL'></span></td>
		<td width="38%"><span class='campoL'></span></td>
		<td width="22%"><span class='campoL'></span></td>
		<td width="30%" nowrap></td>
	   </tr>
	   <tr>
		<td width="1%" colspan="2"><span class='topo'></span></td>
		<td width="38%"></td>
		<td width="22%"></td>
		<td width="32%"><span class='topo'></span></td>
	   </tr>
	  </table>

	  </td>
	 </tr>
	</table>
	<script type="text/javascript">
		jQuery(function($){
			$("#data_baixa").maskedinput("99/99/9999");
		});
	</script>
<?		if(strlen($conta_receber)>0 AND strlen($receber_valor_recebido)==0){
					echo "<center><br>";
					echo "<table id='boleto' border='0' cellspacing='0' width='710' align='center' style='border-collapse: collapse; border: 1px solid #000000;' class='table_line'>";

					echo "<tr>";
					echo "<td align='left'><b style='font-size:14px'>Efetuar a baixa desse registro</b></td>";
					echo "<td align='center'>Data da Baixa</td>";
					echo "<td align='left'><input type='text' id='data_baixa' name='data_baixa' value='' size='11' maxlength='10' class='frm'></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td></td>";
					echo "<td align='center'>Valor Recebido</td>";
					echo "<td align='left'><input type='text' name='valor_recebido' id='valor_recebido' value='' size='8' maxlength='20' class='frm' onblur=\"javascript:checarNumero(this)\"></td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td></td>";
					echo "<td></td>";
					echo "<td align='left'>";
					echo "<INPUT TYPE='hidden' name='btn_acao' id='btn_acao' value=''>";
					echo "<INPUT TYPE='button' name='bt_baixar' id='bt_baixar' value='Efetuar Baixa' onClick=\"javascript:
					
					if (document.frm_receber.btn_acao.value==''){
						if (confirm('Deseja efetuar a baixa deste documento?')){
							document.frm_receber.btn_acao.value='baixar';
							document.frm_receber.submit();
						}
					}else{
						alert('Aguarde submissão.');
					}
					\">";
					echo "&nbsp;&nbsp;";
					#echo "<INPUT TYPE='button' name='bt_cancelar' id='bt_cancelar' onclick='window.location=\"$PHP_SELF\"' value='Cancelar operação' >";

					if(strlen($caixa_banco)>0){
						$sql = "SELECT banco from tbl_caixa_banco where caixa_banco= $caixa_banco";
						$res = pg_exec($con,$sql);
						$xcaixa_banco	= pg_result($res,0,0);
						//echo "imprime boleto $xcaixa_banco";
						if($xcaixa_banco=="001"){
							$boleto = "boleto/boleto_bb.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="237"){
							$boleto = "boleto/boleto_bradesco.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="275"){
							$boleto = "boleto/boleto_real.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="351"){
							$boleto = "boleto/boleto_santander_banespa.php?conta=$conta_receber";
						}
						//echo "<BR>$boleto";
						/*echo "<script language='javascript'>";
							echo "window.open('$boleto' ,'_blank', 'menubar=yes,scrollbars=yes');";
						echo "</script>";*/
					}else{
						$msg_erro.= "Não foi escolhido o banco para gerar o boleto";
					}

					echo "<a href='$boleto' target='blank'>Imprimir Boleto</a>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
				}
	?>
	</form>

	<?
		exit;
}


// AJAX PARA MONTAR O EMAIL DE COBRANÇA
#########################################################################################
if ($enviarEmail=='montar'){
	$cliente = trim($_GET["destinatario"]); // popup para enviar email de cobrança
	if (strlen($cliente)>0){

		$sql = "SELECT  tbl_pessoa.pessoa          ,
				tbl_pessoa.nome            ,
				tbl_pessoa.cnpj            ,
				tbl_pessoa.ie              ,
				tbl_pessoa.nome_fantasia   ,
				tbl_pessoa.endereco        ,
				tbl_pessoa.numero          ,
				tbl_pessoa.complemento     ,
				tbl_pessoa.bairro          ,
				tbl_pessoa.cidade          ,
				tbl_pessoa.estado          ,
				tbl_pessoa.pais            ,
				tbl_pessoa.cep             ,
				tbl_pessoa.fone_residencial,
				tbl_pessoa.fone_comercial  ,
				tbl_pessoa.cel             ,
				tbl_pessoa.fax             ,
				tbl_pessoa.email           ,
				tbl_pessoa.tipo
			FROM tbl_pessoa
			WHERE tbl_pessoa.empresa = $login_empresa
			AND pessoa = $cliente";
		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res)>0){
			$pessoa           = trim(pg_result($res,0,pessoa));
			$nome             = trim(pg_result($res,0,nome));
			$cnpj             = trim(pg_result($res,0,cnpj));
			$endereco         = trim(pg_result($res,0,endereco));
			$numero           = trim(pg_result($res,0,numero));
			$complemento      = trim(pg_result($res,0,complemento));
			$email            = trim(pg_result($res,0,email));
		}

		$sql	= "SELECT 
						tbl_contas_receber.documento,
						to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') AS emissao,
						to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento,
						tbl_contas_receber.valor,
						tbl_pessoa.nome
			FROM tbl_contas_receber 
			JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
			LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
			WHERE  (tbl_contas_receber.posto=$login_loja OR tbl_contas_receber.distribuidor=$login_loja) 
			AND tbl_contas_receber.cliente = $cliente
			AND tbl_contas_receber.recebimento IS NULL
			ORDER BY tbl_contas_receber.vencimento DESC";
		$res = pg_exec($con,$sql);
		$msg_carta = "";
		if(pg_numrows($res)>0){
			$qtde_vencidos = pg_numrows ($res);
			for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$receber_documento		= trim(pg_result($res, $i, documento));
				$receber_emissao		= trim(pg_result($res, $i, emissao));
				$receber_vencimento		= trim(pg_result($res, $i, vencimento));
				$receber_valor			= trim(pg_result($res, $i, valor));
				$receber_valor			= number_format($receber_valor,2,",","");
				$msg_carta .= "Documento: $receber_documento \nValor: $receber_valor \nVencido em: $receber_vencimento \n------------------------------------------------\n";
			}
		}

		$sql = "SELECT	carta_cobranca1,
						carta_cobranca2,
						email_cobranca
				FROM tbl_loja_dados 
				WHERE empresa = $login_empresa
				AND   loja    = $login_loja";
		$res = pg_exec($sql);
		if(@pg_numrows($res)>0){
			$carta_cobranca1 = trim(pg_result($res,0,carta_cobranca1));
			$carta_cobranca2 = trim(pg_result($res,0,carta_cobranca2));
			$email_cobranca  = trim(pg_result($res,0,email_cobranca));
		}
		if (strlen($carta_cobranca1)>0){
			$carta = $carta_cobranca1;
		}else{
			$carta = $carta_cobranca2;
		}
		
		if (strlen($carta)==0){
			$carta = "NENHUMA CARTA PRÉ-DEFINIDA.\n\nNome do cliente: [nome_cliente]";
			$carta .= "\n\nDocumentos vencidos:\n[msg_documentos]";
			$carta .= "\n\nEmail de cobrança: [email_cobranca]";
		}

		$carta = str_replace("[nome_cliente]",$nome,$carta);
		$carta = str_replace("[msg_documentos]",$msg_carta,$carta);
		$carta = str_replace("[email_cobranca]",$email_cobranca,$carta);
		

		$resposta = "<form name='frm_email' method='POST' action='$PHP_SELF?enviarEmail=enviar' onSubmit='javascript:if (confirm(\"Deseja continuar?\")) return true; else return false;'>";
		$resposta .="<table border='0' cellpadding='2' cellspacing='2'  bordercolor='#d2e4fc'  align='center' width='100%' class='table_line'>";
		$resposta .="<input type='hidden' name='cliente' value='$cliente'>";
		$resposta .="<thead>";
		$resposta .="<tr >";
		$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>Envio de Email de Cobrança</b></font></td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td><b>Cliente</b></td>";
		$resposta .="<td>$nome ($cliente)</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td><b>Endereço</b></td>";
		$resposta .="<td>$endereco, $numero</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td><b>Telefone</b></td>";
		$resposta .="<td>Res.: $fone_residencial - Com.: $fone_comercial</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td><b>DE</b></td>";
		$resposta .="<td><input type='hidden' name='de' value='cobranca@telecontrol.com.br'>cobranca@telecontrol.com.br</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td><b>PARA</b></td>";
		$resposta .="<td><input type='hidden' name='para' value='$email'>$email</td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td></td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td>Assunto</td>";
		$resposta .="<td><input type='text' size='50' name='assunto' value='Carta de Cobrança'></td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td>Conteúdo</td>";
		$resposta .="<td><textarea name='mensagem' style='width:95%'rows='15' class='frm'>$carta</textarea></td>";
		$resposta .="</tr>";
		$resposta .="<tr>";
		$resposta .="<td>Enviar Email</td>";
		$resposta .="<td><input type='button' onclick='this.form.submit()' value='Enviar Email'></td>";
		$resposta .="</tr>";
		$resposta .="</table>";
		$resposta .="</form>";
	}
	echo $resposta;
	exit;
}


// AJAX PARA ENVIAR EMAIL
#########################################################################################
if ($enviarEmail=='enviar'){
	$cliente    = trim($_POST["cliente"]);
	$email_de   = trim($_POST["de"]);
	$email_para = trim($_POST["para"]);
	$assunto    = trim($_POST["assunto"]);
	$mensagem   = trim($_POST["mensagem"]);

	#$email_de = str_replace("@","\@",$email_de);
	#$email_para = str_replace("@","\@",$email_para);

	if (strlen($cliente)==0){
		$msg_erro .= "Cliente não encotrado!";
	}
	if (strlen($email_de)==0){
		$msg_erro .= "Email de origem inválido!";
	}
	if (strlen($email_para)==0){
		$msg_erro .= "Email destino inválido";
	}
	if (strlen($assunto)==0){
		$msg_erro .= "Assunto vazio!";
	}
	if (strlen($mensagem)==0){
		$msg_erro .= "Mesagem vazia!";
	}

	if (strlen($msg_erro)==0){
		$to      =  "$email_para";
		$subject = "$assunto";
		$message = "$mensagem";
		$headers = "From: $email_de" . "\r\n" .
			"Reply-To: $email_de" . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

		mail($to, $subject, $message, $headers);
		echo "Email enviado com sucesso!";
		echo "<script languague='javascript'>setTimeout('window.close()',1000);</script>";
	}else{
		echo "Não foi possível enviar o email: <br> Erros: $msg_erro";
		echo "<script languague='javascript'>setTimeout('window.close()',1000);</script>";
	}
	
	exit;
}

// AJAX PARA MOSTRAR OS DETALHES DOS CLIENTES
#########################################################################################
if (strlen($cliente)>0){
	$sql = "SELECT  tbl_pessoa.pessoa          ,
			tbl_pessoa.nome            ,
			tbl_pessoa.cnpj            ,
			tbl_pessoa.ie              ,
			tbl_pessoa.nome_fantasia   ,
			tbl_pessoa.endereco        ,
			tbl_pessoa.numero          ,
			tbl_pessoa.complemento     ,
			tbl_pessoa.bairro          ,
			tbl_pessoa.cidade          ,
			tbl_pessoa.estado          ,
			tbl_pessoa.pais            ,
			tbl_pessoa.cep             ,
			tbl_pessoa.fone_residencial,
			tbl_pessoa.fone_comercial  ,
			tbl_pessoa.cel             ,
			tbl_pessoa.fax             ,
			tbl_pessoa.email           ,
			tbl_pessoa.tipo
		FROM tbl_pessoa
		WHERE tbl_pessoa.empresa = $login_empresa
		AND pessoa = $cliente";
	$res = pg_exec ($con,$sql) ;
	if (pg_numrows($res)>0){
		$pessoa           = trim(pg_result($res,0,pessoa));
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$endereco         = trim(pg_result($res,0,endereco));
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$bairro           = trim(pg_result($res,0,bairro));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$pais             = trim(pg_result($res,0,pais));
		$cep              = trim(pg_result($res,0,cep));
		$fone_residencial = trim(pg_result($res,0,fone_residencial));
		$fone_comercial   = trim(pg_result($res,0,fone_comercial));
		$cel              = trim(pg_result($res,0,cel));
		$fax              = trim(pg_result($res,0,fax));
		$email            = trim(pg_result($res,0,email));
		$ie               = trim(pg_result($res,0,ie));
		$nome_fantasia    = trim(pg_result($res,0,nome_fantasia));
		$tipo_pessoa      = trim(pg_result($res,0,tipo));
	}

	$resposta .="<table border='0' cellpadding='2' cellspacing='2'  bordercolor='#d2e4fc'  align='center' width='100%' class='table_line'>";
	$resposta .="<thead>";
	$resposta .="<tr >";
	$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>CLIENTE</b></font></td>";
	$resposta .="</tr>";
	$resposta .="<tr>";
	$resposta .="<td><b>Nome</b></td>";
	$resposta .="<td>$nome</td>";
	$resposta .="</tr>";
	$resposta .="<tr>";
	$resposta .="<td><b>Endereço</b></td>";
	$resposta .="<td>$endereco, $numero</td>";
	$resposta .="</tr>";
	$resposta .="<tr>";
	$resposta .="<td><b>Telefone</b></td>";
	$resposta .="<td>Res.: $fone_residencial - Com.: $fone_comercial</td>";
	$resposta .="</tr>";
	$resposta .="<tr>";
	$resposta .="<td><b>E-Mail</b></td>";
	$resposta .="<td>$email (<a href=\"javascript:enviarEmail('1','$cliente')\">enviar email de cobrança</a>)</td>";
	$resposta .="</tr>";
	$resposta .="</table>";
	$resposta .="<br>";


	$sql	= "SELECT 
					tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') AS emissao,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento,
					tbl_contas_receber.vencimento as vencimento_bd,
					tbl_contas_receber.valor,
					tbl_contas_receber.valor_dias_atraso,
					TO_CHAR(tbl_contas_receber.recebimento,'DD/MM/YYYY') AS recebimento,
					tbl_contas_receber.valor_recebido,
					tbl_contas_receber.status,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.obs,
					tbl_contas_receber.posto,
					tbl_contas_receber.distribuidor,
					tbl_contas_receber.fabrica,
					tbl_contas_receber.cliente,
					tbl_pessoa.nome
		FROM tbl_contas_receber 
		JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE  (tbl_contas_receber.posto=$login_loja OR tbl_contas_receber.distribuidor=$login_loja) 
		AND tbl_contas_receber.cliente = $cliente";
	$sql .= " ORDER BY tbl_contas_receber.vencimento ASC";

	$resposta .="<table border='1' cellpadding='2' cellspacing='0'  bordercolor='#d2e4fc'  align='center' width='100%' class='table_line'>";
	$resposta .="<thead>";
	$resposta .="<tr >";
	$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>HISTÓRICO</b></font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td><b>Faturamento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Ações</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	$resposta .="</head>";
	$resposta .="<tbody>";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$cont_itens= pg_numrows($res);
		$receber_valo_tota = 0 ;
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			
			$receber_receber		= trim(pg_result($res, $i, contas_receber));
			$receber_documento		= trim(pg_result($res, $i, documento));
			$receber_emissao		= trim(pg_result($res, $i, emissao));
			$receber_vencimento		= trim(pg_result($res, $i, vencimento));
			$receber_vencimento_bd	= trim(pg_result($res, $i, vencimento_bd));
			$receber_valor			= trim(pg_result($res, $i, valor));
			$receber_recebimento	= trim(pg_result($res, $i, recebimento));
			$receber_valor_recebido	= trim(pg_result($res, $i, valor_recebido));
			$receber_status			= trim(pg_result($res, $i, status));
			$receber_faturamento	= trim(pg_result($res, $i, faturamento_fatura));
			$receber_obs			= trim(pg_result($res, $i, obs));
			$receber_posto			= trim(pg_result($res, $i, posto));
			$receber_fabrica		= trim(pg_result($res, $i, fabrica));
			$receber_cliente		= trim(pg_result($res, $i, cliente));
			$receber_cliente_nome	= trim(pg_result($res, $i, nome));
			$rebecer_distribuidor	= trim(pg_result($res, $i, distribuidor));

		

			if($cor=="#F1F4FA") $cor = '#F7F5F0';
			else                $cor = '#F1F4FA';

			if (strlen($receber_recebimento)>0){
				$st="<font color='blue'>Pago</font>";
			}else{
				if($receber_vencimento_bd < date('Y-m-d')){
					$st="<font color='red'>Vencido</font>";
					$receber_valo_tota += $receber_valor;
					$cor = "#FFC0B9";
				}else {
					$st="A vencer";
				}
			}

			$receber_valor = str_replace(",",".",$receber_valor);
	
			$resposta .="<tr bgcolor='$cor' class='linha'  id='linha_$i'>";

			//nome
			if (strlen($receber_cliente_nome)>29) {
				$receber_cliente_nome = substr($receber_cliente_nome,0,29)."...";
			}

			//documento
			$resposta .= "<td nowrap align='center'>";
			$resposta .= "<a href=\"javascript:abrirConta('$receber_receber','$receber_cliente_nome')\">$receber_documento</a>";
			$resposta .= "</td>";

			$resposta .="<td nowrap>&nbsp;";
			$resposta .="</td>";
			
			//vencimento
			$resposta .="<td nowrap align='center'>$receber_vencimento</td>";

			// valor
			$resposta .="<td nowrap align='right'>R$ $receber_valor";
			$resposta .="</td>";

			//acoes
			$resposta .= "<td nowrap align='center'>";
			$resposta .= "<a href=\"javascript:abrirConta('$receber_receber','$receber_cliente_nome')\">Abrir</a>";
			$resposta .= "</td>";


			$resposta .="<td nowrap align='center'>$st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";

		$resposta .="<foot>";
		$resposta .="<tr>";
		$resposta .="<td colspan='4' align='left'><b>Total Vencidos:</b></td>";
		$resposta .="<td colspan='8' align='right'>R$ $receber_valo_tota</td>";
		$resposta .="</tr>";

		$resposta .="</tfoot>";
	}else {
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='8' align='center'><b>Nenhum resultado encontrado</b></td></tr>";
	}
	echo $resposta;
	exit;
}

// BAIXAR LOTE COMPLETO // DESABILITADO //
#########################################################################################
if(strlen($_POST["btn_acao"]) > 0 AND $_POST["btn_acao"]=='BAIXAR_LOTE') {
	$cont_itens= count($_POST["receber"]);
	$data_baixa= $_POST["data_baixa"];
	if(($cont_itens>0) and (strlen($data_baixa)>0)){
		$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;
		for($i=0 ; $i< $cont_itens; $i++){
			$ct_receber= "";
			$ct_receber= $_POST["receber"][$i];
			//echo "receber: $ct_receber";

			if(strlen($ct_receber)>0){
				$sql = "UPDATE tbl_contas_receber SET 
							recebimento	= current_date,
							valor_recebido= valor
						WHERE contas_receber = $ct_receber;";
				$res = pg_exec($con,$sql);
				//echo "<BR>SQL: $sql";
			}			
		}
	}
}

// ACAO PARA BAIXAR O BOLETO MANUALMENTE
#########################################################################################
if( $_POST["btn_acao"]=='baixar') {
	$conta_receber_aux = trim($_POST["baixar_conta"]);
	$data_baixa        = trim($_POST["data_baixa"]);
	$valor_recebido    = trim($_POST["valor_recebido"]);
	$obs               = trim($_POST["obs"]);

	if((strlen($conta_receber_aux)>0) and (strlen($data_baixa)==10)){
		$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;

		if (strlen($valor_recebido)>0){
			$valor_recebido = str_replace(",","",$valor_recebido);
		}else{
			$valor_recebido = " valor "; //valor = valor do banco!!!
		}

		$sql = "UPDATE tbl_contas_receber SET 
						recebimento	= $data_baixa,
						valor_recebido= $valor_recebido,
						obs = '$obs'
				WHERE contas_receber = $conta_receber_aux
					AND (tbl_contas_receber.posto=$login_loja OR tbl_contas_receber.distribuidor=$login_loja) ";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro)==0){
			$msg = "Baixa de documento efetuado com sucesso!";
		}
	}
}

// PESQUISA
#########################################################################################
if($_POST['btn_acao']=='pesquisar' AND $_GET['pesquisar']=='sim') {

	$cliente		= trim($_POST["cliente"]);
	$documento		= trim($_POST["documento"]);
	$vencimento		= trim($_POST["vencimento"]);
	$valor			= trim($_POST["valor"]);
	$filtro			= trim($_POST["filtro"]);
	$dias			= trim($_POST["dias"]);

	$sql	= "SELECT 
					tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') AS emissao,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento,
					tbl_contas_receber.vencimento as vencimento_bd,
					tbl_contas_receber.valor,
					tbl_contas_receber.valor_dias_atraso,
					TO_CHAR(tbl_contas_receber.recebimento,'DD/MM/YYYY') AS recebimento,
					tbl_contas_receber.valor_recebido,
					tbl_contas_receber.status,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.obs,
					tbl_contas_receber.posto,
					tbl_contas_receber.distribuidor,
					tbl_contas_receber.fabrica,
					tbl_contas_receber.cliente,
					tbl_pessoa.nome
		FROM tbl_contas_receber 
		JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE  (tbl_contas_receber.posto=$login_loja OR tbl_contas_receber.distribuidor=$login_loja) ";

	if (strlen($cliente)>0){
		$sql .= " AND tbl_contas_receber.cliente IN (
						SELECT pessoa
						FROM tbl_pessoa
						WHERE empresa = $login_empresa
						AND upper(nome) like upper('%$cliente%')
					)";
	}

	if (strlen($documento)>0){
		$sql .= " AND tbl_contas_receber.documento like '%$documento%'";
	}

	if (strlen($vencimento)>0){
		$sql .= " AND to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') = '$vencimento'";
	}

	if (strlen ($valor) > 0) {
		$valor_busca = str_replace(",",".",$valor);
	}

	if (strlen($valor)>0){
		$valor = str_replace(",",".",$valor);
		$sql .= " AND tbl_contas_receber.valor = $valor";
	}

	if (strlen($filtro)>0){
		if ($filtro=="vencidos"){
			if ($dias>0){
				$sql .= " 
						AND tbl_contas_receber.recebimento IS NULL
						AND tbl_contas_receber.valor_recebido IS NULL
						AND tbl_contas_receber.vencimento::date + INTERVAL '$dias day' < CURRENT_DATE ";
			}else{
				$sql .= "
						AND tbl_contas_receber.recebimento IS NULL
						AND tbl_contas_receber.valor_recebido IS NULL
						AND tbl_contas_receber.vencimento::date + INTERVAL '3 day' < CURRENT_DATE ";
			}
		}
		if ($filtro=="vencer"){
			$sql .= " 
						AND tbl_contas_receber.recebimento IS NULL
						AND tbl_contas_receber.valor_recebido IS NULL
						AND tbl_contas_receber.vencimento::date > CURRENT_DATE ";
		}
		if ($filtro=="recebido"){
			$sql .= " 
						AND tbl_contas_receber.recebimento IS NOT NULL
						AND tbl_contas_receber.valor_recebido IS NOT NULL";
		}
	}

	$sql .= " ORDER BY tbl_contas_receber.vencimento ASC
			LIMIT 400";
//echo "$sql";
	$resposta .="<table border='1' cellpadding='2' cellspacing='0'  bordercolor='#d2e4fc'  align='center' width='700px' class='table_line'>";
	#$resposta .= "<form metohd='post' name='baixar_lote' action='$PHP_SELF' onsubmit=\"this.form.baixar_em_lote.value='sim'\">";
	#$resposta .="<input type='hidden' name='baixar_em_lote'>";
	$resposta .="<thead>";
	$resposta .="<tr >";
	$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>CONTAS A RECEBER</b></font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Baixa</b></td>";
	$resposta .="<td><b>Cliente</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Ações</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	$resposta .="</head>";
	$resposta .="<tbody>";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$cont_itens= pg_numrows($res);
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			
			$receber_receber		= trim(pg_result($res, $i, contas_receber));
			$receber_documento		= trim(pg_result($res, $i, documento));
			$receber_emissao		= trim(pg_result($res, $i, emissao));
			$receber_vencimento		= trim(pg_result($res, $i, vencimento));
			$receber_vencimento_bd	= trim(pg_result($res, $i, vencimento_bd));
			$receber_valor			= trim(pg_result($res, $i, valor));
			$receber_recebimento	= trim(pg_result($res, $i, recebimento));
			$receber_valor_recebido	= trim(pg_result($res, $i, valor_recebido));
			$receber_status			= trim(pg_result($res, $i, status));
			$receber_faturamento	= trim(pg_result($res, $i, faturamento_fatura));
			$receber_obs			= trim(pg_result($res, $i, obs));
			$receber_posto			= trim(pg_result($res, $i, posto));
			$receber_fabrica		= trim(pg_result($res, $i, fabrica));
			$receber_cliente		= trim(pg_result($res, $i, cliente));
			$receber_cliente_nome	= trim(pg_result($res, $i, nome));
			$rebecer_distribuidor	= trim(pg_result($res, $i, distribuidor));

			if(strlen($receber_distribuidor)>0){
				$sql2="SELECT tbl_posto.nome, tbl_posto.fone, tbl_posto.email, rec.qtde, rec.valor	
						FROM   tbl_posto
						LEFT JOIN  (SELECT posto, COUNT(*) AS qtde, SUM (valor) AS valor
							FROM  tbl_contas_receber
							WHERE posto = $receber_posto
							AND   distribuidor = $rebecer_distribuidor
							AND   recebimento IS NULL
							AND   vencimento < CURRENT_DATE
							GROUP BY posto) rec ON tbl_posto.posto = rec.posto
						WHERE tbl_posto.posto = $receber_posto ";
				$res2 = pg_exec ($con,$sql2);
				$receber_cliente_nome = trim(pg_result($res2, nome));
				if(strlen($receber_cliente_nome) == 0){
					$receber_cliente_nome = "Não achou o cliente!";
				}
				$hint = $receber_posto.trim(pg_result($res2, fone))."-".trim(pg_result($res2, email))."- Qtd=".trim(pg_result($res2, qtde))." Valor=".trim(pg_result($res2, valor));
			}

			if($cor=="#F1F4FA") $cor = '#F7F5F0';
			else                $cor = '#F1F4FA';

			$receber_valor = str_replace(",",".",$receber_valor);
	
			$resposta .="<tr bgcolor='$cor' class='linha'  id='linha_$i'>";

			//checkbox
			$resposta .="<td nowrap align='center'><input type='checkbox' name='receber[]' id='receber_receber$i' value='$receber_receber' class='check_normal'></td>";

			//nome
			if (strlen($receber_cliente_nome)>29) {
				$receber_cliente_nome = substr($receber_cliente_nome,0,29)."...";
			}

			$resposta .="<td nowrap title='$hint' align='left'>";

			$resposta .= "<a href=\"javascript:abrirDetalhes('$receber_cliente','$receber_cliente_nome')\">$receber_cliente_nome</a>";
			$resposta .= "</td>";

			//documento
			$resposta .="<td nowrap align='center'>";
			$resposta .= "<a href=\"javascript:abrirConta('$receber_receber','$receber_cliente_nome')\">$receber_documento </a>";
			$resposta .= "</td>";

	/*		$resposta .="<td nowrap>&nbsp;";
			$resposta .="</td>";
			*/
			//vencimento
			$resposta .="<td nowrap align='center'>$receber_vencimento</td>";

			// valor
			$resposta .="<td nowrap align='right' onclick=\"javascript:selecionarLinha($i,'$cor')\" style='cursor:pointer' align='right'><input type='hidden' name='receber_$i' id='receber_$i' value='$valor_aux'>R$ $receber_valor";
			$resposta .="</td>";

			//acoes
			$resposta .= "<td nowrap align='center'>";
			$resposta .= "<a href=\"javascript:abrirConta('$receber_receber','$receber_cliente_nome')\">Abrir</a>";
			$resposta .= "</td>";

			if (strlen($receber_recebimento)>0){
				$st = "<font color='blue'>Pago</font>";
			}else{
				if($receber_vencimento_bd < date('Y-m-d')){
					$st = "<font color='red'>Vencido</font>";
					$receber_valo_tota += $receber_valor;
				}else {
					$st = "A vencer";
					$receber_valo_tota += $receber_valor;
				}
			}
			$resposta .="<td nowrap align='center'>$st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";

		$resposta .="<foot>";
		$resposta .="<tr>";
		$resposta .="<td colspan='8' align='right'>";
		$resposta .="<b>Total:</b> $receber_valo_tota";
		$resposta .="</td>";
		$resposta .="</tr>";

		if ($i >399){
			$resposta .="<tr>";
			$resposta .="<td colspan='8' align='center'>";
			$resposta .="<b style='font-size:14px'>LIMITE de 400 linhas atingido. Faça uma busca mais detalhada.</b>";
			$resposta .="</td>";
			$resposta .="</tr>";
		}
		#$resposta .="<input type='hidden' id='cont_itens' name='cont_itens' value='$cont_itens' size='4'> ";
		#$resposta .="<input type='text' id='resultado' name='resultado' size='10' value='0' class='frm' read-only> ";

		#$resposta .="<tr class='Titulo3'>";
		#$resposta .="<td colspan='8' align='left'>";
		#$resposta .="Data da Baixa:<input type='text' name='data_baixa' value='".date('d/m/Y')."' size='12' class='frm'> ";
		#$resposta .="<input type='hidden' name='btn_acao' value=''> ";
		#$resposta .="<input type='button' name='baixar_sel' value='Baixar Selecionados' class='frm' #onclick=\"document.baixar_selecao.btn_acao.value='BAIXAR_LOTE';document.baixar_selecao.submit(); \"> ";
		#$resposta .="</td>";
		#$resposta .="</tr>";

		$resposta .="</tfoot>";
	}else {
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='8' align='center'><b>Nenhum resultado encontrado</b></td></tr>";
	}
	echo $resposta;
	exit;
}
#########################################################################################

$title = "Contas a receber";

include 'menu.php';
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}


?>

<script type="text/javascript">
	$(function(){
		$('div.demo').each(function() {
			 eval($('p', this).text());
		});
		$('#main p').wrap("<code></code>");
	});
</script>

<style type="text/css">
.Conteudo2,.Titulo2 {
		font:12px "Segoe UI", Tahoma;	
}
	h3 {
		font-size:16px;
		font-weight:bold;
	}

input.botao {
	background:#ced7e7;
	color:#000000;
	border:2px solid #ffffff;
}
.borda {
	border-width: 2px;
	border-style: dotted;
	border-color: #000000;
}
.Titulo2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color:#6C87B7;
	border: 0px;
}
.Titulo3{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
	background-color:#ABBAD6;
}

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.border {
	border: 1px solid #ced7e7;
}

#boleto .topo{
	font-size:10px;
/*	float:left;
	position:relative;
	font-size:10px; */
}
#boleto .campo{
	font-size:14px;
	font-weight:bold;
	text-align:right;
	float:right;
}
#boleto .campoL{
	font-size:14px;
	font-weight:bold;
}

.bloqueiado {
	border-color:#FFFFFF;
	background-color:#FFFFFF;
	color:#000000;
	font-size:12px;
	font-weight:bold;
}

input {
	BORDER-RIGHT: #888888 1px solid; 
	BORDER-TOP: #888888 1px solid; 
	FONT-WEIGHT: bold; 
	FONT-SIZE: 8pt; 
	BORDER-LEFT: #888888 1px solid; 
	BORDER-BOTTOM: #888888 1px solid; 
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; 
	BACKGROUND-COLOR: #f0f0f0

}
.check_normal{
	border:none;
}
tr.linha td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}

.demo{
	width:700px;
	background-color:#E2ECFE;
}

</style>


<script type="text/javascript">
	jQuery(function($){
		$("#vencimento").maskedinput("99/99/9999");
		$("#data_baixa").maskedinput("99/99/9999");
	});
</script>

<script language='javascript'>

//SELECIONA O FATURAMENTO RELACIONADO COM O Cliente
function selecionar(a){
	var nf =document.getElementById('nf').value;
	var doc=document.getElementById('documento').value;
	document.getElementById('doc_final').innerHTML= "<b>"+nf+"-"+doc+"</b>";
}


function duplo(d){
	//alert();
	document.getElementById('fatDes').fucus;
	document.getElementById('fatDes').dblclick;
}


function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function teste_baixar(botao){
	if (botao.value=='Baixar'){
		botao.value='Confimar Baixa';
//		desbloqueia_campos('frm_receber');
//		desmostra('bt_baixar');
//		desmostra('bt_limpar');
//		mostra('bt_cancelar');
		botoes_original('baixar');
		return false;
	}
	else{
		return true;
	}
}


function set_focus(id, x){
	if(se_existe(id)){
		document.getElementById(id).focus();
	}else{
		if(x > 10){
			return false;
		}else{
			x++;
			setTimeout("set_focus('"+id+"',"+ x +");",1000);
		}
	}
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO 
function calcula_total_selecionado(tot){
	//alert('passou aqui');
	var forn=0, lenPr = 0, len=0, soma = 0.0, somap = 0,testav=0, testap=0, conti=0;
	var cont_itens= document.getElementById('cont_itens').value;
	//alert(cont_itens);
	for (f=0; f<cont_itens;f++) { 
		if(document.getElementById('receber_'+f).value==''){
			
		}else{
			if(document.getElementById('receber'+f).checked == true){
				valor= parseFloat(document.getElementById('receber_'+f).value);
				//SOMA VALOR 
				soma += valor; //format_number(valor,2);
			}
		}
	}
	soma = format_number(soma,2);
	soma = soma.toString().replace( ".", "," );
	document.getElementById('resultado').value= soma;
}


function abrirDetalhes(cliente,nome){
	$('#linkDetalhes').attr("href","<? echo  $PHP_SELF; ?>?cliente="+cliente);
	$('#linkDetalhes').attr("title","Detalhes do Cliente "+nome);
	$('#linkDetalhes').click();
}
function enviarEmail(enviar,cliente){
	var url = '<? echo $PHP_SELF ?>?enviarEmail=montar&destinatario='+cliente;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=auto, directories=no, width=550, height=500, top=18, left=0");
	janela_aut.focus();
}
function abrirConta(receber,nome){
	$('#linkContaReceber').attr("href","<? echo  $PHP_SELF; ?>?receber="+receber+"&height=450&width=750");
	$('#linkContaReceber').attr("title","Contas a Receber do Cliente "+nome);
	$('#linkContaReceber').click();
}

</script>

<script language='javascript'>
	$(document).ready(function() { 
		$('#frm_pesquisa').ajaxForm({ 
			target: '#dados',
			beforeSubmit: function(){
				document.getElementById('dados').innerHTML = "Carregando...<br><img src='imagens/carregar_os.gif' >";
					},
			success: function() { 
				$('#dados').fadeIn('slow'); 
				//alert('Pesquisa realizada!');
			}
		});
	});
</script>

<!--  LINKS - para abrir as poopur. É um HACK pois não funciona com o ajax -->
<a href='' id='linkDetalhes' title='Detalhes' class='thickbox'></a>
<a href='' id='linkContaReceber' title='Contas a Receber' class='thickbox'></a>
<!--  LINKS - para abrir as poopur. É um HACK pois não funciona com o ajax -->


<?
	if(strlen($msg)>0){
		echo "<h4 aling='center' style=\"align:center'\">$msg</h4><br>";
	}
?>

<br>

<div class="demo" id='pesquisa'>
	<h1><img src='imagens/moedas.gif' alt='Adicionar Contas a Pagar' border='0' align='absmiddle'> Contas a Receber</h1>
		<form method='POST' name='frm_pesquisa' id='frm_pesquisa' action='<? echo $PHP_SELF ?>?pesquisar=sim'>

		<table border='0' cellpadding='0' cellspacing='2'  bordercolor='##555555'  align='center' width='600px' class='table_line'>

				<tr>
				<td><b>Cliente</b></td>
				<td colspan='4'><input type='text' name='cliente' size='40' value=''></td>
				</tr>

				<tr>
				<td><b>Documento</b></td>
				<td colspan='4'><input type='text' name='documento' size='20' value=''></td>
				</tr>

				<tr>
				<td><b>Data Vencimento</b></td>
				<td colspan='4'><input type='text' name='vencimento' id='vencimento' size='11' maxlength='10' value=''></td>
				</tr>

				<tr>
				<td><b>Valor</b></td>
				<td colspan='4'><input type='text' name='valor' size='20' value=''></td>
				</tr>

				<tr>
				<td><input type='radio' name='filtro' value='recebido'> Recebido</td>
				<td><input type='radio' name='filtro' value='vencidos' selected> Vencido</td>
				<td><input type='radio' name='filtro' value='vencer'> A Vencer</td>
				<td><input type='text' name='dias' value='' size='5'> dias</td>
				</tr>

				<tr>
				<td>&nbsp;</td>
				</tr>

				<tr>
					<td colspan='5' align='center'>
						<input type='hidden' name='btn_acao' value='pesquisar'>
						<input type='submit' name='btn_filtrar' value='Pesquisar'>
					</td>
				</tr>

		</table>
		</form>
	<p style='display:none'>$(this).corner("15px");</p>
</div>
<BR>
<a href='contas_receber_new_documento?btn_acao=abrirDocumento&keepThis=true&TB_iframe=true&height=540&width=750' title='Contas a Receber' class='thickbox' style='font-size:14px'><img src='imagens/add.png' alt='Adicionar Contas a Pagar' border='0' align='absmiddle'> Novo Recebimento </a>

<br>
<br>

<DIV class='exibe' id='dados' align='center'></DIV>

</BODY>
</HTML>