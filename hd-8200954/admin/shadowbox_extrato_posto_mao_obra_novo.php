<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
include_once('../anexaNF_inc.php');
include "../classes/mpdf61/mpdf.php";
include "../plugins/fileuploader/TdocsMirror.php";

if (isset($_POST['ajax_acao'])) {

	$os = $_POST['os'];

	$sinalizador = $_POST['sinalizador'];

	pg_query($con, "BEGIN");

	$sqlSinalizador = "UPDATE tbl_os SET sinalizador = {$sinalizador} WHERE os = {$os}";
	$resSinalizador = pg_query($con, $sqlSinalizador);

	if ($sinalizador == 9) {

		$caixa = $_POST['caixa'];

		$sql = "SELECT  tbl_os.os           ,
					tbl_os.sua_os           ,
					tbl_os.posto            ,
					tbl_os_extra.mao_de_obra,
					tbl_os_extra.extrato    ,
					to_char(data_geracao,'MM/YYYY') as data_geracao
			FROM    tbl_os 
			JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os and tbl_os.fabrica = tbl_os_extra.i_fabrica
			JOIN    tbl_extrato USING(extrato)
			WHERE   tbl_os.fabrica = $login_fabrica 
				AND tbl_os.os= {$os}";
		$res = pg_query ($con,$sql);

		$os           = pg_fetch_result($res,0,os);
		$sua_os       = pg_fetch_result($res,0,sua_os);
		$posto        = pg_fetch_result($res,0,posto);
		$mao_de_obra  = pg_fetch_result($res,0,mao_de_obra);
		$xextrato      = pg_fetch_result($res,0,extrato);
		$data_geracao = pg_fetch_result($res,0,data_geracao);

		$xhistorico =  "Regularização de OS nº $sua_os, pertinente ao extrato {$data_geracao}, caixa arquivo {$caixa}.";

		$sql = "INSERT INTO tbl_extrato_lancamento (
								posto                ,
								fabrica              ,
								lancamento           ,
								historico            ,
								valor                ,
								admin                
								) VALUES (
								$posto               ,
								$login_fabrica       ,
								153                  ,
								'$xhistorico'        ,
								'$mao_de_obra'       ,
								$login_admin         
								);";
		$res = pg_query ($con,$sql);

	}

	$retorno = "sucesso";

	if (pg_last_error()) {
		$retorno = "erro";
		pg_query($con, "ROLLBACK");
	} else {
		pg_query($con, "COMMIT");
	}

	exit($retorno);
}
?>
<style type="text/css">
.Tabela{
    border:1px solid #d2e4fc;
    background-color: white; 
}
.menu{
	border: 1px solid #d2e4fc;
	color: #ffffff;
	background-color: #596D9B;
}
.titulo{
	border: 1px solid #d2e4fc;
	color: #ffffff;
	background-color: #596D9B;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<?php
$os =  $_GET['os'];
$sqlOs = "SELECT 	tbl_os_extra.os,
					tbl_os.sua_os,
					tbl_os.sinalizador,
					tbl_os.consumidor_nome,
					tbl_os.data_nf,
					tbl_os.nota_fiscal,
					tbl_os.revenda_nome,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_marca.nome
			FROM tbl_os_extra 
				JOIN tbl_os USING (os) 
				JOIN tbl_produto USING (produto)
				JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.os = {$os} ;
			";
$resOs =  pg_query($con, $sqlOs);

$sinalizadorAtual = pg_fetch_result($resOs, 0, 'sinalizador');

$s3_tdocs = new TdocsMirror();
foreach (pg_fetch_all($resOs) as $pdf) {?>
	<body align='center' style="text-align: -moz-center;background-color: white;z-index: 9999 !important;margin-top: 20%;">
	<table style="margin-left: 100px;" class='Tabela' align="center">
		<tr>
			<td rowspan='8'>
				<center>OS FABRICANTE<br><br>&nbsp;<b>
				<FONT SIZE='6' COLOR='#C67700'><?=$pdf['sua_os']?></FONT>
				<center>CONSUMIDOR&nbsp;<b>
				<BR><font color='#D81005' SIZE='4' ><strong><?=$pdf['nome']?></strong></font>	
			</td>
		</tr>
			<td colspan='2' class='menu'>INFORMAÇÔES DA OS</td>
		<tr>
			<td class='titulo'>DATA DA NF</td>
			<td><?=$pdf['data_nf']?></td>
		</tr>
		<tr>
			<td class='titulo'>NF</td>
			<td><?=$pdf['nota_fiscal']?></td>
		</tr>
		<tr>
			<td class='titulo'>REVENDA</td>
			<td><?=$pdf['revenda_nome']?></td>
		</tr>
		<tr>
			<td class='titulo'>REFÊRENCIA</td>
			<td><?=$pdf['referencia']?></td>
		</tr>
		<tr>
			<td class='titulo'>DESCRIÇÃO</td>
			<td><?=$pdf['descricao']?></td>
		</tr>
		<tr>
			<td class='titulo'>NOME</td>
			<td><?=$pdf['consumidor_nome']?></td>
		</tr>
	</table>
	<?php
	 	$os = $pdf['os'];
		$sqlTdocs = "SELECT tdocs_id, obs FROM tbl_tdocs where fabrica = {$login_fabrica} AND referencia = 'os' AND referencia_id = '{$os}' UNION SELECT tdocs_id , tbl_tdocs.obs from tbl_tdocs join tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_tdocs.referencia_id JOIN tbl_os ON tbl_os.os = tbl_os_revenda_item.os_lote WHERE tbl_tdocs.fabrica = $login_fabrica and contexto='os' and tbl_os.os = $os;";
		$resultImg = pg_query($con, $sqlTdocs);
		$idTdocs = pg_fetch_result($resultImg, 0, tdocs_id);
		
		if (empty($idTdocs)) {
			echo "<div  style='width: 100%;height: 100%;background-color: white;margin-left: 100px;'><label>Os sem Nota Fiscal</label></div>";
		}else {
			$getTdocs = $s3_tdocs->get($idTdocs);

			$obs = json_decode(pg_fetch_result($resultImg, 0, obs));
			$ext = end(explode('.', $obs[0]->filename));

			if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
				echo "<table align='center' class='Tabela' style='width:560px;margin-left: 100px; cursor:pointer;text-align:center;'><tr><td><td data-girar='direita'> Girar Direita </td>";
				echo "<td data-girar='original'> Original </td>";
				echo "<td data-girar='baixo'> Baixo </td>";
				echo "<td data-girar='esquerda'> Girar Esquerda </td></tr></table>";
				echo "<img align='center' data-zoom='false'  src='{$getTdocs['link']}' width='570' height='570' style='margin-left: 100px;'>";
			} else {
				$im = new Imagick();

				$data = file_get_contents($getTdocs['link']);
				$content_os = 'xls/' . $os . '.pdf';
				file_put_contents($content_os, $data);

				chmod($content_os, 0777);
				$im->readimage($content_os . '[0]'); 
				$im->setImageFormat('jpeg');    
				$im->writeImage('xls/' . $os . '.jpg'); 
				$im->clear(); 
				$im->destroy();

				$postImage = $s3_tdocs->post("xls/{$os}.jpg");
				$image = $s3_tdocs->get($postImage[0]["{$os}.jpg"]['unique_id']);

				echo "<img align='center' data-zoom='false'  src='{$image['link']}' width='570' height='570' style='margin-left: 100px;'>";
				unlink('xls/' . $os . '.jpg');
				unlink($os . '.pdf');
			}
		}		
	 	
	?>
<?php } ?>
<br /><br />
<style>
#acoes {
	background-color: #E7EAF1;
	top: 0;
	left: 0;
	border: black 1px solid;
	width: 100%;
	min-width: 317px;
	border-radius: 0px 0px 5px 0px;
	position: fixed;
}
button {
	width: 25%;
	color:#fff;
	margin-left: 6.3%;
	float: left;
	cursor: pointer;
	margin-top: 1.5%;
	font-weight: 400;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	user-select: none;
	border: 1px solid transparent;
    border-top-color: transparent;
    border-right-color: transparent;
    border-bottom-color: transparent;
    border-left-color: transparent;
	padding: 0.375rem 0.75rem;
	font-size: 1rem;
	line-height: 1.5;
	border-radius: 0.25rem;
	transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-acao[data-acao=aprovado] {
	background-color: #28a745;
	border-color: #28a745;
}
.btn-acao[data-acao=aprovado]:hover {
    box-shadow: 0 0 0 0.2rem 
    rgba(40, 167, 69, 0.5);
    background-color: green;
}

.irregular {
	background-color: #c82333;
    border-color:#bd2130;
}
.irregular:hover {
    box-shadow: 0 0 0 0.2rem #d9534f;
    background-color: darkred;
}

.btn-acao[data-acao=finalizar] {
	background-color: #0069d9;
	border-color: #0062cc;
}
.btn-acao[data-acao=finalizar]:hover {
    box-shadow: 0 0 0 0.2rem lightblue;
    background-color: darkblue;
}
#anterior, #proximo {
	width: % !important;
	display: inline-block;
	background-color: #5a6268;
	border-color: #545b62;
	margin-bottom: 10px;
}

#anterior:hover, #proximo:hover {
	box-shadow: 0 0 0 0.2rem lightgray;
    background-color: #23272b;
}

#select_sinalizadores {
	width: 100%;
	text-align: center;
}
#div_btns {
	width: 100%;
}
#sinalizadores {
	margin-top: 3%;
}
</style>
<div id="acoes">
	<?php
	if ($sinalizadorAtual != 3 && $sinalizadorAtual != 1) { ?>
		<div id="div_btns">
			<button type="button" class="btn-acao" data-acao="aprovado">
				Aprovado
			</button>
			<button type="button" class="btn-acao irregular" data-acao="confirmar-irregularidade">
				Irregularidade
			</button>
			<button type="button" class="btn-acao" data-acao="finalizar">
				Finalizar Conferência
			</button>
		</div>
		<div id="select_sinalizadores" hidden>
			<select id="sinalizadores">
				<option value="" selected>Selecione o Sinalizador</option>
				<?php
				$sqlSinalizadores = "SELECT sinalizador, acao
									 FROM tbl_sinalizador_os
									 WHERE disponivel IS TRUE";
				$resSinalizadores = pg_query($con, $sqlSinalizadores);

				while ($dados = pg_fetch_object($resSinalizadores)) { ?>
					<option value="<?= $dados->sinalizador ?>"><?= $dados->acao ?></option>
				<?php
				} ?>
			</select>
			<br />
			<span style="font-size: 11px;width: 100%;" />Selecione o sinalizador, e clique no botão confirmar irregularidade novamente.</span>
			<div id="div_caixa" hidden>
				Caixa: <input type="number" name="caixa" id="caixa" style="width: 80px;" />
			</div>
		</div>
	<?php
	} else { 

		if ($sinalizadorAtual == 1) {
			$msg = traduz("Os marcada como OK, não é possível alterar.");
		} else {
			$msg = traduz("OS Reincidente, não é possível alterar");
		}

		?>
		<div style="margin: 5px;color: darkgreen;font-weight: bolder;">
			<?= $msg ?>
		</div>
		<button type="button" class="btn-acao" data-acao="finalizar">
			Finalizar Conferência
		</button>
	<?php
	} ?>
	<br />
	<button id="anterior" type="button" os="<?= $os ?>">
		<< Anterior
	</button>
	<button id="proximo" type="button" os="<?= $os ?>">
		Próximo >>
	</button>
	<br />
	<span id="info_pagina"></span>
</div>
<br />
</body>
<script>

$("#sinalizadores").change(function(){

	if ($(this).val() == "9") {

		$("#div_caixa").show();

	} else {

		$("#div_caixa").hide();

	}

});

var osId = '<?= $os ?>';

var pagina = $("#search tbody tr:visible[data-os][data-os="+osId+"]",window.parent.document).index();
pagina = (pagina / 2) + 1;

if (pagina == 1) {
	$("#anterior").attr("disabled", true).css("opacity", 0.5);
}

var totalPaginas = $("#search tbody tr[data-os]:visible",window.parent.document).length;

if (pagina == totalPaginas) {
	$("#proximo").attr("disabled", true).css("opacity", 0.5);
}

$("#info_pagina").html("<br />página <strong>"+pagina+"</strong> de <strong>"+totalPaginas+"</strong><br /><br />");

$("#anterior").click(function(){
	var os = $(this).attr("os");
	var index = $("#search tr[data-os="+os+"]",window.parent.document).index() - 1;

	if (index == 0) {
		alert("Não é possível voltar pois esta é a primeira OS da lista");
	} else {
		var os_anterior = $("#search tbody tr:nth-child("+index+"):visible",window.parent.document).data("os");
		window.location = "?os="+os_anterior;
	}
});


$("#proximo").click(function(){
	var os = $(this).attr("os");
	var index = $("#search tbody tr[data-os="+os+"]",window.parent.document).index() + 3;
	var os_prox = $("#search tbody tr:nth-child("+index+"):visible",window.parent.document).data("os");
	window.location = "?os="+os_prox;
}); 

$(".btn-acao").click(function(){

	let acao = $(this).attr("data-acao");

	if (acao == "confirmar-irregularidade") {

		$(this).text("Confirmar Irregularidade").attr("data-acao", "irregularidade");

		$("#select_sinalizadores").show();

		return;

	} else if (acao == "irregularidade" && $("#sinalizadores").val() == "") {

		alert("Selecione um sinalizador");

		return;

	}

	if (acao != "finalizar") {

		let sinalizador = 1;
		let caixa = "";

		if (acao == "irregularidade") {
			sinalizador = $("#sinalizadores").val();

			if (sinalizador == "9" && $("#caixa").val() == "") {

				alert("Informe a caixa");
				return;

			}

			caixa = $("#caixa").val();

		}

		$.ajax({
			type: "POST",
			url: location.href,
			data: {
				acao: acao,
				os: '<?= $os ?>',
				ajax_acao: true,
				sinalizador: sinalizador,
				caixa: caixa
			},
			complete: function (data){

				let msg = acao == "aprovado" ? "OS Aprovada com sucesso" : "OS marcada como irregular";

				if (data.responseText == "sucesso") {
					alert(msg);
				} else {
					alert(msg);
				}

			}
		});

	} else {

		let contexto = window.parent.document;

		$("input[name=btn_gravar_conferencia]", contexto).click();

	}

});

$('[data-zoom]').on('click', function(){
	if ($(this).data('zoom') == false) {
		aumenta(this);
		$(this).data('zoom', true);
	} else {
		diminui(this);
		$(this).data('zoom', false);
	}
});
function aumenta(obj){
    obj.height=obj.height*2;
	obj.width=obj.width*2;
} 
function diminui(obj){
	obj.height=obj.height/2;
	obj.width=obj.width/2;
}
$('[data-girar]').on('click', function() {
	if ($(this).data('girar') == 'esquerda') {
		$('[data-zoom]').css({ transform : 'rotate(90deg)' })
	}
	if ($(this).data('girar') == 'original') {
		$('[data-zoom]').css({ transform : 'rotate(0deg)' })
	}
	if ($(this).data('girar') == 'direita') {		
		$('[data-zoom]').css({ transform : 'rotate(270deg)' })
	}
	if ($(this).data('girar') == 'baixo') {
		$('[data-zoom]').css({ transform : 'rotate(180deg)' });
	}
});
</script>
