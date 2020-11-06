<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';

$btn_acao = $_POST["btn_acao"];

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ORDEM DE SERVIÇO DE TROCA";

$btn_acao       = $_POST['btn_acao'];

function getThead(){
    global $login_fabrica;

    $thead = "<thead>
               <tr >
                   <th>Número da OS</th>
                   <th>Data de Abertura</th>
                   <th>Data da Troca</th>
                   <th>Data de Fechamento</th>
                   <th>Admin</th>
                   <th>Nome do Consumidor</th>
                   <th>Documento do Consumidor</th>";
    if (in_array($login_fabrica, array(35))) {
        $thead .= "<th> Referência Produto </th>";
        $thead .= "<th> Descrição Produto </th>";
    } else {
        $thead .= "<th> Produto</th>";
    }

        $thead .= "<th> Nome Posto</th>
                    <th> Motivo da troca</th>
                </tr>
            </thead>";

    return $thead;

}

function getTbody($res){
    global $login_fabrica;

    $tbody = "<tbody>";

    for ($i = 0; $i < pg_num_rows($res); $i++) {

        $result = pg_fetch_object($res, $i);
        $dataAbertura = new DateTime($result->data_abertura);
        $dataTroca = new DateTime($result->data_troca);
        $dataFechamento = $result->data_fechamento;

        $tbody .= "<tr>";

		$tbody	 .= "<td >".$result->sua_os ."</td>";
		$tbody	 .= "<td >".$dataAbertura->format("d/m/Y")."</td>";
		$tbody	 .= "<td >".$dataTroca->format("d/m/Y")."</td>";
		$tbody	 .= "<td >".$dataFechamento ."</td>";
		$tbody	 .= "<td >".$result->nome_completo ."</td>";
		$tbody	 .= "<td >".$result->consumidor_nome ."</td>";
        $tbody .= "<td>". formata_cpf_cnpj($result->consumidor_cpf) . "</td>";

        if (in_array($login_fabrica, array(35))) {
            $tbody   .= "<td >".$result->produto_referencia."</td>";
            $tbody   .= "<td >".$result->produto_descricao."</td>";
        } else {
            $tbody   .= "<td >".$result->produto_referencia . " - " . $result->produto_descricao ."</td>";
        }

		$tbody	 .= "<td >".$result->codigo_posto . " - " .$result->posto_nome ."</td>";
		$tbody	 .= "<td >".$result->motivo_troca ."</td>";

        $tbody .= "</tr>\n";
    }
    $tbody .= "</tbody>";
    return $tbody;
}

function getTFoot($res){
    return "<tfoot>
               <tr>
                   <td> Total de Registros: ".pg_num_rows($res). "</td>
              </tr>
            </tfoot>";
}
function montaArquivo($fp, $res){
    $tHead = "<table>". getThead();
    $tBody = getTbody($res);
    $tFoot = getTFoot($res);

    fwrite($fp, $tHead.$tBody.$tFoot);
}
if($btn_acao == 'submit'){

	$data_inicial        = trim($_POST['data_inicial']);
	$data_final          = trim($_POST['data_final']);
	$codigo_posto        = trim($_POST['codigo_posto']);
	$motivo_troca        = $_POST['motivo_troca'];
	$admin               = $_POST['admin'];
    $produto_referencia = $_POST["produto_referencia"];
	if(strlen($data_inicial) == 0 and strlen($data_final) == 0 and strlen($os_troca_especifica) == 0) {
		$msg_erro["msg"][]= "Data Inválida";
	}

	//Início Validação de Datas
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)){
                $msg_erro["msg"][]= "Data Inválida";
            }
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$xdata_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$xdata_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($xdata_final < $xdata_inicial){
			$msg_erro["msg"][] = "Data Inválida.";
		}

		//Fim Validação de Datas
	}

    if (strlen($msg_erro) == 0 ) {
        //$aprova = $_POST['aprova'];
        $cond_posto  = " 1=1 ";
        $cond_motivo = " 1=1 ";
        $cond_admin = "1=1";
        $cond_produto = " 1=1";
        $codigo_posto = trim($_POST['codigo_posto']);

        if(strlen($admin) > 0){
            $cond_admin = " tbl_admin.admin = {$admin} ";
        }
        if(strlen($codigo_posto) > 0){
            $cond_posto = "  tbl_posto_fabrica.codigo_posto = '$codigo_posto'
						AND tbl_posto_fabrica.fabrica = $login_fabrica ";
        }


        if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
            if($login_fabrica == 35){
                $cond_data = " tbl_os_troca.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
            }else{
                $cond_data = " tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'";
            }
        }

        if(strlen($motivo_troca) > 0){
        	$cond_motivo = " tbl_os_troca.causa_troca = $motivo_troca ";
        }

        if(strlen($produto_referencia) > 0){
            $cond_produto = " tbl_produto.referencia = '{$produto_referencia}'";
        }
        if($_POST["gerar_excel"] == "true"){
            $limit = "";
        }else{
            $limit = " limit 501 ";
        }

        if (in_array($login_fabrica,array(35))){
            $join_admin = "LEFT JOIN tbl_admin ON tbl_os_troca.admin = tbl_admin.admin";
        }else{
            $join_admin = "LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.troca_garantia_admin";
        }
        //hD 14932 mudou a busca de data abertura pela data de digitação
        $sql = "SELECT
                      tbl_os.os,
                      tbl_os.sua_os,
                      tbl_os.data_abertura,
                      to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento,
                      tbl_os_troca.data as data_troca,
                      tbl_admin.nome_completo,
                      tbl_os.consumidor_nome,
                      tbl_os.consumidor_cpf,
                      tbl_posto_fabrica.codigo_posto,
                      tbl_posto.nome as posto_nome,
                      tbl_produto.referencia as produto_referencia,
                      tbl_produto.descricao as produto_descricao,
                      tbl_causa_troca.causa_troca,
                      tbl_causa_troca.descricao as motivo_troca
                FROM tbl_os_troca
                JOIN tbl_os	       ON tbl_os.os		  = tbl_os_troca.os AND tbl_os.fabrica = {$login_fabrica}
                JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca
                JOIN tbl_produto	  ON tbl_produto.produto     = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
                JOIN tbl_posto		  ON tbl_os.posto	     = tbl_posto.posto
                JOIN tbl_posto_fabrica	  ON tbl_posto.posto	     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                $join_admin
                WHERE tbl_os_troca.fabric = $login_fabrica
                AND   tbl_os.fabrica	  = $login_fabrica
                AND $cond_produto
				AND $cond_posto
                AND $cond_admin
				AND $cond_motivo
				AND $cond_data
				ORDER BY tbl_posto_fabrica.codigo_posto asc,tbl_os.os asc {$limit}";

        // echo nl2br($sql);exit; 
        $resSubmit = pg_exec($con,$sql);

        if($_POST["gerar_excel"] == "true"){

            if(pg_num_rows($resSubmit) > 0){
                $data = date("d-m-Y-H:i");
                $fileName = "relatorio_os_troca_produtos_".$data.".xls";

                $fp = fopen("/tmp/{$fileName}", "w");

                montaArquivo($fp, $resSubmit);
                fclose($fp);
                if (file_exists("/tmp/{$fileName}")) {

                    system("mv /tmp/{$fileName} xls/{$fileName}");

                    echo "xls/{$fileName}";
                }
            }
            exit;
        }
    }

}

$sqlOptionMotivoTroca = "SELECT causa_troca, descricao FROM tbl_causa_troca WHERE fabrica = {$login_fabrica} and ativo";
$resOptionMotivoTroca = pg_query($con, $sqlOptionMotivoTroca);
$countOptionsMotivoTroca = pg_num_rows($resOptionMotivoTroca);
$optionsMotivoTroca = array();
if($countOptionsMotivoTroca > 0){
  $optionsMotivoTroca[""] = "Todos os Motivos";

  for($i = 0; $i < $countOptionsMotivoTroca; $i++){
    $causaTroca = pg_fetch_object($resOptionMotivoTroca, $i);
    $optionsMotivoTroca[$causaTroca->causa_troca] = $causaTroca->descricao;
  }
}else{
  $optionsMotivoTroca["nenhum"] = "<option> Nenhum Motivo de Troca Cadastrado</option>";
}

$sqlOptionAdmin = "SELECT admin, nome_completo FROM tbl_admin where fabrica = {$login_fabrica} AND ativo order by nome_completo ASC";
$resOptionAdmin = pg_query($con, $sqlOptionAdmin);
$countOptionsAdmin = pg_num_rows($resOptionAdmin);
$optionsAdmin = array();
if($countOptionsAdmin > 0){
  $optionsAdmin[""] = "Todos os Admins";

  for($i = 0; $i < $countOptionsAdmin; $i++){
    $admin = pg_fetch_object($resOptionAdmin, $i);
    $optionsAdmin[$admin->admin] = $admin->nome_completo;
  }
}else{
  $optionsAdmin["nenhum"] = "<option> Nenhum Admin Cadastrado</option>";
}

include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "autocomplete",
    "dataTable",
    "shadowbox",
    "mask"
);

/* Include do plugin_loader */
include("plugin_loader.php");

?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>
<?
$inputs = array(
	"data_inicial"=> array(
		"span"=>4,
		"width" => 6,
	        "type"=>"input/text",
		"label"=> ($login_fabrica == 35) ? "Data Inicial de Troca" : "Data Inicial"
	),
	 "data_final"=> array(
                "span"=>4,
		"width" => 6,
                "type"=>"input/text",
                "label"=> ($login_fabrica == 35) ? "Data Final de Troca" : "Data Final"
        ),
    "codigo_posto"=> array(
        "span"=>4,
		"width" => 6,
        "type"=>"input/text",
        "label"=>"Posto Código",
		"lupa"=>array("name"=>"lupa", "tipo"=>"posto", "parametro"=>"codigo")
    ),
    "descricao_posto"=> array(
        "span"=>4,
        "width" => 12,
        "type"=>"input/text",
        "label"=>"Nome Posto",
		"lupa"=>array("name"=>"lupa", "tipo"=>"posto", "parametro"=>"nome")
    ),
    "produto_referencia"=> array(
        "span"=>4,
		"width" => 6,
        "type"=>"input/text",
        "label"=>"Referência Produto",
		"lupa"=>array("name"=>"lupa", "tipo"=>"produto", "parametro"=>"referencia")
    ),
    "produto_descricao"=> array(
        "span"=>4,
        "width" => 12,
        "type"=>"input/text",
        "label"=>"Descrição Produto",
		"lupa"=>array("name"=>"lupa", "tipo"=>"produto", "parametro"=>"descricao")
    ),
    "admin"=> array(
        "span"=>4,
        "width" => 7,
        "type"=>"select",
        "label"=>"Admin",
        "options" => $optionsAdmin
    ),
    "motivo_troca"=> array(
        "span"=>4,
		"width" => 12,
        "type"=>"select",
        "options" => $optionsMotivoTroca,
        "label"=>"Motivo Troca",

    )

);
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_pesquisa" method="post" action="<?=$PHP_SELF?>" class='form-search form-inline tc_formulario'>


    <div class="titulo_tabela">Parâmetros para Pesquisa</div>

    <br />

<? echo montaForm($inputs); ?>

		<p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>
<?
if (isset($resSubmit)) {

		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="resultado_os_troca_produtos" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >

            			<th>Número da OS</th>
            			<th>Data de Abertura</th>
            			<th>Data da Troca</th>
			            <th>Data de Fechamento</th>
            			<th>Admin</th>
			            <th>Nome do Consumidor</th>
                        <th>Documento do Consumidor</th>
                        <?php
                        if (in_array($login_fabrica, array(35))) { ?>
                            <th> Ref. Produto</th>
                            <th> Descrição Produto</th>
                        <?php
                        } else { ?>
			                <th> Produto</th>
                        <?php
                        } ?>
              			<th> Nome Posto</th>
						<th> Motivo da troca</th>
		</tr>
                </thead>
				<tbody>
					<?php
                    for ($i = 0; $i < $count; $i++) {

                        $result = pg_fetch_object($resSubmit, $i);
                        $dataAbertura   = new DateTime($result->data_abertura);
                        $dataTroca      = new DateTime($result->data_troca);
					    $dataFechamento = $result->data_fechamento;
                    ?>
                    <tr>

               			<td nowrap><a href="os_press.php?os=<?=$result->os?>" target="_blank" > <?=$result->sua_os?> </a> </td>
						<td nowrap><?=$dataAbertura->format("d/m/Y")?> </td>
						<td nowrap><?=$dataTroca->format("d/m/Y")?> </td>
						<td nowrap><?=$dataFechamento?> </td>
						<td nowrap><?=$result->nome_completo?> </td>
						<td nowrap><?=$result->consumidor_nome?> </td>
                        <td nowrap><?=formata_cpf_cnpj($result->consumidor_cpf)?></td>
                        <?php
                        if (in_array($login_fabrica, array(35))) { ?>
                            <td nowrap><?= $result->produto_referencia ?> </td>
                            <td nowrap><?= $result->produto_descricao ?> </td>
                        <?php
                        } else { ?>
                            <td nowrap><?=$result->produto_referencia . " - " . $result->produto_descricao?> </td>
                        <?php
                        } ?>
						<td nowrap><?=$result->codigo_posto . " - " .$result->posto_nome?> </td>
						<td nowrap><?=$result->motivo_troca?> </td>
                   </tr>
                   <? }    ?>

            </tbody>
	    <? if($count <= 50){ ?>
	      <tfoot>
		<tr>

		  <td>Total</td>
		  <td><?=$count?></td>
		</tr>
	      </tfoot>

	    <? } ?>
            </table>

   	<?php if ($count > 50) { ?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_troca_produtos" });
				</script>
   <?php  }	?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
<?   }else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
include "rodape.php" ?>
