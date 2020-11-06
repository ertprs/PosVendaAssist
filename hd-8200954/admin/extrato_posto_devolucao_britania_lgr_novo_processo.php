<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include "../anexaNFDevolucao_inc.php";
include_once '../class/tdocs.class.php';


$msg = "";

$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao) > 0) {
	$extrato = $_POST['extrato'];
	$posto = $_POST['posto'];
	$posto = $_GET['posto'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$extrato_devolucao = pg_result ($res,$i,extrato_devolucao);

		$nota_fiscal = trim($_POST['nota_fiscal_' . $extrato_devolucao]);
		$total_nota  = trim($_POST['total_nota_'  . $extrato_devolucao]);
		$base_icms   = trim($_POST['base_icms_'   . $extrato_devolucao]);
		$valor_icms  = trim($_POST['valor_icms_'  . $extrato_devolucao]);

		if (strlen($nota_fiscal) == 0) {
			$msg = " Favor informar o número de todas as Notas de Devolução.";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}

		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(",","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);

		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

		if (strlen ($msg) == 0) {
			$sql =	"UPDATE tbl_extrato_devolucao SET
					nota_fiscal             = '$nota_fiscal'      ,
					total_nota              = $total_nota         ,
					base_icms               = $base_icms          ,
					valor_icms              = $valor_icms
				WHERE extrato_devolucao = $extrato_devolucao";
#			echo nl2br($sql);
			$resX = @pg_exec ($con,$sql);
			$msg = pg_errormessage($con);
		}
	}

	if (strlen($msg) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?extrato=$extrato");
		exit;
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($_POST['ajax_remove_anexo'] == true) {

	$tdocsID = $_POST['tdocs_id'];

	if (!empty($tdocsID)) {
		$xtDocs = new TDocs($con, $login_fabrica);
		$xtDocs->setContext("comprovantelgr");
	   	$removeu = $xtDocs->removeDocumentById($tdocsID);

	   	if ($removeu) {
			exit("OK|true|msg|Anexo removido com sucesso");
	   	} else {
			exit("erro|true|msg|Erro ao remover o anexo");
	   	}

	} else {
		exit("erro|true|msg|Anexo não encontrado");
	}

}

if (filter_input(INPUT_POST,'remove_nota',FILTER_VALIDATE_BOOLEAN)) {
    $tdocsID            = $_POST['tdocs_id'];
    $faturamento  = $_POST['faturamento'];

    pg_query($con,"BEGIN TRANSACTION");
    $sql = "UPDATE tbl_faturamento SET nota_fiscal = NULL WHERE faturamento = $faturamento";
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");

    $xtDocs = new TDocs($con, $login_fabrica);
    $xtDocs->setContext("lgr");
    $removeu = $xtDocs->removeDocumentById($tdocsID);

    if ($removeu) {
        $retorno = array("ok" => true, "msg" => "Nota removida com sucesso");
    }

    echo json_encode($retorno);
    exit;
}

$sql = "SELECT posto
		FROM tbl_extrato
		WHERE extrato = $extrato
		AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res)==0){
	$msg_erro .= "Nenhum posto encontrado para este extrato!";
}else{
	$posto = pg_result ($res,0,posto);
}

$login_posto = $posto;

$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

include "cabecalho.php";
?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/FancyZoom/FancyZoom.js"></script>
<script src="plugins/FancyZoom/FancyZoomHTML.js"></script>
<script type="text/javascript">
$(function(){

	$(".btn-remove-anexo").click(function() {

		if (confirm('Deseja remover este anexo?')) {

			var tdocs_id = $(this).data("tdocs");

			$.ajax({
				url: "<?=$PHP_SELF?>?>",
				cache: false,
				type: "POST",
				data: {
					ajax_remove_anexo : true,
					tdocs_id : tdocs_id
				},
				success: function(data){
					retorno = data.split('|');

					if (retorno[0]=="OK") {
						alert(retorno[3]);
						location.reload();
					} else{
						alert(retorno[3]);
					}
				}
			});
		}
	});

    $(".btn_remove_nota").click(function(){
        if (confirm("Deseja remover a nota de devolução?")) {
            var tdocs_id    = $(this).data("tdocs");
            var faturamento = $(this).data("faturamento");

            $.ajax({
                url:"<?=$PHP_SELF?>",
                type:"POST",
                dataType:"JSON",
                data: {
                    remove_nota:true,
                    tdocs_id:tdocs_id,
                    faturamento:faturamento
                }
            })
            .done(function(data){
                if (data.ok) {
                    alert(data.msg);
                    location.reload();
                }
            })
            .fail(function(){
                alert("Erro ao remover Nota.");
            });
        }
    });
});

</script>
<style>
	.formulario {
		background-color:#D9E2EF;
		font:12px Arial;
		text-align:left;
	}
	.formulario td p{font-size:12px;}
	.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	}
	.btn-remove-anexo{
		background: #ff0000;
		color: #ffffff;
		border: solid 1px #000000;
		cursor: pointer;
		padding: 4px;
		display: block;
	}
	.btn-remove-anexo:hover{
		background: #d90000;
		color: #ffffff;
		border: solid 1px #000000;
		cursor: pointer;
	}
</style>
<center>
<?
	echo "<table width='550' align='center'>";
	echo "<tr><td>";
	echo "<b>Conforme determina a legislação local</b><p>";

	echo "Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.";
	echo "<br>";
	echo "O valor da mão-de-obra será exibido somente após confirmação da Nota Fiscal de Devolução.";
	echo "<br>";
	echo "TODAS as peças de Áudio e Vídeo devem retornar junto com esta Nota fiscal.";
	echo "<br>";
	echo "As peças das linhas de eletroportáteis e branca devem ficar no posto por 90 dias para inspeção ou de acordo com os procedimentos definidos por seu DISTRIBUIDOR.";
	echo "<br>";

	echo "</td></tr></table>";

?>


<? if (strlen($msg) > 0) {
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
} ?>


<?

if (strlen ($extrato) == 0) $extrato = trim($_GET['extrato']);
if (strlen ($somente_consulta) == 0) $somente_consulta = trim($_GET['somente_consulta']);

//============================

$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
$resY = pg_exec ($con,$sql);
$qtde = pg_result ($resY,0,0);
if ($qtde > 0) {
	$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NOT NULL";
	$resY = pg_exec ($con,$sql);
	$qtde = pg_result ($resY,0,0);
	if ($qtde > 0) {
		$sql = "DELETE FROM tbl_extrato_devolucao WHERE extrato = $extrato";
		$resY = pg_exec ($con,$sql);
	}
}



$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='+0' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?
if(strlen($somente_consulta)> 0){
	echo "<td align='center' width='33%'><a href='extrato_posto_mao_obra_novo_britania.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta'>Ver Mão-de-Obra</a></td>";
}else{
	echo "<td align='center' width='33%'><a href='extrato_posto_mao_obra_novo_britania.php?extrato=$extrato&posto=$posto'>Ver Mão-de-Obra</a></td>";
}


?>
<td align='center' width='33%'><a href='extrato_posto_britania_novo_processo.php?somente_consulta=sim'>Ver outro extrato</a></td>
</tr>
</table>


<p>

<?

	$array_nf_canceladas = array();
	$sql="SELECT	trim(nota_fiscal) as nota_fiscal,
					to_char(data_nf,'DD/MM/YYYY') as data_nf
			FROM tbl_lgr_cancelado
			WHERE	fabrica = $login_fabrica
			AND     posto   = $login_posto
			AND foi_cancelado IS TRUE";
	$res_nf_canceladas = pg_exec ($con,$sql);
	$qtde_notas_canceladas = pg_numrows ($res_nf_canceladas);
	if ($qtde_notas_canceladas>0){
		for($i=0;$i<$qtde_notas_canceladas;$i++) {
			$nf_cancelada = pg_result ($res_nf_canceladas,$i,nota_fiscal);
			$data_nf      = pg_result ($res_nf_canceladas,$i,data_nf);

			$sql2="SELECT faturamento
					FROM tbl_faturamento
					WHERE fabrica             = $login_fabrica
					AND distribuidor           = $login_posto
					AND extrato_devolucao      = $extrato
					AND posto                  = 13996
					AND LPAD(nota_fiscal::text,7,'0')  = LPAD(trim('$nf_cancelada')::text,7,'0')
					AND cancelada IS NOT NULL";
			$res_nota = pg_exec ($con,$sql2);
			$notasss = pg_numrows ($res_nota);
			if ($notasss>0){
				array_push($array_nf_canceladas,$nf_cancelada);
			}else{
				if ($extrato==156369){
					if ($nf_cancelada=="0027373" OR $nf_cancelada=="0027374"){
						continue;
					}
				}
				if ($extrato==165591){
					if ($nf_cancelada=="0027155"){
						continue;
					}
				}
				if ($login_posto==595 AND ($extrato == 165591 OR $extrato==156369)){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
				if ($login_posto==13951 AND $extrato==147564){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
				if ($login_posto==1537 AND $extrato==156705){
					array_push($array_nf_canceladas,"$nf_cancelada");
				}
			}
		}
	}
	if (count($array_nf_canceladas)>0){
		if (count($array_nf_canceladas)>1){
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>As notas:</b><br>".implode(",<br>",$array_nf_canceladas)." <br>foram <b>canceladas</b> e deverão ser preenchidas novamente! <br></h3>";
		}else{
			echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>A nota</b> ".implode(", ",$array_nf_canceladas)." foi <b>cancelada</b> e deverá ser preenchida novamente! <br></h3>";
		}
	}

?>
<script>
$(document).ready(function(){

	$(".btn-remove-anexo").click(function(){

		if (confirm('Deseja remover este anexo?')) {

			var tdocs_id = $(this).data("tdocs");

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				cache: false,
				type: "POST",
				data: {
					ajax_remove_anexo : true,
					tdocs_id : tdocs_id
				},
				success: function(data){
					retorno = data.split('|');

					if (retorno[0]=="OK") {
						alert(retorno[3]);
						location.reload();
					} else{
						alert(retorno[3]);
					}
				}
			});
		}
	});
});

</script>
<style>
	.btn-remove-anexo{
		background: #ff0000;
		color: #ffffff;
		border: solid 1px #000000;
		cursor: pointer;
		padding: 4px;
		display: block;
	}
	.btn-remove-anexo:hover{
		background: #d90000;
		color: #ffffff;
		border: solid 1px #000000;
		cursor: pointer;
	}
</style>
<form name='frm_nota_fiscal' method='POST' action='<? echo $PHP_SELF ?>?'>
<input type='hidden' name='btn_acao' value='cancelar_notas'>
<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
<input type='hidden' name='posto' value='<? echo $posto; ?>'>
<?

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor,
		posto
	FROM tbl_faturamento
	WHERE posto in (13996,4311)
	AND distribuidor      = $login_posto
	AND fabrica           = $login_fabrica
	AND extrato_devolucao = $extrato
	AND cancelada IS NULL
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$qtde_for=pg_numrows ($res);

if ($qtde_for > 0 OR 1==1) {
	if ($login_fabrica == 3) {

        $tDocs = new TDocs($con, $login_fabrica);
	    $temAnexo = $tDocs->getDocumentsByRef($extrato,'comprovantelgr')->attachListInfo;

	    if (count($temAnexo) > 0) {

	    	foreach ($temAnexo as $key => $anexo) {
		    	echo '<div style="display:inline-block;width:140px">
		    	<a href="'.$anexo['link'].'" target="_blank" >
					<img src="'.$anexo['link'].'" class="anexo_thumb" style="width: 110px; height: 90px;margin:10px;" />
		    	</a>

				<button type="button" class="btn-remove-anexo" data-tdocs="'.$anexo['tdocs_id'].'"> Remover Anexo</button></div>';
	    	}

	    	echo '<script>setupZoom();</script>';
	    }
	}

	$contador=0;
	for ($i=0; $i < $qtde_for; $i++) {

		$contador++;
		$faturamento_nota    = trim (pg_result ($res,$i,faturamento));
		$distribuidor        = trim (pg_result ($res,$i,distribuidor));
		$posto               = trim (pg_result ($res,$i,posto));
		$nota_fiscal         = trim (pg_result ($res,$i,nota_fiscal));
		$extrato_devolucao	 = trim (pg_result ($res,$i,extrato_devolucao));
		$distribuidor        = "";
		$produto_acabado     = "";

		$sql_topo = "SELECT
					CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
					tbl_peca.devolucao_obrigatoria
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = $posto
				AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND tbl_faturamento.extrato_devolucao = $extrato_devolucao
				AND tbl_faturamento.faturamento       = $faturamento_nota
				LIMIT 1";
		$res_topo = pg_exec ($con,$sql_topo);
		$produto_acabado = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria = pg_result ($res_topo,0,devolucao_obrigatoria);

		$pecas_produtos = "PEÇAS";
		$devolucao = " RETORNO OBRIGATÓRIO ";

		if ($posto=='4311'){
			$posto_desc = "Devolução para a TELECONTROL - ";
		}else{
			$posto_desc="";
		}

		if ($devolucao_obrigatoria=='f') $devolucao = " NÃO RETORNÁVEIS ";
		if ($devolucao_obrigatoria=='f') $pecas_produtos = "$posto_desc PEÇAS";

		if ($produto_acabado == "TRUE"){
			$pecas_produtos = "$posto_desc PRODUTOS";
			 $devolucao = " RETORNO OBRIGATÓRIO ";
		}

		if ($posto=='13996'){ #BRITANIA

			$sql_data_geracao_extrato = "SELECT data_geracao::date FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
			$res_data_geracao_extrato = pg_query($con, $sql_data_geracao_extrato);

			$data_geracao_extrato = pg_fetch_result($res_data_geracao_extrato, 0, "data_geracao");

			if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

				$razao    = "BRITANIA ELETRONICOS SA";
				$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239-270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "07019308000128";
				$ie       = "254.861.660";

			}else{

				$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
				$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
				$cidade   = "Joinville";
				$estado   = "SC";
				$cep      = "89239270";
				$fone     = "(41) 2102-7700";
				$cnpj     = "76492701000742";
				$ie       = "254.861.652";

			}

		}
		if ($posto=='4311'){ #TELECONTROL
				$razao    = "TELECONTROL NETWORKING LTDA";
				$endereco = "AV. CARLOS ARTENCIO 420 ";
				$cidade   = "Marília";
				$estado   = "SP";
				$cep      = "17.519-255 ";
				$fone     = "(14) 3433-6588";
				$cnpj     = "04716427000141 ";
				$ie       = "438.200.748-116";
		}

		$cabecalho  = "";
		$cabecalho  = "<br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

		$cabecalho .= "<tr align='left'  height='16'>\n";
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
		$cabecalho .= "<td>Emissao <br> <b>$data</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
		$topo .=  "<thead>\n";
		if ($numero_linhas==5000 AND  $jah_digitado==0){
//			$topo .=  "<tr align='left'>\n";
//			$topo .=  "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
//			$topo .=  "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
//			$topo .=  "</td>\n";
//			$topo .=  "</tr>\n";
		}
		$topo .=  "<tr align='center'>\n";
		$topo .=  "<td><b>Código</b></td>\n";
		$topo .=  "<td><b>Descrição</b></td>\n";
		$topo .=  "<td><b>Qtde.</b></td>\n";

			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";

		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";

		$sql = "SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_peca.devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				SUM (tbl_faturamento_item.qtde) as qtde,
				SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
				SUM (tbl_faturamento_item.base_icms) AS base_icms,
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING (faturamento)
				JOIN tbl_peca             USING (peca)
				WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   tbl_faturamento.faturamento=$faturamento_nota
					AND   tbl_faturamento.posto=$posto
					AND   tbl_faturamento.distribuidor=$login_posto
				GROUP BY
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.devolucao_obrigatoria,
					tbl_peca.produto_acabado,
					tbl_peca.ipi,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco
				ORDER BY tbl_peca.referencia";

		$resX = pg_exec ($con,$sql);

		$notas_fiscais=array();
		$qtde_peca=0;

		if (pg_numrows ($resX)==0) continue;

		echo $cabecalho;
		echo $topo;

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$ipi                 = pg_result ($resX,$x,ipi);
			$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria = pg_result ($resX,$x,devolucao_obrigatoria);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$peca_preco          = pg_result ($resX,$x,preco);

			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);

			$total               = pg_result ($resX,$x,total);
			$qtde                = pg_result ($resX,$x,qtde);


			if ($qtde==0) {
				$peca_preco       =  $peca_preco;
			} else {
				$peca_preco       =  $total / $qtde;
			}

			$total_item  = $peca_preco * $qtde;

//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0) {
				$base_icms=0;
				$valor_icms=0;
			} else {
				$base_icms  = $total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if (strlen($aliq_ipi)==0) $aliq_ipi=0;

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			} else {
				$base_ipi=$total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>";
			echo "$peca_referencia";
			echo "</td>\n";
			echo "<td align='left'>$peca_descricao</td>\n";

			echo "<td align='center'>$qtde</td>\n";
			echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
			echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
			echo "<td align='right'>$aliq_icms</td>\n";
			echo "<td align='right'>$aliq_ipi</td>\n";

			echo "</tr>\n";
			flush();
		}

		$sql_nf = " SELECT tbl_faturamento_item.nota_fiscal_origem
					FROM tbl_faturamento_item
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica           = $login_fabrica
					AND   tbl_faturamento.distribuidor      = $login_posto
					AND   tbl_faturamento.posto             = $posto
					AND   tbl_faturamento.extrato_devolucao = $extrato
					ORDER BY tbl_faturamento.nota_fiscal";
		$resNF = pg_exec ($con,$sql_nf);
		for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
			array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal_origem));
		}
		$notas_fiscais = array_unique($notas_fiscais);
		#asort($notas_fiscais);

		if (count($notas_fiscais)>0){
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
			echo "</tr>";
			echo "</tfoot>";
		}

		echo "</table>\n";

        $tdocsNota = new TDocs($con,$login_fabrica);
        $tdocsNota->setContext('lgr');
        $temNota = $tdocsNota->getDocumentsByRef($faturamento_nota)->hasAttachment;
        $valor = $tdocsNota->getDocumentsByRef($faturamento_nota)->attachListInfo;
        foreach ($valor as $nota) {
            $nota_id = $nota['tdocs_id'];
        }
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
		echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
		echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
		echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
		echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		echo "<tr>\n";
		echo "<td><h1><center>";
		echo ($temNota) ? "<a href='".$tdocsNota->url."' target='_blank'>" : "";
		echo (empty($nota_fiscal)) ? "Sem Nota cadastrada" : "Nota de Devolução $nota_fiscal";
		echo ($temNota) ? "</a>" : "";
		echo "</center></h1></td>\n";
        echo ($temNota) ? "<td><center><button type='button' class='btn_remove_nota' data-tdocs='".$nota_id."' data-faturamento='".$faturamento_nota."'> Remover Nota Fiscal</button></center></td>" : "";
		echo "</tr>";
		echo "</table>";

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;

	}

########################################################
### PEÇAS COM RESSARCIMENTO
########################################################


	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(os_produto)
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra.extrato = $extrato
		AND  tbl_os.fabrica        = $login_fabrica
		AND  tbl_os.posto          = $login_posto
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	$sql = "SELECT
				tbl_os.os                                                         ,
				tbl_os.sua_os                                                     ,
				TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
				tbl_produto.referencia                       AS produto_referencia,
				tbl_produto.descricao                        AS produto_descricao ,
				tbl_admin.login
			FROM ( SELECT os FROM tbl_os_extra WHERE extrato = $extrato ) x
			JOIN  tbl_os             ON x.os           = tbl_os.os
			JOIN tbl_os_produto      ON tbl_os.os      = tbl_os_produto.os
			LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
			LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica        = $login_fabrica
			AND   tbl_os.posto          = $login_posto
			AND   tbl_os.ressarcimento  IS TRUE
			AND   tbl_os.troca_garantia IS TRUE
			";

	if (strlen($nota_fiscal)>0){

		$resX = pg_exec ($con,$sql);

		if(pg_numrows($resX)>0){

			echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

			echo "<tr align='left'  height='16'>\n";
			echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			echo "<b>&nbsp;<b>PEÇAS COM RESSARCIMENTO - DEVOLUÇÃO OBRIGATÓRIA </b><br>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>";
			echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>Emissao <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";


			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Endereço <br> <b>$endereco </b> </td>";
			echo "<td>Cidade <br> <b>$cidade</b> </td>";
			echo "<td>Estado <br> <b>$estado</b> </td>";
			echo "<td>CEP <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr align='center'>";
			echo "<td><b>Código</b></td>";
			echo "<td><b>Descrição</b></td>";
			echo "<td><b>Ressarcimento</b></td>";
			echo "<td><b>Responsavel</b></td>";
			echo "<td><b>OS</b></td>";
			echo "</tr>";

			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

				$sua_os             = pg_result ($resX,$x,sua_os);
				$produto_referencia = pg_result ($resX,$x,produto_referencia);
				$produto_descricao  = pg_result ($resX,$x,produto_descricao);
				$data_ressarcimento = pg_result ($resX,$x,data_ressarcimento);
				$quem_trocou        = pg_result ($resX,$x,login);

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<td align='left'>$produto_referencia</td>";
				echo "<td align='left'>$produto_descricao</td>";
				echo "<td align='left'>$data_ressarcimento</td>";
				echo "<td align='right'>$quem_trocou</td>";
				echo "<td align='right'>$sua_os</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	echo "</form>";
}else{

	echo "<h1>Posto autorizado ainda não preencheu as notas de devolução.<br>Para consultar as notas, logue como Este Posto e acesse seu extrato.</h1>";
	//$res = pg_exec ($con,$sql);

}
?>
<p><p>

<? include "rodape.php"; ?>
