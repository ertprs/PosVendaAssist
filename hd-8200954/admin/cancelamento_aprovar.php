<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include 'funcoes.php';

#$os_os = $os;

$tipo = $_GET["tipo"];
$os   = $_GET["os"];

if( strlen($_POST["os"]) > 0 ){
	$os = $_POST["os"];
}

$tipo        = $_GET["tipo"];
$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

$meses       = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
$layout_menu = "auditoria";
$title       = "APROVAÇÃO DE EXCLUSÃO DE ORDEM DE NÚMERO";

include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>


<script type="text/javascript">
var ok   = false;
var cont = 0;

function checkaTodos()
{
	f = document.frm_pesquisa2;
	if( !ok )
	{
		for( i=0; i<f.length; i++ )
		{
			if( f.elements[i].type == "checkbox" )
			{
				f.elements[i].checked = true;
				ok = true;
				if( document.getElementById('linha_'+cont) )
				{
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
					document.getElementById('linha_aux_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for( i=0; i<f.length; i++ )
		{
			if( f.elements[i].type == "checkbox" )
			{
				f.elements[i].checked = false;
				ok=false;
				if( document.getElementById('linha_'+cont) )
				{
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
					document.getElementById('linha_aux_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox, mudarcor, mudacor2, cor)
{
	if( document.getElementById(theCheckbox) ){
		//document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if( document.getElementById(mudarcor) ){
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
	if( document.getElementById(mudacor2) ){
		document.getElementById(mudacor2).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

</script>

<script type="text/javascript" charset="utf-8">
	$().ready(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));

		Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
	});
	
</script>

<?php

if( $btn_acao == 'Pesquisar' ){
	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
    $os           = trim($_POST['os']);
    $debito       = $_POST['debito'];
    $posto_codigo = $_POST['codigo_posto'];
    $descricao_posto   = $_POST['descricao_posto'];

    if(strlen($descricao_posto)>0 and strlen($codigo_posto)>0){
        $sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
        $res=pg_exec($con,$sql);
        $posto=pg_result($res,0,0);
        if(strlen($posto) >0 ){
            $condPosto = " and tbl_posto_fabrica.posto=$posto ";
        }
    }

	if( strlen($os) > 0 ){
		$Xos = " AND tbl_os.sua_os = '$os' ";
	}

	if( strlen($aprova) == 0 ){
		$aprova = "aprovacao";
		$aprovacao = "245";
	}elseif($aprova=="aprovacao"){
		$aprovacao = "245";
	}elseif($aprova=="aprovadas"){
		$aprovacao = "246";
	}elseif($aprova=="reprovadas"){
		$aprovacao = "247";
	}

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";

		$dat = explode("/", $data_inicial );//tira a barra
		$d   = $dat[0];
		$m   = $dat[1];
		$y   = $dat[2];
		if( !checkdate($m, $d, $y) ) $msg_erro = "Data Inválida";
	}

	if (strlen($data_final) > 0) {

		$dat = explode("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( !checkdate($m, $d, $y) ) $msg_erro = "Data Inválida";

		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

	if ((empty($data_inicial) OR empty($data_final)) and empty($os)) {
		$msg_erro = "É necessário informar o intervalo de datas";
	}

	if(strlen($msg_erro)==0 and !empty($data_inicial) and !empty($data_final)){

		$d_ini             = explode("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim             = explode("/", $data_final);//tira a barra
		$nova_data_final   = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$aux_data_inicial  = $nova_data_inicial;
		$aux_data_final    = $nova_data_final;

		if($nova_data_final < $nova_data_inicial){
			$msg_erro = "Data Inválida.";
		}

		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final   = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final

		if(strlen($msg_erro)==0){
			if (strtotime($aux_data_inicial.'+12 month') <= strtotime($aux_data_final) ) {
		            $msg_erro = 'O intervalo entre as datas não pode ser maior que 12 meses';
			}
		}
	}
}

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	if( strlen($observacao) == 0){
		$msg_erro .= "Informe o motivo.";
	}

	if(strlen($observacao) > 0){
		$observacao = "'".str_replace("'","",$observacao)."'"; 
	}

	if (strlen($qtde_os)==0){
		$qtde_os = 0;
	}

	for ($x=0;$x<$qtde_os;$x++){

		$xxos = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT status_os, tbl_os_status.observacao, tbl_os.posto, tbl_os.sua_os, codigo_posto, tbl_posto_fabrica.contato_email
					FROM tbl_os_status
					JOIN tbl_os ON tbl_os_status.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
					JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE status_os IN (245,246,247)
					AND tbl_os_status.os = $xxos
					ORDER BY data DESC
					LIMIT 1";
			$res_os = pg_exec($con,$sql);

			if (pg_numrows($res_os)>0){
				$status_da_os        = trim(pg_result($res_os,0,'status_os'));
				$status_obs_anterior = trim(pg_result($res_os,0,'observacao'));
				$posto 				 = pg_result($res_os,0,'posto');
				$posto_contato_email = pg_result($res_os,0,'contato_email');
				$codigo_posto		 = pg_result($res_os,0,'codigo_posto');
				$sua_os              = pg_result($res_os,0,'sua_os');
				$num_os_black        = $codigo_posto . $sua_os;

				if( $status_da_os == 245 ){
					//Aprovada
					if( $select_acao == "246" ){
						$auditada = "aprovada";
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,$select_acao,current_timestamp,$observacao,$login_admin)";
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "UPDATE tbl_os SET cancelada = true WHERE os = $xxos and fabrica = $login_fabrica";
						$res = pg_query($con, $sql);
						$msg_erro .= pg_last_error($con);
					}

					//Recusada
					if($select_acao == "247"){
						$auditada = "reprovada";
						$sql = "INSERT INTO tbl_os_status
								(os,status_os,data,observacao,admin)
								VALUES ($xxos,$select_acao,current_timestamp,$observacao,$login_admin)";
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);						
					}
				}

				if (strlen($msg_erro)==0){
					$res = pg_exec($con,"COMMIT TRANSACTION");
					$ok = "O.S $auditada com sucesso. ";
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
					$msg_erro = "Falha ao reprovar O.S";
				}
			}
		}
	}
}


if(strlen($msg_erro) > 0){
	echo "<div align='center' class='alert alert-error' style='margin:auto;'>$msg_erro</div><Br>";
}

if(strlen($ok) > 0){
	echo "<div align='center' class='alert alert-success' style='margin:auto;'>$ok</div><Br>";
}
?>

	<form class='form-search form-inline tc_formulario' name="frm_pesquisa" method="post" action="<?php echo $PHP_SELF; ?>">

		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='numero_os'>Número da OS</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $os ?>" class="frm">
							</div>
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="span12">
							</div>
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
	                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $posto_codigo ?>" >
	                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span4'>
	            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='descricao_posto'>Nome Posto</label>
	                <div class='controls controls-row'>
	                    <div class='span12 input-append'>
	                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
	                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				 <label class="radio">
			        <input type="radio" name="aprova" value='aprovacao' <?php if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Em aprovação
			    </label>
			</div>
			<div class='span2'>
			    <label class="radio">
					<input type="radio" name="aprova" value='reprovadas' <?php if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas
			    </label>
			</div>
			<div class='span2'>
				 <label class="radio">
			        <input type="radio" name="aprova" value='aprovadas' <?php if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas
			    </label>
			</div>				
		</div>            
	    <div class='row-fluid'>
	    	<div class='span12'>
				<input type='hidden' name='btn_acao' value=''>
				<center><input type="submit" class="btn" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" value="Pesquisar" /></center>
				<br /><br />
			</div>
		</div>
	</form>
</div>
<?php

if( strlen($msg_erro) == 0 ){ // executa a query

	if( $btn_acao == 'Pesquisar' ){
		$sqlx =  "SELECT interv.os, interv.data
				INTO TEMP tmp_interv_$login_admin
				FROM (
				SELECT
				ultima.os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (245,246,247) AND tbl_os_status.os = ultima.os $automatico ORDER BY os_status DESC LIMIT 1) AS ultimo_status,
				(SELECT data FROM tbl_os_status WHERE tbl_os_status.fabrica_status = 3 AND status_os IN (245,246,247) AND tbl_os_status.os = ultima.os ORDER BY os_status DESC LIMIT 1) AS data
				FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (245,246,247) $automatico ) ultima
				) interv
				WHERE interv.ultimo_status IN ($aprovacao);

				CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os); ";
		$res = pg_exec($con, $sqlx);

		if( $aprova=="aprovadas"){
			$condAprovadas = " AND tbl_os.cancelada is true ";
		}

		$sqly = " SELECT tbl_os.os,
				tbl_os.sua_os                                               ,
				tbl_admin.login                                AS admin_nome,
				tbl_os.consumidor_nome                                      ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
				tbl_os.fabrica                                              ,
				tbl_os.consumidor_nome                                      ,
				tbl_os.nota_fiscal_saida                                    ,
				tbl_os.serie                       AS produto_serie         ,
				to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
				tbl_posto.nome                     AS posto_nome            ,
				tbl_posto_fabrica.codigo_posto                              ,
				tbl_posto_fabrica.contato_estado                            ,
				tbl_produto.referencia             AS produto_referencia    ,
				tbl_produto.descricao              AS produto_descricao     ,
				tbl_produto.voltagem                                        ,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245,246,247) ORDER BY data DESC LIMIT 1) AS status_os         ,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245,246,247) ORDER BY data DESC LIMIT 1) AS status_observacao,
				(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245,246,247) ORDER BY data DESC LIMIT 1) AS status_descricao,
				(SELECT tbl_admin.login FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245,246,247) ORDER BY data DESC LIMIT 1) AS admin_aprovou_reprovou,
				(SELECT TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245,246,247) ORDER BY data DESC LIMIT 1) AS status_data,
				(SELECT tbl_os_status.data FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN (245) ORDER BY data DESC LIMIT 1) AS status_data2
                $sql_extrato
			FROM tmp_interv_$login_admin X
			JOIN tbl_os ON tbl_os.os = X.os
			JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
			JOIN tbl_posto_fabrica        ON tbl_posto.posto     = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os.admin

			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE /* hd 52463 */
			$condAprovadas
			$Xos
			$condPosto
			";
		if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
			$sqly .= " AND X.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
		}
			$sqly .= " ORDER BY status_data2 DESC ";
	
		$res = pg_exec($con, $sqly);

		if( pg_numrows($res)>0 ){

			echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

			echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
			echo "<input type='hidden' name='data_final'     value='$data_final'>";
			echo "<input type='hidden' name='aprova'         value='$aprova'>";

			echo "<table class='table table-striped table-bordered table-large' id='tabela_exclusao'>";
			echo "<thead><tr class=\"titulo_coluna\">";
			if($aprovacao == 245){			
				echo "<th><a style='cursor: hand;color: white;' href='#' onclick='javascript: checkaTodos()'>Todas</a></th>";
			}

			echo "<th>OS</th>";
			echo "<th>Data Digitação</th>";
			echo "<th>Data Abertura</th>";
			echo "<th>Posto</th>";
			echo "<th>Produto</th>";
			echo "<th>Descrição</th>";
			if($aprovacao != 245){
				echo "<th>Responsável<br>Solicitação</th>";
				echo "<th>Responsável<br>Auditoria</th>";
			}else{
				echo "<th>Responsável<br>Solicitação</th>";
				echo "<th>Data<br>Solicitação</th>";
			}
			echo "</tr></thead>";

			$cores            = '';
			$qtde_intervencao = 0;

			for( $x=0; $x<pg_numrows($res); $x++ ){
				$os						= pg_result($res, $x, os);
				$sua_os					= pg_result($res, $x, sua_os);
				$codigo_posto			= pg_result($res, $x, codigo_posto);
				$posto_nome				= pg_result($res, $x, posto_nome);
				$consumidor_nome		= pg_result($res, $x, consumidor_nome);
				$produto_referencia		= pg_result($res, $x, produto_referencia);
				$produto_descricao		= pg_result($res, $x, produto_descricao);
				$produto_serie			= pg_result($res, $x, produto_serie);
				$produto_voltagem		= pg_result($res, $x, voltagem);
				$data_digitacao			= pg_result($res, $x, data_digitacao);
                $data_abertura          = pg_result($res, $x, data_abertura);
                $os_extrato             = pg_result($res, $x, extrato);
				$data_extrato			= pg_result($res, $x, data_extrato);
				$status_os				= pg_result($res, $x, status_os);
				$status_observacao		= pg_result($res, $x, status_observacao);
				$status_descricao		= pg_result($res, $x, status_descricao);
				$admin_aprovou_reprovou = pg_result($res, $x, admin_aprovou_reprovou);
				$status_data			= pg_result($res, $x, status_data);
				$admin_nome				= pg_result($res, $x, admin_nome);

				$cores++;
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';

				echo "<tr bgcolor='$cor' id='linha_$x'>";
				

				if( $status_os == 245 )
				{
					echo "<td align='center' width='0'>";
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','linha_aux_$x','$cor');\" ";
					if( strlen($msg_erro)>0 ){
						if( strlen($_POST["check_".$x])>0 ){
							echo " CHECKED ";
						}
					}
					echo ">";
					echo "</td>";
				}

				
				echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
					if($aprova=="aprovadas"){
						echo "$sua_os";
					}else{
						echo "<input type='hidden' name='sua_os_{$x}' value='$sua_os'>";
						echo "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>";
					}
				echo "</td>";
				echo "<td>".$data_digitacao. "</td>";
				echo "<td>".$data_abertura. "</td>";
				if(in_array("extrato",$debito)){
                    echo "<td>".$data_extrato. "</td>";
                    echo "<td>".$os_extrato. "</td>";
				}
				echo "<td align='left' nowrap title='".$codigo_posto." - ".$posto_nome."'>".$codigo_posto." - ".substr($posto_nome,0,20) ."...</td>";
				echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
				echo "<td align='left' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_descricao ."</acronym></td>";
                if($login_fabrica == 1){
					echo "<td align='rigth'>".number_format($total_debito,2,',','.')."</td>";
				}

				$sql_admin = "  SELECT  tbl_admin.admin,
                                        tbl_admin.login
                                FROM    tbl_os_status
                                JOIN    tbl_admin USING(admin)
                                WHERE   os          = $os
                                AND     status_os   = 245";
				$res_admin = pg_exec($con,$sql_admin);

				if(pg_numrows($res_admin)>0){
					$admin_solicitou = pg_fetch_result($res_admin, 0, 'login');
					$data_exclusao = pg_fetch_result($res_admin, 0, 'data_exclusao');
					if(strlen($admin_solicitou) > 0){
						echo "<td align='left' nowrap>".ucfirst($admin_solicitou)."</td>";
					}
				}

				if( $aprovacao != 245 ){
					echo "<td nowrap>";
						echo ucfirst($admin_aprovou_reprovou);
					echo "</td>";				
				}else{					
					echo "<td>";
                    echo "$status_data";
                    echo "&nbsp;</td>";
				}

				echo "</tr>";
				echo "<tr bgcolor='$cor' id='linha_aux_$x'>";
				echo "<td>";
				echo "</td>";

				if(in_array("extrato",$debito)){
                    $colspan = 10;
                }else{
                    $colspan = 9;
                }
				if( $aprovacao != 245 and $login_fabrica == 1 )
					$colspan+= 2;

				echo "<td align='left' colspan='$colspan'><acronym title='Data da solicitação da exclusão: ".$status_data."'><b>Obs: </b>". nl2br($status_observacao) . "</acronym></td>";
				echo "</tr>";
			}

			echo "<input type='hidden' name='qtde_os' value='$x'>";
			echo "<tr>"; $colspan+= 1;
			echo "<td class='subtitulo' colspan='$colspan' class='subtitulo' style='text-align:left;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

			if( trim($aprova) == 'aprovacao' )
			{
				echo "<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'>";
				echo "&nbsp; Com Marcados:&nbsp;<select name='select_acao' size='1' class='frm' >";
				echo "<option value=''></option>";
				echo "<option value='246'";  if ($_POST["select_acao"] == "246")  echo " selected"; echo ">APROVAR E CANCELAR</option>";
				echo "<option value='247'";  if ($_POST["select_acao"] == "247")  echo " selected"; echo ">REPROVAR CANCELAMENTO</option>";
				echo "</select>";
				echo "&nbsp;&nbsp; Motivo:<input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='button' class='btn btn-primary' value='Gravar' id='btn_gravar' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'>";
			}else
				echo "</td>";
			echo "<input type='hidden' name='btn_acao' value='auditar'>";
			#echo "<input type='hidden' name='btn_acao' value='$os_os'>";
			echo "</table>";
			echo "</form>";
		}else{
			echo "<div class='container alert alert-warning'><h4>Não foram encontrados resultados para esta pesquisa</h4></div>";
		}
		$msg_erro = '';
	}
}//fim do else (validaçao das datas)
?>
<?php
include "rodape.php";
?>
