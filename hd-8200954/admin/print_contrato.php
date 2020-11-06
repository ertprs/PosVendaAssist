<?php
$printer = false;
if (isset($_GET["pg"]) && $_GET["pg"] == "print") {
	$printer = true;
}
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$areaAdminRepresentante = preg_match('/\/admin_representante\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_admin.php';
} elseif ($areaAdminRepresentante === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_admin.php';
	include_once 'fn_traducao.php';
} elseif ($areaAdminCliente) {
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include_once 'fn_traducao.php';
	$dirLogo = "../";
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}


use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;

$objContratoStatus = new ContratoStatus($login_fabrica, $con);
$objContrato       = new Contrato($login_fabrica, $con);
$tipo = $_GET['tipo'];
$label_tipo = " do Contrato";
if ($tipo == "proposta") {
	$label_tipo = " da Proposta";
}
$contrato = $_GET['contrato'];
$dadosContratos   = $objContrato->get($contrato);
extract($dadosContratos[0]);
$campo_extra = json_decode($campo_extra,1);
extract($campo_extra);


$logos = [];
$logos[190] = $dirLogo."logos/".strtolower($login_fabrica_nome)."_logo.png";
function geraDataTimeNormal($data) {
    list($ano, $mes, $vetor) = explode("-", $data);
    $resto = explode(" ", $vetor);
    $dia = $resto[0];
    return $dia."/".$mes."/".$ano;
}
if (!$printer) {

include 'cabecalho_new.php';

$plugins = array(
   "dataTable",
    "multiselect",
   "datepicker",
   "shadowbox",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "font_awesome",
   "autocomplete"

);

include("plugin_loader.php");
}
?>

<?php if ($printer) {?>
<html>
<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="all" />
	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="all" />
<style type="text/css">

.titulo_tabela {
font-weight: bold;
background-color: #596d9b;
color: #fff;
}

.box-print {
max-width: 800px;
/*font-size: 10px;*/
margin: 0 auto;
}

table {
width: 100%;
font-size: 12px;
}

.tar{
	text-align: right;
}
h4 {
    font-size: 14.5px;
}
.table-bordered td.tar {
    background: #D9E2EF;
    -webkit-print-color-adjust: exact !important;
}
.table-itens .titulo_itens th{
    background-color: #D9E2EF !important;
    -webkit-print-color-adjust: exact !important;
}   
.table-bordered th, .table-bordered td {
    border-color:#ccc !important;
}
@media print {
	@page {
	    size: A4;
	    margin: 5mm;
	}
	.titulo_tabela {
		font-weight: bold !important;
		background-color: #596d9b !important;
	    -webkit-print-color-adjust: exact !important;
		color: #fff !important;
	}
	.table-bordered td.tar {
	    background-color: #D9E2EF !important;
	    -webkit-print-color-adjust: exact !important;
	}   
	.table-itens .titulo_itens th{
	    background-color: #D9E2EF !important;
	    -webkit-print-color-adjust: exact !important;
	} 
	.table-bordered th, .table-bordered td {
	    border-color:#ccc !important;
	}  
}
</style>


</head>
<body>
<?php } else {?>
	<style type="text/css">
		.titulo_tabela {
		font-weight: bold;
		background-color: #596d9b;
		color: #fff;
		}

		.box-print {
		max-width: 800px;
		/*font-size: 10px;*/
		margin: 0 auto;
		}

		table {
		width: 100% !important;
		font-size: 12px;
		}

		.tar{
			text-align: right;
		}
		h4 {
		    font-size: 14.5px;
		}
		.table-bordered td.tar {
		    background: #D9E2EF;
		    -webkit-print-color-adjust: exact !important;
		}
		.table-itens .titulo_itens th{
		    background-color: #D9E2EF !important;
		    -webkit-print-color-adjust: exact !important;
		}   
		.table-bordered th, .table-bordered td {
		    border-color:#ccc !important;
		}

	</style>

<?php }?>

	<div class="container" >
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th><img src="<?php echo $logos[$login_fabrica];?>" alt=""></th>
				<th class="tar" colspan="4">
					<div class="tac" style="width: 200px;border: solid 1px #eee;float: right;">
						<h4>Nº <?php echo $label_tipo;?></h4>
						<h1><?=$contrato?></h1>
						<p><?=$nome_status?></p>
					</div>
				</th>
			</tr>
		</table>
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Informações <?php echo $label_tipo;?></th>
			</tr>
			<tr>
				<td class="tar" nowrap><b>Genêro:</b> </td>
				<td><?=($genero_contrato == "M") ? "Manutenção" : "Locação";?></td>
				<td class="tar" nowrap><b>Tipo <?php echo $label_tipo;?>:</b></td>
				<td><?=$tipo_contrato_nome?></td>
				<td class="tar" nowrap><b>Tabela de Preço:</b></td>
				<td colspan="3"><?=$nome_tabela?></td>
			</tr>
			<tr>
				<?php if ($tipo == "contrato") {?>
				<td class="tar" nowrap><b>Data Vigência:</b></td>
				<td> <?=geraDataTimeNormal($data_vigencia)?></td>
				<?php }?>
				<td class="tar" nowrap><b>Qtde Preventivas:</b></td>
				<td> <?=$qtde_preventiva?></td>
				<?php if ($tipo == "contrato") {?>
				<td class="tar" nowrap><b>Dia da Preventiva:</b></td>
				<td> <?=$dia_preventiva?></td>
				<?php }?>
				<td class="tar" nowrap><b>Qtde Corretivas:</b></td>
				<td colspan="<?php echo ($tipo == "contrato") ? 1 : 5;?>"> <?=$qtde_corretiva?></td>
			</tr>
			<?php if ($tipo == "contrato" && isset($mao_obra_fixa)) {?>
			<tr>
				<td class="tar" nowrap><b>M.O Fixa:</b></td>
				<td> <?=$mao_obra_fixa?></td>
				<td class="tar" nowrap><b>Valor M.O Fixa:</b></td>
				<td colspan="5"> <?php echo  'R$ '.number_format($valor_mao_obra_fixa, 2, ',', '.');?></td>
			</tr>
			<?php }?>
	

		</table>
		<?php if ($tipo == "contrato") {?>
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Descrição do <?php echo $label_tipo;?></th>
			</tr>
			<tr>
				<td class="tar" class="tar" nowrap><b>Descrição:</b></td>
				<td colspan="5"><?=$descricao?></td>
			</tr>
		</table>
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Informações do Posto Autorizado</th>
			</tr>
			<tr>
				<td class="tar" class="tar" nowrap><b>Código:</b></td>
				<td><?=$representante_codigo?></td>
				<td class="tar" nowrap><b>Nome:</b></td>
				<td colspan="5"> <?=$representante_nome?></td>
			</tr>
		</table>
		<?php }?>
		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Informações do Representante</th>
			</tr>
			<tr>
				<td class="tar" class="tar" nowrap><b>Código:</b></td>
				<td><?=$representante_codigo?></td>
				<td class="tar" nowrap><b>Nome:</b></td>
				<td colspan="5"> <?=$representante_nome?></td>
			</tr>
			<tr>
				<td class="tar" nowrap><b>CPF/CNPJ:</b></td>
				<td> <?=$cpf_cnpj_representante?></td>
				<td  class="tar" nowrap><b>Telefone:</b></td>
				<td> <?=$fone_representante?></td>
				<td  class="tar" nowrap><b>Email:</b></td>
				<td colspan="3"> <?=$email_representante?></td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="8" >Informações do Cliente</th>
			</tr>
			<tr>
				<td class="tar" nowrap><b>Nome:</b></td>
				<td colspan="5"> <?=$cliente_nome?></td>
				<td class="tar" nowrap><b>CPF/CNPJ:</b></td>
				<td><?=$cliente_cpf?></td>
			</tr>
			<tr>
				<td class="tar" nowrap><b>E-mail:</b></td>
				<td colspan="3"><?=$cliente_email?></td>
				<td class="tar" nowrap><b>Telefone:</b> </td>
				<td><?=$cliente_fone?></td>
				<td class="tar" nowrap><b>Celular:</b></td>
				<td><?=$cliente_celular?></td>
			</tr>
		
			<tr>
				<td class="tar" nowrap><b>CEP:</b></td>
				<td><?=$cliente_cep?></td>
				<td class="tar" nowrap><b>Endereço:</b></td>
				<td colspan="3"><?=$cliente_endereco?></td>
				<td class="tar" nowrap><b>Número:</b></td>
				<td><?=$cliente_numero?></td>
			</tr>
			<tr>
				<td class="tar" nowrap><b>Complemento:</b> </td>
				<td><?=$cliente_complemento?></td>
				<td class="tar" nowrap><b>Bairro:</b> </td>
				<td><?=$cliente_bairro?></td>
				<td class="tar" nowrap><b>Cidade:</b> </td>
				<td><?=$cliente_cidade?></td>
				<td class="tar" nowrap><b>UF:</b> </td>
				<td><?=$cliente_uf?></td>
			</tr>
		</table>
		<?php if ($areaAdmin === true && $tipo == "contrato") {

			$sqlAud = "SELECT tbl_contrato_auditoria.*, 
					  TO_CHAR(tbl_contrato_auditoria.data_input, 'DD/MM/YYYY') as data,
					  TO_CHAR(tbl_contrato_auditoria.aprovado, 'DD/MM/YYYY')  as aprovado,
					  TO_CHAR(tbl_contrato_auditoria.reprovado, 'DD/MM/YYYY') as reprovado,
 					  tbl_admin.nome_completo as nome_admin
                                     FROM tbl_contrato_auditoria 
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_contrato_auditoria.admin AND tbl_admin.fabrica = $login_fabrica
                                    WHERE contrato = $contrato ORDER BY tbl_contrato_auditoria.data_input DESC";
			$resAud = pg_query($con, $sqlAud);
//print_r(pg_last_error());exit;
			if (pg_num_rows($resAud) > 0) {
		?>


		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;" >
			<tr>
				<th class="titulo_tabela" colspan="6" >Auditorias do Contrato</th>
			</tr>
			<tr>
				<td class="tac" nowrap><b>Data:</b></td>
				<td class="tal" nowrap><b>Detalhe:</b></td>
				<td class="tac" nowrap><b>Aprovação:</b></td>
				<td class="tac" nowrap><b>Reprovação:</b></td>
				<td class="tal" nowrap><b>Motivo:</b></td>
				<td class="tal" nowrap><b>Admin:</b></td>
			</tr>
			<?php foreach(pg_fetch_all($resAud) as $ik => $lin) {?>
			<tr>
				<td class='tac'><?=$lin['data']?></td>
				<td><?=$lin['obs']?></td>
				<td class='tac'><?=$lin['aprovado']?></td>
				<td class='tac'><?=$lin['reprovado']?></td>
				<td><?=$lin['motivo']?></td>
				<td><?=$lin['nome_admin']?></td>
			</tr>


			<?php }?>
		</table>
			<?php }?>
		<?php }?>


		<table class="table table-bordered table-itens" style="margin: 0 auto; table-layout: fixed;margin-top: 5px;margin-bottom: 55px;" >
			<tr>
				<th class="titulo_tabela" colspan="5" >Informações do Produto/Serviço</th>
			</tr>
			<tr class="titulo_itens">
				<th class="tac">Referencia</th>
				<th class="tal">Descrição</th>
				<th class="tac">Preço</th>
				<th class="tac">Horimetro</th>
				<th class="tac">Preventiva</th>
			</tr>
			<?php
            	$dadosItens = $objContrato->getItens($contrato);
                if (count($dadosItens) > 0 && !isset($dadosItens["erro"])) {
                	$total_produtos = 0;
            		foreach ($dadosItens as $k => $value) {
            			$total_produtos += $value["preco"];
            ?>
			<tr>
                <td class="tac"><?=$value["referencia_produto"];?></td>
                <td class="tal"><?=$value["nome_produto"];?></td>
                <td class="tac"><?php echo  'R$ '.number_format($value["preco"], 2, ',', '.');?></td>
                <td class="tac"><?=$value["horimetro"];?></td>
                <td class="tac"><?=($value["preventiva"] == "t") ? "Sim" : "Não";?></td>
			</tr>
			<?php
					} 
				} 
			?>
			<tr>
                <td class="tar" colspan="4"><h4>Valor Total de Produtos <?php echo $label_tipo;?></h4></td>
                <td class="tac"><h4><?php echo  'R$ '.number_format($total_produtos, 2, ',', '.');?></h4></td>
			</tr>
			<tr>

                <td class="tar" colspan="4"><h4>Desconto <?php echo $label_tipo;?></h4></td>
                <td class="tac"><h4><?php echo $desconto_representante."%";?></h4></td>
			</tr>
			<tr>
                <td class="tar" colspan="4"><h4>Valor Total <?php echo $label_tipo;?></h4></td>
                <td class="tac"><h4><?php echo  'R$ '.number_format($valor_contrato, 2, ',', '.');?></h4></td>
			</tr>
		</table>
	</div>
<?php
if (!$printer) {
?>
	<div class="row-fluid">
		<div class="span12 tac">
			<a href="print_contrato.php?tipo=<?php echo $_GET["tipo"];?>&contrato=<?php echo $_GET["contrato"];?>&pg=print" target="_blank" class="btn btn-info btn-xlarger"><i class="icon-print icon-white"></i> Imprimir</a>
		</div>
	</div>
<?php 
include "rodape.php";
} else {
?>
</body>	
</html>
<?php }?>

