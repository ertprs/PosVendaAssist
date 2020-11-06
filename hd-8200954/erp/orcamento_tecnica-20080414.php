<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

include '../funcoes.php';

if (strlen($_GET['filtro']) > 0)    $filtro = strtoupper(trim($_GET['filtro']));
else                                $filtro = strtoupper(trim($_POST['filtro']));

if (strlen($_GET['acao']) > 0)	$acao = strtoupper(trim($_GET['acao']));
else							$acao = strtoupper(trim($_POST['acao']));

if (strlen($_GET['tipo']) > 0)	$tipo = strtoupper(trim($_GET['tipo']));
else							$tipo = strtoupper(trim($_POST['tipo']));

if (strlen($_GET['orcamento']) > 0)	$orcamento = strtoupper(trim($_GET['orcamento']));
else								$orcamento = strtoupper(trim($_POST['orcamento']));


if (isset($_POST['gravarDataconserto']) AND isset($_POST['orcamento'])){
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$orcamento          = trim($_POST['orcamento']);
	if (strlen($orcamento)>0){
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto ='null';
		}

		$sql = "UPDATE tbl_orcamento_os
				SET data_conserto = $gravarDataconserto
				WHERE orcamento=$orcamento";
		$res = pg_exec($con,$sql);
	}
		exit;
}


//     29 | Consertado          
//     30 | Entregue            
//     31 | Reprovado           
//     32 | Sucateado           
//     33 | Pronto para Entrega 
//     34 | Cancelado           
//     35 | Finalizado          
//     36 | Montagem do Produto 


if (strlen($acao)>0 AND strlen($orcamento)>0 AND $tipo=='OS'){

/*
select * from tbl_status_os;
 status_os |           descricao            |
-----------+--------------------------------+
         5 | Sem conserto                   |
        75 | Aguardando aprovação           |
        76 | Venda em Andamento             |
        77 | Produto Entregue               |
        78 | Sucateado                      |
        79 | Consertado                     |
*/

######  tbl_status_os #######
	if ($acao=='CONSERTADO'){
		$status_os=79;
		$status = 33;
		$msg_acao = "Produto consertado";
	}
	if ($acao=='SUCATEADO'){
		$status_os=78;
		$status = 35;
		$msg_acao = "Produto sucateado";
	}
	if ($acao=='ENTREGUE'){
		$status_os=77;
		$status = 35;
		$msg_acao = "Produto entregue.";
	}
	if ($acao=='SEMCONSERTO'){
		$status_os=5;
		$status = 33;
		$msg_acao = "Produto sem conserto.";
	}
	if ($acao=='ENTREGUESEMCONSERTO'){
		$status_os= 5;
		$status = 77;
		$msg_acao = "Produto entregue ao cliente sem conserto.";
	}

	if ($acao=='ENTREGUESEMCONSERTOCANCELADO'){
		$status_os= 77;
		$status=31;
		$msg_acao = "Produto entregue ao cliente sem conserto.";


	}

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	$sql_est1 = "UPDATE tbl_orcamento_os
				SET status = $status_os
				WHERE orcamento = $orcamento";
	$res_est1 = pg_exec ($con,$sql_est1);
	$msg_erro .= pg_errormessage($con);

	$sql_est2 = "UPDATE tbl_orcamento
				SET status = $status
				WHERE orcamento = $orcamento
				AND empresa     = $login_empresa
				AND loja        = $login_loja";
	$res_est2 = pg_exec ($con,$sql_est2);
	$msg_erro .= pg_errormessage($con);

	$res = pg_exec ($con,"SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento=$orcamento");
	$hd_chamado  = pg_result ($res,0,0);

	if (strlen($hd_chamado)==0){$msg_erro .= "Ocorreu um erro inesperado. Contate o suporte";}

	$sql = "INSERT INTO tbl_hd_chamado_item 
				(hd_chamado,empregado,posto,comentario)
				VALUES ($hd_chamado,$login_empregado,$login_loja,
				'$msg_acao'
			)";
	$res = pg_exec ($con,$sql);

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($acao)>0 AND strlen($orcamento)>0 AND $tipo=='VENDA'){
	/*
	 status |        descricao        | fabrica | afeta_pedido | afeta_os | work_flow
	--------+-------------------------+---------+--------------+----------+-----------
		 77 | Entregue Sem Conserto   |      27 |              |          |
		 28 | Aprovado                |      27 |              |          |
		 30 | Entregue                |      27 |              |          |
		 31 | Reprovado               |      27 |              |          |
		 33 | Pronto para Entrega     |      27 |              |          |
		 34 | Cancelado               |      27 |              |          |
		 35 | Finalizado              |      27 |              |          |
		 36 | Montagem do Produto     |      27 |              |          |
	*/
	######  tbl_status #######

	if ($acao=='ENTREGUE'){
		$status=30;
		$msg_acao = "Produto entregue.";
	}
	if ($acao=='PRONTO'){
		$status=33;
		$msg_acao = "Produto pronto para entrega.";
	}
	if ($acao=='CANCELADO'){
		$status=34;
		$msg_acao = "Cancelado";
	}
	if ($acao=='MONTAGEM'){
		$status=36;
		$msg_acao = "Produto em montagem";
	}
	if ($acao=='FINALIZADO'){
		$status=35;
		$msg_acao = "Venda finalizada";
	}

	if (strlen($status)==0){
		$msg_erro .= "Erro. Contate o suporte.";
	}



//ENTREGUESEMCONSERTOCANCELADO

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	$sql_est = "UPDATE tbl_orcamento
				SET status = $status
				WHERE orcamento = $orcamento
				AND empresa     = $login_empresa
				AND loja        = $login_loja";
	$res_est = pg_exec ($con,$sql_est);
	$msg_erro .= pg_errormessage($con);

	$res = pg_exec ($con,"SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento=$orcamento");
	$hd_chamado  = pg_result ($res,0,0);

	if (strlen($hd_chamado)==0){$msg_erro .= "Ocorreu um erro inesperado. Contate o suporte";}

	$sql = "INSERT INTO tbl_hd_chamado_item 
				(hd_chamado,empregado,posto,comentario)
				VALUES ($hd_chamado,$login_empregado,$login_loja,
				'$msg_acao'
			)";
	$res = pg_exec ($con,$sql);

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'vendas') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>

<script language='javascript'>

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function fnc_imprimir(orcamento) {

	var url = "orcamento_print?orcamento="+orcamento;
	janela_aut = window.open(url, "_blank", "width=795,height=650,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0");
	janela_aut.focus();
}

function gravar_status(linha,id,tipo){
	if (document.getElementById(linha).value != ''){
		if (confirm('Deseja continuar?')){
			var linha = document.getElementById(linha).value;
			window.location = 'orcamento_tecnica.php?acao='+linha+'&orcamento='+id+'&tipo='+tipo;
		}
	}
}
	$(function(){
		$("input[@rel='data_conserto']").maskedinput("99/99/9999 99:99");
	});

$().ready(function() {
	$("input[@rel='data_conserto']").blur(function(){
		var campo = $(this);

			$.post('<? echo $PHP_SELF; ?>', 
				{ 
					gravarDataconserto : campo.val(),
					orcamento: campo.attr("alt")

				}, 
				function(resposta){
				}
			);
		
	});
});
</script>


<? include "javascript_pesquisas.php" ?>

<style>
.Titulo_tela{
	text-align: center;
	font-size: 14px;
	font-weight: bold;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.titulo_tabela {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

table { 
		font:0.7em Arial, Helvetica, sans-serif; 
	/*background-color:#F2F2F2; */
}
caption { 
/*	background-color:#5A666E; */
	text-align:'right';
/*	background-color:#D5EAEA;*/
	text-transform:uppercase; 
	font-weight:bold; 
	font-size:10px; 
}
caption select{
	font-size:10px; 
}
thead th { 
	background-color:#F5B348; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	border-top:1px solid #F4D39E; 
	border-left:1px solid #F4D39E; 
	border-bottom:1px solid #B76E00; 
	border-right:1px solid #B76E00; 
}
tfoot th { 
	background-color:#F29601; 
	color:#724809; 
	padding:2px; 
	text-transform:uppercase; 
	font-size:1.2em; 
}
tfoot td { 
	background-color:#FC0; 
	color:#724809; 
	font-weight:bold; 
	text-transform:uppercase; 
	font-size:1.2em; 
	padding:0px 5px; 
}
.odd {  }
tbody td { 
	/* #F1F4FA" : "#F7F5F0"; */
	/*background-color:#F1F4FA; */
	color:#5A666E; 
/*	padding:2px; 
	text-align:center; 
	border-top:1px solid #FFF; 
	border-left:1px solid #FFF; 
	border-bottom:1px solid #AFB5B8; 
	border-right:1px solid #AFB5B8;  */
}
tbody th { 
/*	background-color:#5A666E; 
	color:#D7DBDD; */
	padding:2px; 
	text-align:center; 
	border-top:1px solid #93A1AA; 
	border-left:1px solid #93A1AA; 
	border-bottom:1px solid #2F3B42; 
	border-right:1px solid #2F3B42;
}
tbody td a {  
	color:#724809; 
	text-decoration:none; 
	font-weight:bold;
}
tbody td a:hover { 
	background-color:#F5B348; 
	color:#FFF;
}
tbody th a {
	color:#FFF; 
	text-decoration:none; 
	font-weight:bold;
}
tbody th a:hover { 
	color:#FC0; 
	text-decoration:underline;
}

a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}


</style>

<? if (strlen($msg_erro)>0) {?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<!-- TITULO   -->

<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
<tr >
	<td width='20'></td>
	<td nowrap class='Titulo_tela' align='ceter'>Ordem de Serviços</td>
	<td width='20'></td>
</tr>
</table>
<!--LISTAGEM DOS ORCAMENTOS  -->

<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='98%' border='0'>
<caption>
</caption>
<thead>
	<tr>
		<td nowrap class='Titulo'>#</td>
		<td nowrap class='Titulo'>Data</td>
		<td nowrap class='Titulo'>Cliente</td>
		<td class='Titulo'>Valor Total</td>
<!--		<td nowrap class='Titulo'>Vendedor</td>-->
		<td nowrap class='Titulo'>Status OS</td>
		<td  class='Titulo'>Status Produto</td>
		<td nowrap class='Titulo'>Box</td>
		<td nowrap class='Titulo'>NF Saída</td>
		<td nowrap class='Titulo'>Data NF Saída</td>
		<td nowrap class='Titulo'>Data Conserto</td>
		<td nowrap class='Titulo' colspan='2'>Ações</td>
	</tr>
</thead>
<tbody>
<? 


	if (strlen($filtro)>0){
		if(strtoupper($filtro)=="APROVADOS"){
			$filtro=" AND tbl_orcamento.data_aprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="REPROVADOS"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="AGUARDANDO"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NULL AND tbl_orcamento.data_aprovacao IS NULL";
		}else{
			$filtro="";
		}
	}

	$sql = "SELECT	
					LPAD(tbl_orcamento.orcamento,4,'0')                 AS orcamento_numero,
					tbl_orcamento.orcamento                             AS orcamento,
					to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY')  AS data,
					tbl_orcamento.total_mao_de_obra                     AS total_mao_de_obra,
					tbl_orcamento.aprovado                              AS aprovado,
					to_char(tbl_orcamento.data_aprovacao,'DD/MM/YYYY')  AS aprovacao,
					to_char(tbl_orcamento.data_reprovacao,'DD/MM/YYYY') AS reprovacao,
					tbl_cliente.cliente                                 AS cliente,
					tbl_cliente.nome                                    AS cliente_nome,
					tbl_orcamento.consumidor_nome                       AS consumidor_nome,
					tbl_pessoa.nome                                     AS vendedor,
					tbl_orcamento.total                                 AS total,
					tbl_status.descricao                                AS status_desc,
					tbl_status_os.descricao                             AS status_os_desc,
					tbl_status.status                                   AS status,
					tbl_status_os.status_os                             AS status_os,
				to_char(tbl_orcamento_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) AS data_conserto    ,
				to_char(tbl_orcamento_os.data_nf_saida, 'DD/MM/YYYY' )         AS data_nf_saida    ,
				tbl_orcamento_os.nf_saida                                                          ,
				tbl_orcamento_os.prateleira_box
			FROM   tbl_orcamento
			JOIN tbl_orcamento_os USING(orcamento)
			LEFT JOIN tbl_cliente USING(cliente)
			LEFT JOIN tbl_empregado ON tbl_empregado.empregado = tbl_orcamento.vendedor
			LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_empregado.pessoa
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_empregado.loja
			LEFT JOIN tbl_status ON tbl_status.status = tbl_orcamento.status
			LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_orcamento_os.status
			WHERE tbl_orcamento.empresa=$login_empresa

			$filtro
			ORDER BY orcamento ASC";
	$res = pg_exec ($con,$sql) ;
//	echo $sql;
	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
		$orcamentoX        = trim(pg_result ($res,$x,orcamento_numero));
		$orcamentoY        = trim(pg_result ($res,$x,orcamento));
		$data              = trim(pg_result ($res,$x,data));
		$total_mao_de_obra = trim(pg_result ($res,$x,total_mao_de_obra));
		$aprovado          = trim(pg_result ($res,$x,aprovado));
		$aprovacao         = trim(pg_result ($res,$x,aprovacao));
		$reprovacao        = trim(pg_result ($res,$x,reprovacao));
		$cliente           = trim(pg_result ($res,$x,cliente));
		$cliente_nome      = trim(pg_result ($res,$x,cliente_nome));
		$consumidor_nome   = trim(pg_result ($res,$x,consumidor_nome));
		$vendedor          = trim(pg_result ($res,$x,vendedor));
		$total             = trim(pg_result ($res,$x,total));
		$status            = trim(pg_result ($res,$x,status));
		$status_os         = trim(pg_result ($res,$x,status_os));
		$status_desc       = trim(pg_result ($res,$x,status_desc));
		$status_os_desc    = trim(pg_result ($res,$x,status_os_desc));
		$data_conserto     = trim(pg_result ($res,$x,data_conserto));
		$nf_saida          = trim(pg_result ($res,$x,nf_saida));
		$data_nf_saida     = trim(pg_result ($res,$x,data_nf_saida));
		$prateleira_box    = trim(pg_result ($res,$x,prateleira_box));

		
		if (strlen($consumidor_nome)==0){
			$consumidor_nome = $cliente_nome;
		}
		if ($aprovado=='t'){
			$data_aprovacao="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if ($aprovado=='f'){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b> ";
		}else{
			$data_aprovacao=" - ";
		}

		if (strlen($aprovacao)>0){
			$data_aprovaca="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if (strlen($reprovacao)>0){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b>";
		}else{
			$data_aprovacao=" - ";
		}

		$total_mao_de_obra = number_format($total_mao_de_obra,2,'.','');
		$total = number_format($total,2,',','.');
			if($cor == "#FFFFFF") $cor = "#F0F5FB";
			else                  $cor = "#FFFFFF";

		echo "<tr bgcolor=$cor>";
		echo "<td align='center'><a href='orcamento_cadastro.php?orcamento=$orcamentoY' target='_blank'>$orcamentoX</a></td>";
		echo "<td align='center'>$data</td>";
		echo "<td>$consumidor_nome</td>";
		echo "<td align='right'> $total</td>";
//		echo "<td align='center'>$vendedor</td>";
		echo "<td align='center'>$status_desc</td>";
		echo "<td align='center'>$status_os_desc</td>";
		echo "<td align='center'>$prateleira_box</td>";
		echo "<td align='center'>$nf_saida</td>";
		echo "<td align='center'>$data_nf_saida </td>";
		echo "<td><input class='frm' type='text' name='data_conserto_$i' alt='$orcamentoY' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'></td>";

		echo "<td align='right'>";

		if ($status_os==77 AND $status=31){
			echo "-";
		}else{
			echo "
					<select name='atualizar_$x' style='font-size:10px'>
						<option value=''></option>";

			if ($status_os==80){
				echo "<option value='CONSERTADO'>CONSERTADO</option>";
				echo "<option value='SEMCONSERTO'>SEM CONSERTO</option>";
				echo "<option value='SUCATEADO'>SUCATEADO</option>";
			}

			if ($status_os==79){
				echo "<option value='ENTREGUE'>ENTREGUE</option>";
			}
			if ($status_os==5 ) {
				echo "<option value='ENTREGUESEMCONSERTO'>ENTREGUE</option>";
			}
			if ($status_os==81 ) {
				echo "<option value='ENTREGUESEMCONSERTOCANCELADO'>ENTREGUE</option>";
			}
			if ($status_os==77){
				echo "<option value='FINALIZADO'>FINALIZADO</option>";
			}

			echo "	</select></td>";
			echo "<td>
					<input type='button' name='btn_ok' value='Gravar' onClick=\"javascript:gravar_status('atualizar_$x',$orcamentoY,'OS')\">
					";
		}
		echo "</td>";
	}
	if(pg_numrows ($res)==0){
		echo "<tr>";
		echo "<td align='center' colspan='8'><b>Nenhuma Ordem de Serviço</b></td>";
		echo "</td>";
	}
?>
</tbody>
<tfoot>

</tfoot>
</table>


<!-- TITULO   -->
<br><br>
<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
<tr >
	<td width='20'></td>
	<td nowrap class='Titulo_tela' align='ceter'>Venda de Produtos</td>
	<td width='20'></td>
</tr>
</table>
<!--LISTAGEM DOS ORCAMENTOS  -->

<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
<caption>
</caption>
<thead>
	<tr>
		<td nowrap class='Titulo'>#</td>
		<td nowrap class='Titulo'>Data</td>
		<td nowrap class='Titulo'>Cliente</td>
		<td nowrap class='Titulo'>Valor Total</td>
		<td nowrap class='Titulo'>Vendedor</td>
		<td nowrap class='Titulo'>Status</td>
		<td nowrap class='Titulo' colspan='2'>Ações</td>
	</tr>
</thead>
<tbody>
<? 


	if (strlen($filtro)>0){
		if(strtoupper($filtro)=="APROVADOS"){
			$filtro=" AND tbl_orcamento.data_aprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="REPROVADOS"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NOT NULL ";
		}else
		if(strtoupper($filtro)=="AGUARDANDO"){
			$filtro=" AND tbl_orcamento.data_reprovacao IS NULL AND tbl_orcamento.data_aprovacao IS NULL";
		}else{
			$filtro="";
		}
	}

	$sql = "SELECT	
					LPAD(tbl_orcamento.orcamento,4,'0')                 AS orcamento_numero,
					tbl_orcamento.orcamento                             AS orcamento,
					to_char(tbl_orcamento.data_digitacao,'DD/MM/YYYY')  AS data,
					tbl_orcamento.total_mao_de_obra                     AS total_mao_de_obra,
					tbl_orcamento.aprovado                              AS aprovado,
					to_char(tbl_orcamento.data_aprovacao,'DD/MM/YYYY')  AS aprovacao,
					to_char(tbl_orcamento.data_reprovacao,'DD/MM/YYYY') AS reprovacao,
					tbl_cliente.cliente                                 AS cliente,
					tbl_cliente.nome                                    AS cliente_nome,
					tbl_orcamento.consumidor_nome                       AS consumidor_nome,
					tbl_pessoa.nome                                     AS vendedor,
					tbl_orcamento.total                                 AS total,
					tbl_status.descricao                                AS status_desc,
					tbl_status.status                                   AS status
			FROM   tbl_orcamento
			JOIN tbl_orcamento_venda USING(orcamento)
			LEFT JOIN tbl_cliente USING(cliente)
			LEFT JOIN tbl_empregado ON tbl_empregado.empregado = tbl_orcamento.vendedor
			LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_empregado.pessoa
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_empregado.loja
			LEFT JOIN tbl_status ON tbl_status.status = tbl_orcamento.status
			WHERE tbl_orcamento.empresa=$login_empresa
			AND tbl_orcamento.aprovado IS TRUE
			AND tbl_orcamento.status<>35
			$filtro
			ORDER BY orcamento ASC";
	$res = pg_exec ($con,$sql) ;
	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
		$orcamentoX        = trim(pg_result ($res,$x,orcamento_numero));
		$orcamentoY        = trim(pg_result ($res,$x,orcamento));
		$data              = trim(pg_result ($res,$x,data));
		$total_mao_de_obra = trim(pg_result ($res,$x,total_mao_de_obra));
		$aprovado          = trim(pg_result ($res,$x,aprovado));
		$aprovacao         = trim(pg_result ($res,$x,aprovacao));
		$reprovacao        = trim(pg_result ($res,$x,reprovacao));
		$cliente           = trim(pg_result ($res,$x,cliente));
		$cliente_nome      = trim(pg_result ($res,$x,cliente_nome));
		$consumidor_nome   = trim(pg_result ($res,$x,consumidor_nome));
		$vendedor          = trim(pg_result ($res,$x,vendedor));
		$total             = trim(pg_result ($res,$x,total));
		$status            = trim(pg_result ($res,$x,status));
		$status_desc       = trim(pg_result ($res,$x,status_desc));
		
		if (strlen($consumidor_nome)==0){
			$consumidor_nome = $cliente_nome;
		}
		if ($aprovado=='t'){
			$data_aprovacao="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if ($aprovado=='f'){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b> ";
		}else{
			$data_aprovacao=" - ";
		}

		if (strlen($aprovacao)>0){
			$data_aprovaca="<b style='color:blue;font-weight:normal'>Aprovado</b>";
		}else
		if (strlen($reprovacao)>0){
			$data_aprovacao="<b style='color:red;font-weight:normal'>Reprovado</b>";
		}else{
			$data_aprovacao=" - ";
		}

		$total_mao_de_obra = number_format($total_mao_de_obra,2,'.','');
		$total = number_format($total,2,'.','');

		echo "<tr>";
		echo "<td align='center'><a href='orcamento_cadastro.php?orcamento=$orcamentoY' target='_blank'>$orcamentoX</a></td>";
		echo "<td align='center'>$data</td>";
		echo "<td>$consumidor_nome</td>";
		echo "<td align='right'>R$ $total</td>";
		echo "<td align='center'>$vendedor</td>";
		echo "<td align='center'>$status_desc</td>";
		echo "<td align='right'>";

		if ($status==27){
			echo "-";
		}else{
			echo "	<select name='atualizar_$x' style='font-size:10px'>
					<option value=''></option>";

			if ($status==28){
				echo "<option value='MONTAGEM'>MONTAGEM DO PRODUTO</option>";
			}
			if ($status==36){
				echo "<option value='PRONTO'>PRONTO PARA ENTREGA</option>";
			}
			if ($status==33){
				echo "<option value='ENTREGUE'>ENTREGUE</option>";
			}
			if ($status==30){
				echo "<option value='FINALIZADO'>FINALIZADO</option>";
			}
			echo "	</select></td>";
			echo "<td>
					<input type='button' name='btn_ok' value='Gravar' onClick=\"javascript:gravar_status('atualizar_$x',$orcamentoY,'VENDA')\">
			</td>";
			echo "</td>";
		}
	}
	if(pg_numrows ($res)==0){
		echo "<tr>";
		echo "<td align='center' colspan='8'><b>Nenhum Orçamento encontrado</b></td>";
		echo "</td>";
	}
?>
</tbody>
<tfoot>

</tfoot>
</table>

<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>


<?
 //include "rodape.php";
 ?>


