<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

//APROVAR e RECUSAR
$btn_acao = trim($_POST["btn_aprovar"]);
if(strlen($btn_acao) > 0){

	$qtde_pedido = trim($_POST["qtde_pedido"]);
	$observacao  = trim($_POST["observacao"]);
	$select_acao = trim($_POST["select_acao"]);

	if (strlen($qtde_pedido)==0){
		$qtde_pedido = 0;
	}

	if($select_acao == 'recusar' and strlen($observacao) == 0){
		$msg_erro = "Para reprovar pedido, precisa inserir o motivo da recusa.";
	}
	if($qtde_pedido==0) {
		$msg_erro="Por favor, selecione o pedido para ser aprovado ou reprovado.";
	}

	if(strlen($msg_erro) == 0) {
		for ($x=0;$x<$qtde_pedido;$x++){
			$Xpedido = trim($_POST["check_".$x]);
			if(strlen($Xpedido) > 0) {
				if($select_acao =='aprovar') {
					$sql = "UPDATE tbl_pedido SET 
								data_aprovacao = CURRENT_TIMESTAMP ,
								tabela = 30 ,
								pedido_via_distribuidor = true
							WHERE tbl_pedido.fabrica = 10
							AND   tbl_pedido.pedido  = $Xpedido ";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(strlen($msg_erro) == 0){
						$msg_erro="Pedido aprovado!";
					}
				}elseif($select_acao =='recusar'){

					$sql = "UPDATE tbl_pedido SET status_pedido = 17,
									obs='$observacao'
									WHERE tbl_pedido.fabrica = 10
									AND   tbl_pedido.pedido  = $Xpedido ";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(strlen($msg_erro) == 0){
						$msg_erro = "Pedido Reprovado!";
					}
				}
			}
		}
	}
}

//GRAVAR
$btn_gravar = trim($_POST["btn_gravar"]);
if(strlen($btn_gravar)>0 ){

	$qtde_pedido = trim($_POST["qtde_pedido"]);
	if (strlen($qtde_pedido)==0){
		$qtde_pedido = 0;
	}

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	for ($x=0;$x<$qtde_pedido;$x++){
		$conhecimento = trim($_POST["conhecimento_".$x]);
		$pedido       = trim($_POST["pedido_".$x]);
		$faturamento  = trim($_POST["faturamento_".$x]);
		if(strlen($conhecimento) > 0){
			$sql = " UPDATE tbl_faturamento set conhecimento = '$conhecimento' WHERE faturamento= $faturamento";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) == 0){
				$sql="SELECT tbl_posto.email
						FROM  tbl_pedido
						JOIN  tbl_posto ON tbl_posto.posto = tbl_pedido.posto
						WHERE	tbl_pedido.pedido  = $pedido ";

				$res=pg_exec($con,$sql);

				$email = pg_result($res,0,email);
				$nome       = "Telecontrol";
				$mensagem  .= "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br>Prezado,<br>seu pedido $pedido foi embarcado, para consultar a situação clique o link abaixo:<br><br<A HREF='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$conhecimento>Clique aqui para ver a situação do seu pedido</a><br>\n";
				$mensagem  .= "Telecontrol Networking<br>\n";
				$mensagem  .= "www.telecontrol.com.br";
				$assunto   = "Loja Virtual - situação do seu pedido no correio";
				$boundary = "XYZ-" . date("dmYis") . "-ZYX";
				$mens  = "--$boundary\n";
				$mens .= "Content-Transfer-Encoding: 8bits\n";
				$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
				$mens .= "$mensagem\n";
				$mens .= "--$boundary\n";
				$headers  = "MIME-Version: 1.0\n";
				$headers .= "Date: ".date("D, d M Y H:i:s O")."\n";
				$headers .= "From: \"Telecontrol\" <suporte@telecontrol.com.br>\r\n";
				$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
				@mail($email, $assunto,$mens, $headers);
			}
		}
	}
	if(strlen($msg_erro) == 0){
			$res = @pg_exec($con,"COMMIT TRANSACTION");
			$msg="Gravado com sucesso!";


	}else{
			$res = @pg_exec($con,"ROLLBACK TRANSACTION");
			$msg="Aconteceu um erro, tente novamente";
	}
}

//REPROVAR PEDIDO APROVADO, NÃO FATURADO
$btn_reprovar = trim($_POST["btn_reprovar"]);
if (strlen($btn_reprovar) > 0) {
	
	$observacao  = trim($_POST["observacao"]);
	$qtde_pedido = trim($_POST["qtde_pedido"]);

	if (strlen($qtde_pedido)==0) {
		$qtde_pedido = 0;
	}

	$res = @pg_exec($con, "BEGIN TRANSACTION");
	
	for ($x = 0; $x < $qtde_pedido; $x++) {
		
		$Xpedido = trim($_POST["check_".$x]);//PEDIDO
		
		if (strlen($Xpedido) > 0) {
			
			$sql = "UPDATE tbl_pedido
					   SET status_pedido  = 17,
						   obs            = '$observacao',
						   data_aprovacao = NULL
					 WHERE tbl_pedido.fabrica = 10
					   AND tbl_pedido.pedido = $Xpedido";
			
			//echo nl2br($sql) . '<br />';
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT  PE.pedido      ,
							PE.distribuidor,
							PI.pedido_item ,
							PI.peca        ,
							PI.qtde        ,
							OP.os
						FROM   tbl_pedido        PE
						JOIN   tbl_pedido_item   PI ON PI.pedido     = PE.pedido
						LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
						LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
						WHERE PE.pedido  = $Xpedido
						AND   PE.fabrica = 10
						AND   distribuidor = 4311
						AND   PI.qtde > PI.qtde_cancelada";
			
			//echo nl2br($sql) . '<br /><br />';
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					
					$peca         = pg_fetch_result($res,$i,peca);
					$qtde         = pg_fetch_result($res,$i,qtde);
					$os           = pg_fetch_result($res,$i,os);
					$distribuidor = pg_fetch_result($res,$i,distribuidor);
					
					if (strlen($distribuidor) > 0) {
						
						$sql  = "SELECT fn_pedido_cancela($distribuidor,10,$Xpedido,$peca,'$observacao')";
						//$sql  = "SELECT fn_pedido_cancela_gama($distribuidor,10,$Xpedido,$peca,$qtde,'$observacao')";
						//echo nl2br($sql) . '<br />';
						$resY = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (strlen($msg_erro) != 0) {
							$res = @pg_exec($con,"ROLLBACK TRANSACTION");
						}

					}

				}

			}

		}

	}
	
	if (strlen($msg_erro) == 0) {
		$res = @pg_exec($con,"COMMIT TRANSACTION");
		$msg = "Gravado com sucesso!";
	} else {
		$res = @pg_exec($con,"ROLLBACK TRANSACTION");
		//$msg = "Aconteceu um erro, tente novamente";
		$msg = $msg_erro;
		//echo '<br />' . $msg_erro;
	}

}

?>

<html>
<head>
<title>Pedidos da Loja Virtual</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

function fnc_pesquisa_pedido (pedido) {
	var url = "";
	url = "<?=$PHP_SELF?>?detalhe=" + pedido ;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");

}

function MudaCampo(campo){
	if (campo.value== 'recusar') {
		document.getElementById('observacao').disabled=false;
	}else{
		document.getElementById('observacao').disabled=true;
	}
}

function MostraEsconde(dados){
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		if (style2.style.display){
			style2.style.display = "";
		}else{
			style2.style.display = "block";
		}
	}
}
</script>
<style>
.posto{
	font-weight: bold;
	font-size: 15px;
}
.titulo{
	background-color: #485989;
	color:#FFFFFF;
	font-weight:bold;
}
.exibir{
	padding:8px;
	color:  #555555;
	display:none;
	background-color: #C0C0C1;
}
</style>
<script language="JavaScript">
var checkflag = "false";
function SelecionaTodos(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            if (field[i]) {
				field[i].checked = true;
			}
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
			if (field[i]) {
				field[i].checked = false;
			}
        }
        checkflag = "false";
        return true;
    }
}
</script>

</head>

<body>

<? include 'menu.php' ?>

<p>
<center><h1>Pedidos da Loja Virtual</h1></center>
<?
$codigo_posto = $_POST['codigo_posto'];
$nome         = $_POST['nome'];
$pedido       = $_POST['pedido'];

	if(strlen($msg_erro) > 0 or strlen($msg) >0){
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}
		if(strlen($msg) > 0) $msg_erro = $msg;
		echo "<center><font color='red'>".$msg_erro."</font></center>";

	}
?>

<center>

<form name='frm_estoque' action='<? echo $PHP_SELF.'?'.$_SERVER['QUERY_STRING'] ?>' method='post'>

<?
echo "<br>";
echo "<div align='left' style='position: relative; left: 10'>";
echo "<table border='0' cellspacing='0' cellpadding='0'>";
echo "<tr height='18'>";
echo "<td width='18' ><img src='../imagens/status_vermelho' align='absmiddle'/></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"$PHP_SELF?status=aprovacao\">";
echo "Pedido aguardando aprovação";
echo "</a></b></font></td><BR>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td width='18'><img src='../imagens/status_verde' align='absmiddle'/></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"$PHP_SELF?status=aprovado\">";
echo "Pedido aprovado";
echo "</a></b></font></td>";
echo "</tr>";
echo "<tr height='18'>";
echo "<td width='18'><img src='../imagens/status_cinza' align='absmiddle'/></td>";
echo "<td align='left'><font size='1'><b>&nbsp;  <a href=\"$PHP_SELF?status=reprovado\">";
echo "Pedido Reprovado";
echo "</a></b></font></td>";
echo "</tr>";
echo "</table>";
echo "</div>";
echo "<BR>";

$status = $_GET['status'];

if (strlen($status) == 0 or $status == 'aprovacao') {
	$sql_status = " AND tbl_pedido.data_aprovacao   IS NULL
                    AND (tbl_pedido.status_pedido not in (14,17) OR tbl_pedido.status_pedido IS NULL ) ";
	$bola = "vermelho";
}
if ($status == 'aprovado') {
#	$sql_status= "AND tbl_pedido.data_aprovacao IS NOT NULL AND (tbl_pedido.status_pedido IS NULL OR tbl_pedido.status_pedido <> 13)";
	$sql_status= "AND tbl_pedido.data_aprovacao IS NOT NULL ";
	$bola = "verde";
}
if($status == 'reprovado') {
	$sql_status =" AND      tbl_pedido.status_pedido in (14,17) ";
	$bola="cinza";
}
/*
		$sql = "SELECT 	tbl_posto_fabrica.codigo_posto    ,
						tbl_posto.posto                   ,
						tbl_posto.nome                    ,
						tbl_posto.fone                    ,
						tbl_posto.cnpj                    ,
						tbl_posto.ie                      ,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero  ,
						tbl_posto_fabrica.contato_cidade  ,
						tbl_posto_fabrica.contato_estado  ,
						tbl_pedido.pedido                 ,
						tbl_pedido.obs                    ,
						tbl_faturamento.faturamento       ,
						tbl_faturamento.conhecimento      ,
						tbl_faturamento.total_nota        ,
						tbl_faturamento.nota_fiscal       ,
						tbl_pedido.total                  ,
						tbl_contas_receber.documento      ,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS pedido_data,
						TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento
			FROM tbl_posto
			JOIN tbl_pedido         USING (posto)
			JOIN tbl_contas_receber USING (posto)
			JOIN tbl_pedido_item    USING (pedido)
			JOIN tbl_peca           USINg (peca)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido  = tbl_pedido_item.pedido
			LEFT JOIN tbl_faturamento   ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE       tbl_pedido.fabrica          = 10
			AND         tbl_pedido.finalizado       IS NOT NULL
			AND         tbl_pedido.exportado        IS NULL
			AND         tbl_pedido.troca            IS NOT TRUE
			AND         tbl_pedido.recebido_fabrica IS NULL
			AND   tbl_pedido.pedido_loja_virtual IS TRUE
			$sql_status
			GROUP BY	tbl_posto_fabrica.codigo_posto     ,
						tbl_posto.posto                    ,
						tbl_posto.nome                     ,
						tbl_posto.cnpj                     ,
						tbl_posto.ie                       ,
						tbl_pedido.pedido                  ,
						tbl_pedido.data                    ,
						tbl_faturamento.faturamento        ,
						tbl_faturamento.conhecimento       ,
						tbl_faturamento.total_nota         ,
						tbl_faturamento.nota_fiscal        ,
						tbl_pedido.total                   ,
						tbl_contas_receber.documento       ,
						TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY'),
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY'),
						TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY'),
						tbl_pedido.obs                     ,
						tbl_posto.fone                     ,
						tbl_posto_fabrica.contato_endereco ,
						tbl_posto_fabrica.contato_numero   ,
						tbl_posto_fabrica.contato_cidade   ,
						tbl_posto_fabrica.contato_estado
			ORDER BY tbl_pedido.pedido ";
*/

$sql = "SELECT DISTINCT tbl_posto_fabrica.codigo_posto    ,
						tbl_posto.posto                   ,
						tbl_posto.nome                    ,
						tbl_posto.fone                    ,
						tbl_posto.cnpj                    ,
						tbl_posto.ie                      ,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero  ,
						tbl_posto_fabrica.contato_cidade  ,
						tbl_posto_fabrica.contato_estado  ,
						tbl_pedido.pedido                 ,
						tbl_pedido.obs                    ,
						tbl_pedido.total                  ,
						tbl_faturamento.faturamento       ,
						tbl_faturamento.conhecimento      ,
						tbl_faturamento.nota_fiscal       ,
						tbl_faturamento.total_nota        ,
						tbl_embarque.total_frete          ,
						TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao ,
						tbl_pedido.total                  ,
						to_char (tbl_pedido.data,'DD/MM/YYYY') AS pedido_data,
						tbl_contas_receber.documento      ,
						TO_CHAR(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento
			FROM tbl_posto
			JOIN tbl_pedido     ON tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido  = tbl_pedido_item.pedido
			LEFT JOIN tbl_faturamento   ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			LEFT JOIN tbl_embarque      ON tbl_embarque.embarque = tbl_faturamento.embarque
			LEFT JOIN tbl_contas_receber ON tbl_contas_receber.posto = tbl_posto.posto and
			tbl_contas_receber.faturamento_fatura = tbl_faturamento.faturamento_fatura and
			tbl_contas_receber.distribuidor = tbl_faturamento.distribuidor
			WHERE       tbl_pedido.fabrica          = 10
			AND         tbl_pedido.finalizado       IS NOT NULL
			AND         tbl_pedido.exportado        IS NULL
			AND         tbl_pedido.troca            IS NOT TRUE
			AND         tbl_pedido.recebido_fabrica IS NULL
			AND   tbl_pedido.pedido_loja_virtual IS TRUE
			$sql_status
			ORDER BY tbl_pedido.pedido ";

		$res = pg_exec ($con,$sql);

		$posto_ant = "";
		$pedido_item_ant = "";

		$reotnro = "";
		if(pg_numrows($res) > 0){
			echo "<table width='1200' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
			echo "<tr class='titulo'>";
			if(strlen($status) == 0 or $status =='aprovacao' or $status == 'aprovado'){
				echo "<td align='center'>";
				echo "<input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.check);' style='cursor: hand;'>";
				echo "</td>";
			}
			echo "<th>Pedido</th>";
			echo "<th>Total</th>";
			echo "<th>Data</th>";
			echo "<th width='300px'>Posto</th>";
			echo "<tH>Atrasado</th>";
			if ($status =='aprovado') {
				echo "<th>Total NF</th>";
				echo "<th>Total Frete</th>";
				echo "<th>NF</th>";
				echo "<th>Emissão</th>";
				echo "<th>Boleto</th>";
				echo "<th>Vencimento</th>";
			}
			echo "</tr>";
			$cores=0;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$pedido         = pg_result($res,$i,pedido);
				$motivo_recusa  = pg_result($res,$i,obs);
				$faturamento    = pg_result($res,$i,faturamento);
				$conhecimento   = pg_result($res,$i,conhecimento);
				$posto          = pg_result($res,$i,posto);
				$total          = pg_result($res,$i,total);
				$total_nf       = pg_result($res,$i,total_nota);
				$total_frete    = pg_result($res,$i,total_frete);
				$data_pedido    = pg_result($res,$i,pedido_data);
				$codigo_posto   = pg_result($res,$i,codigo_posto);
				$nome           = pg_result($res,$i,nome);
				$fone           = pg_result($res,$i,fone);
				$contato_endereco = pg_result($res,$i,contato_endereco);
				$contato_numero = pg_result($res,$i,contato_numero);
				$contato_cidade = pg_result($res,$i,contato_cidade);
				$contato_estado = pg_result($res,$i,contato_estado);
				$documento      = pg_result($res,$i,documento);
				$vencimento     = pg_result($res,$i,vencimento);
				$total          = number_format($total,2,",",".");
				$total_nf       = number_format($total_nf,2,",",".");
				$total_frete    = number_format($total_frete,2,",",".");

				if ($pedido_ant <> $pedido) {
					$cores++;
					$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

					echo "<tr bgcolor='$cor' style=' align='center' style='font-weight:bold'>";
					echo "<input type='hidden' name='pedido_$i' value='$pedido'>";
					echo "<input type='hidden' name='faturamento_$i' value='$faturamento'>";
					if(strlen($status) == 0 or $status =='aprovacao' or $status =='aprovado'){
						echo "<td align='center' width='0'>";
						if ((trim($documento) == '' &&  $status =='aprovado') or (strlen($status) == 0 or $status =='aprovacao')) {
							echo "<input type='checkbox' name='check_$i' id='check' value='$pedido' ";
							if (strlen($msg_erro)>0){
								if (strlen($_POST["check_".$i])>0){
									echo " CHECKED ";
								}
							}
							echo ">";
						}
						echo "</td>";
					}
					echo "<td nowrap><a href =\"javascript:MostraEsconde('dados_$i')\"><img src='../imagens/status_$bola' border=0>&nbsp;&nbsp;$pedido</a></td>";
					echo "<td nowrap>R$ $total</td>";
					echo "<td>$data_pedido</td>";
					echo "<td>$nome</td>";

					#----- Posto em atraso -----#
					echo "<td nowrap>";
					$sql = "SELECT TO_CHAR (vencimento,'DD/MM/YYYY') AS vencimento, TO_CHAR (valor,'999,999.99') AS valor
							FROM tbl_contas_receber
							WHERE posto = $posto
							AND   distribuidor = 4311
							AND   vencimento < CURRENT_DATE
							AND   recebimento IS NULL";
					$resZ = pg_exec ($con,$sql);
					for ($z = 0 ; $z < pg_numrows ($resZ) ; $z++) {
						echo pg_result ($resZ,$z,vencimento);
						echo " - R$ " . pg_result ($resZ,$z,valor);
						echo "<br>";
					}
					echo "</td>";
					#---------------------------#
					
					if ($status =='aprovado') {
						echo "<td nowrap>R$ $total_nf</td>";
						echo "<td nowrap>R$ $total_frete</td>";
						echo "<td><a href =\"javascript:MostraEsconde('dados_$i')\">" . pg_result ($res,$i,nota_fiscal) . "</a></td>";
						echo "<td>" . pg_result ($res,$i,emissao) . "</td>";
						echo "<td>" . $documento . "</td>";
						echo "<td>" . $vencimento . "</td>";
					}
					echo "</tr>";
					$pedido_ant = $pedido;

					echo "<tr><td colspan='12' align='center'>";
					echo "<div class='Exibir' id='dados_$i' style='float:left; width:1200px'>";
					echo "<br />";
					echo "<div>";
                        echo "<table width='1200px' border='1' align='center' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF'>";
    						echo "<tr>";
                                echo "<td align='center' class='posto'>" . pg_result($res,$i,nome) . "</td>";
    						echo "</tr>";
    						echo "<tr>";
    							echo "<td align='center'>CNPJ: ".pg_result($res,$i,cnpj)."&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;";
    							echo " I.E.:".pg_result($res,$i,ie)."&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;Fone: $fone</td>";
    						echo "</tr>";
    						echo "<tr><td align='center'>$contato_endereco $contato_numero</td></tr>";
                            echo "<tr><td align='center'>$contato_cidade - $contato_estado</b></td></tr>";
						echo "</table>";
						echo "<br /><br />";
                    echo "</div>";
					
					if(strlen($motivo_recusa) > 0 AND $status == 'reprovado'){
						echo "<br><b>Motivo da reprovaçao: $motivo_recusa</b>";
					}
					
                    echo "<div>";
					echo "<table width='590px' border='1' align='center' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF' style='float:left'>";
						echo "<tr><td colspan='5' align='center'><b>PEDIDO</b></td></tr>";
						echo "<tr class='titulo'>";
                            echo "<th>#</th>\n";
							echo "<th>Referência</th>";
							echo "<th>Produto</th>";
							echo "<th>Qtd</th>";
							echo "<th>Preço</th>";
						echo "</tr>";
					
					$sqlx = "SELECT tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_pedido_item.preco,
									tbl_pedido_item.qtde
							FROM tbl_pedido
							JOIN tbl_pedido_item USING (pedido)
							JOIN tbl_peca USING (peca)
							WHERE tbl_pedido.pedido = $pedido
							ORDER BY tbl_peca.descricao ";
					
					$resx = pg_exec($con,$sqlx);
                    
					$tot_qtd_pedido   = 0;
					$tot_preco_pedido = 0;
					
					for ($j = 0; $j < pg_numrows($resx); $j++) {
					    
					    $preco_pedido = pg_result($resx,$j,preco);
					    $qtd_pedido   = pg_result($resx,$j,qtde);
						
						$cor_item = ($j % 2 == 0) ? '#E8EBEE': '#FEFEFE';
						
						$tot_qtd_pedido   += $qtd_pedido;
						$tot_preco_pedido += ($qtd_pedido * $preco_pedido);

						echo "<tr bgcolor='$cor_item' style=' align='center'>";
                            echo "<td align='center'>".($j + 1)."</td>\n";
    						echo "<td align='center'>".pg_result($resx,$j,referencia)."</td>";
    						echo "<td align='center'>".pg_result($resx,$j,descricao)."</td>";
    						echo "<td align='center'>".$qtd_pedido."</td>";;
    						echo "<td align='right'>R$&nbsp;".number_format($preco_pedido,2,",",".")."</td>";
						echo "</tr>";
					}
					
					echo "<tr class='titulo'>\n";
							echo "<td align='left' colspan='3'>Total:</td>\n";
							echo "<td align='center' nowrap>$tot_qtd_pedido</td>\n";
							echo "<td align='center' nowrap>R$ ".number_format($tot_preco_pedido,2,",",".")."</td>\n";
					echo "</tr>\n";
					
					echo "</table>";

					if ($faturamento != '') {

						$sql = "SELECT      tbl_peca.referencia                                        ,
											tbl_peca.descricao                                         ,
											tbl_faturamento_item.qtde                                  ,
											tbl_faturamento_item.preco                                 ,
											tbl_faturamento_item.pedido                                ,
											to_char(tbl_pedido.data, 'DD/MM/YYYY')       AS data_pedido,
											tbl_faturamento_item.os                                    ,
											tbl_os.sua_os                                              ,
											to_char(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_os    ,
											tbl_posto.nome                               AS posto_nome ,
											tbl_posto.cidade                             AS posto_cidade
								FROM        tbl_faturamento_item
								JOIN        tbl_peca       ON tbl_peca.peca     = tbl_faturamento_item.peca
								LEFT JOIN   tbl_pedido     ON tbl_pedido.pedido = tbl_faturamento_item.pedido
								LEFT JOIN   tbl_os         ON tbl_os.os         = tbl_faturamento_item.os
								LEFT JOIN   tbl_posto      ON tbl_pedido.posto  = tbl_posto.posto
								WHERE       tbl_faturamento_item.faturamento = $faturamento
								ORDER BY    tbl_peca.descricao";

						$resul = pg_exec($con,$sql);
					
						if (pg_numrows($resul) > 0) {
							
							echo "<table width='590px' border='1' align='center' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF' style='float:left; margin-left:10px'>";
							echo "<tr><th colspan='5' align='center'>PEÇAS FATURADAS</b></th></tr>";
							echo "<tr class='titulo'>";
    							echo "<th class='menu_top'>#</th>\n";
    							echo "<th class='menu_top'>Peça</th>\n";
    							echo "<th class='menu_top'>Descrição</th>\n";
    							echo "<th class='menu_top'>Qtd</th>\n";
    							echo "<th class='menu_top'>Preço</th>\n";
							echo "</tr>\n";
							
							$tot_qtd   = 0;
							$tot_preco = 0;
							
							for ($s = 0 ; $s < pg_numrows($resul); $s++) {
								$peca        = trim(pg_result($resul,$s,referencia));
								$descricao   = trim(pg_result($resul,$s,descricao));
								$qtde        = trim(pg_result($resul,$s,qtde));
								$preco       = trim(pg_result($resul,$s,preco));
								$pedido      = trim(pg_result($resul,$s,pedido));
								$sua_os      = trim(pg_result($resul,$s,sua_os));
								$data_os     = trim(pg_result($resul,$s,data_os));
								$posto_cidade= trim(pg_result($resul,$s,posto_cidade));
								
								$tot_qtd   += $qtde;
								$tot_preco += ($qtde * $preco);
					
								$cor = ($s % 2 == 0) ? '#E8EBEE': '#FEFEFE';

								echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
    								echo "<td align='center' nowrap>" . ($s+1) . "</td>\n";
    								echo "<td align='center' nowrap>$peca</td>\n";
    								echo "<td align='center' nowrap>$descricao</td>\n";
    								echo "<td align='center'>$qtde</font></td>\n";
    								echo "<td align='right'>R$ ". number_format($preco,2,",",".") ."</td>\n";
								echo "</tr>\n";
							}
							
							echo "<tr class='titulo'>\n";
    								echo "<td align='left' colspan='3'>Total:</td>\n";
    								echo "<td align='center' nowrap>$tot_qtd</td>\n";
    								echo "<td align='center' nowrap>R$ ".number_format($tot_preco,2,",",".")."</td>\n";
							echo "</tr>\n";
							echo "</table>\n";

						}

					}
					
					echo "</div>";
			
				}
				
				echo "</div>";
				echo "</td></tr>";

			}

			echo "<tr>";
			echo "<td height='20' bgcolor='#485989' colspan='12' align='left'> ";
			if (strlen($status) == 0 or $status =='aprovacao' or $status =='aprovado'){
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='../admin/imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
				if (strlen($status) == 0 or $status =='aprovacao') {
					echo "<select name='select_acao' size='1' class='frm' onChange='MudaCampo(this)'>";
					echo "<option value=''></option>";
					echo "<option value='aprovar'";  if ($_POST["select_acao"] == "aprovar")  echo " selected"; echo ">APROVAR</option>";
					echo "<option value='recusar'";  if ($_POST["select_acao"] == "recusar")  echo " selected"; echo ">REPROVAR</option>";
					echo "</select>";
				} else {
					echo "<input class='frm' type='hidden' name='select_acao' id='select_acao' size='30' maxlength='250' value='recusar' />";
				}
				echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
				
				if ($status =='aprovado') {
					echo "&nbsp;&nbsp;<input type='submit' name='btn_reprovar' value='Reprovar' />";
				} else {
					echo "&nbsp;&nbsp;<input type='submit' name='btn_aprovar' value='Gravar'>";
				}
				
			}
			echo "<input type='hidden' name='qtde_pedido' value='$i'>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		} else {
			echo "<tr><td>";
			echo "<center><h2>Nenhum pedido encontrado</h></center>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}

	echo "</form>";

include "rodape.php";?>

</form>
</body>
</html>