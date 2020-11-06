<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
}
else {
	$admin_privilegios="gerencia";
	$layout_menu = "gerencia";
}
include 'autentica_admin.php';



if (isset($_POST["btn_acao"]) == "Pesquisar") {

	$data_inicio 	= $_POST["data_inicio"];
	$data_fim		= $_POST["data_fim"];
	$posto_nome 	= $_POST["posto_nome"];
	$codigo_posto   = $_POST["codigo_posto"];

	if(strlen(trim($data_inicio)) == 0 and strlen(trim($data_fim))==0){
		$erro .= "Por favor informar a data.";
	}

	if(strlen(trim($codigo_posto))>0){
		$join_posto 		= "left join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto 
								and tbl_posto_fabrica.fabrica = $login_fabrica ";
		$complemento_sql 	= " and tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	list($diD, $diM, $diY) = explode("/", $data_inicio);
	$data_inicio_banco = $diY."-".$diM."-".$diD;
	list($dfD, $dfM, $dfY) = explode("/", $data_fim);
	$data_fim_banco = $dfY."-".$dfM."-".$dfD;

	$data1 = new DateTime("$data_inicio_banco");
	$data2 = new DateTime("$data_fim_banco");

	$intervalo = $data1->diff($data2);

	if($intervalo->days > 90){
		$erro .= "O período não pode ser superior a 3 meses.";
	}

	if(strlen(trim($erro))==0){
			$sql = "SELECT tbl_pedido.data, tbl_pedido.pedido, tbl_tipo_pedido.descricao as tipo_descricao, tbl_pedido_item.qtde, tbl_peca.descricao as peca_descricao, tbl_peca.referencia FROM tbl_pedido 
			inner join tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido 
					and tbl_tipo_pedido.fabrica = $login_fabrica
			inner join tbl_pedido_item on tbl_pedido.pedido = tbl_pedido_item.pedido
			inner join tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
			$join_posto
			WHERE tbl_pedido.fabrica = $login_fabrica and tbl_pedido.data BETWEEN '$data_inicio_banco 00:00:00' AND '$data_fim_banco 23:59:59' 
			$complemento_sql 
			ORDER BY tbl_pedido.data";
			$resultado = pg_query($con, $sql);
			//echo nl2br($sql);
	}

	if(isset($_POST['gerar_excel'])){
		
		if(pg_num_rows($resultado)>0){

			$data 		= date("d-m-Y-H-i");
			$fileName 	= "relatorio_pedido_pecas_{$data}.csv";
			$file 		= fopen("/tmp/{$fileName}", "w");


			$head = "Data Pedido;Pedido;Tipo;Qtde Solicitada;Código Peça;Descrição Peça \r\n";
			fwrite($file, $head);

			for($j=0; $j<pg_num_rows($resultado); $j++){
				$data 			= substr(pg_fetch_result($resultado, $j, data), 0, 10);
				$pedido			= pg_fetch_result($resultado, $j, pedido);
				$tipo_descricao	= pg_fetch_result($resultado, $j, tipo_descricao);
				$peca_descricao	= pg_fetch_result($resultado, $j, peca_descricao);
				$referencia		= pg_fetch_result($resultado, $j, referencia);
				$qtde 			= pg_fetch_result($resultado, $j, qtde);

				list($dY, $dM, $dD) = explode("-", $data);

				$data = $dD."/".$dM."/".$dY;

				$body .= "$data;$pedido;$tipo_descricao;$qtde;$referencia;$peca_descricao \r\n";
			}

			fwrite($file, $body);
			fclose($file);
			
			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}
		exit;
	}

}

$title = "RELATÓRIO DE PEDIDO DE PEÇA";

include "cabecalho_new.php";
$jsonPOST = excelPostToJson($_POST);
	$plugins = array( 	"multiselect",
						"lupa",
						"autocomplete",
						"datepicker",
						"mask",
						"dataTable",
						"shadowbox"
				);
	include "plugin_loader.php";
?>

<script type="text/javascript">
$(function(){
            var login_fabrica = <?=$login_fabrica?>;
            $.datepickerLoad(Array("data_fim", "data_inicio"));
            $("span[rel=lupa]").click(function () {
                        $.lupa($(this));
            });
            Shadowbox.init();
            if(login_fabrica == 86){
                        $("#linha").multiselect({
                                    selectedText: "selecionados # de #"
                        });
            }

            if ($.inArray(login_fabrica, ["52", "30", "85"]) > -1) {

		//Busca pelo NOME DO CLIENTE ADMIN - FRICON
		$("#cliente_nome_admin").autocomplete("relatorio_tempo_conserto_mes_ajax.php?tipo_busca=cliente_admin&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			max: 30,
			matchContains: true,
			formatItem: formatCliente,
			formatResult: function(row) {
			return row[3];
			}
		});

		$("#cliente_nome_admin").result(function(event, data, formatted) {
			$("#cliente_admin").val(data[0]) ;
			$("#cliente_nome_admin").val(data[3]) ;
		});
    }

    function formatCidade(row) {
        return row[0] + " - " + row[1];
    }

    if ($("select[name=estado]").val() != ""){
        $("#cidade_consumidor").show();
        $("input[name=cidade]").removeAttr("readonly");
    }else{
        $("#cidade_consumidor").hide();
        $("input[name=cidade]").attr("readonly");
    }

    if ($("select[name=pais]").val() == "BR"){
        $("#estado").show();
    }else{
        $("#estado").hide();
    }

    $("select[name=estado]").change(function(){
        var regiao = $("select[name=estado]").val();
        if (regiao != ""){
            $("#cidade_consumidor").show();
            $("input[name=cidade]").val("");
            $("input[name=cidade]").removeAttr("readonly");
        }else{
            $("#cidade_consumidor").hide();
            $("input[name=cidade]").val("");
            $("input[name=cidade]").attr("readonly");
        }
    });

    $("select[name=pais]").change(function(){
        var pais = $("select[name=pais]").val();
        if (pais != "BR"){
            $("select[name=estado]").val("");
            $("input[name=cidade]").val("");
            $("#estado").hide();
            $("#cidade_consumidor").hide();
        }else{
            $("#estado").show();
        }
    });


    function regiao(){
        return $("select[name=estado]").val();
    }

});


    function retorna_posto(posto){
        gravaDados('codigo_posto',posto.codigo);
        gravaDados('posto_nome',posto.nome);
    }

   	function gravaDados(name, valor){
        try {
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
    }


	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatCliente(row) {
		return row[2] + " - " + row[3] + " - Cidade: " + row[4];
	}

	function maiuscula(valor)
	{
		var valor = $("input[name=cidade]").val();
		var valor = valor.toUpperCase();
		$("input[name=cidade]").val(valor);
	}
</script>

<?



//HD 216470: Acrescentada a validação de dados ($msg_erro)

if(strlen(trim($erro))>0){ ?>
   	<div class="alert alert-error">
		<h4><?php echo $erro ?></h4>
    </div>
<?php 
}

?>


<form name='frm_percentual' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'>Data Início</label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicio" value="<?php echo $data_inicio ?>" id="data_inicio" class="span6">
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'>Data Fim</label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_fim" value="<?php echo $data_fim ?>" id="data_fim" class="span6">
					</div>
				</div>
			</div>
		
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_nome'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>
            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <input type="submit"  class="btn" name="btn_acao" value="Pesquisar" >
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"></div>
        </div>
	</form>
	<br>
	<?
	echo "<center><font size='1'></font></center>";
	if(isset($_POST["btn_acao"]) and strlen(trim($erro))==0 and pg_num_rows($resultado) > 0 ){ 

	?>
	<table id="relatorio" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class='titulo_coluna'>
			<th>Data Pedido</th> 
			<th>Pedido</th>
			<th>Tipo</th>
			<th>Qtde Solicitada</th>
			<th>Código Peça</th>
			<th>Descrição Peça</th>
		</tr>		
	</thead>
	<tbody>
	<?php 

		$count = pg_num_rows($resultado);

		for($i=0; $i<pg_num_rows($resultado); $i++){
			$data 			= substr(pg_fetch_result($resultado, $i, data), 0, 10);
			$pedido			= pg_fetch_result($resultado, $i, pedido);
			$tipo_descricao	= pg_fetch_result($resultado, $i, tipo_descricao);
			$peca_descricao	= pg_fetch_result($resultado, $i, peca_descricao);
			$referencia		= pg_fetch_result($resultado, $i, referencia);
			$qtde 			= pg_fetch_result($resultado, $i, qtde);

			list($dY, $dM, $dD) = explode("-", $data);

			$data = $dD."/".$dM."/".$dY;

			echo "<tr bgcolor='#F7F5F0'>
					<td>$data</td> 
					<td>$pedido</td> 
					<td>$tipo_descricao</td> 
					<td>$qtde</td>
					<td>$referencia</td> 
					<td>$peca_descricao</td> 
				</tr>";
		}

	?>
	</body>
	</table>

	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>

<?
	if ($count > 50) {
		?>
			<script>
				$.dataTableLoad({ table: "#relatorio" });
			</script>
		<?php
	}

}

if(isset($_POST["btn_acao"]) and strlen(trim($erro))==0 and pg_num_rows($resultado) ==0 ){ ?>
	<div class="alert alert-error">
		<h4>Nenhum registro encontrado.</h4>
    </div>
<?php 

}


	include "rodape.php";
?>
