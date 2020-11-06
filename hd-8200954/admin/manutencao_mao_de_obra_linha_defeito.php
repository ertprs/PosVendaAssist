<?php

/*

página criada como eXtenção para inserir a mão de obra em uma "tabela".

refatoração: bruno kawakami 22/08/2012

os GanHos estão na organização do sistema e posterior edição para outras fábricas.

sabendo que É bem mais viável programar NA orientação a objeto.

importante: por favor ao próximo programador que pegar esta tela para trabalhar, não utilize "ifs" desordenadamente. crie um metodo
especifique a fabrica e depois chame na main()

bruno kawakami, TELECONTROL.

*/

class pagGenerate{

	private $msg_erro;
	private $msg_sucesso;

	##################################### jquerySource
	private function jquerySource(){

		$jquerySource = '
			<script language="JavaScript" src="js/jquery-1.6.1.min.js"></script>
			<script type="text/javascript" src="js/jquery-ui-1.8rc3.custom.js"></script>
			<script type="text/javascript" src="js/ui.dropdownchecklist-1.4-min.js"></script>
			<script type="text/javascript" src="js/jquery.price_format.js"></script>

			<script type="text/javascript">

				jQuery.fn.toggleText = function (value1, value2) {
				    return this.each(function () {
				        var $this = $(this),
				            text = $this.text();

				        if (text.indexOf(value1) > -1)
				            $this.text(text.replace(value1, value2));
				        else
				            $this.text(text.replace(value2, value1));
				    });
				};
				$(document).ready(function() {

					$(\'#selecionarTodos\').click(function() {
				        if(this.checked == true){
				            $("input[id=id_tabela_mao_obra]").each(function() {
				                this.checked = true;
				            });
				        } else {
				            $("input[id=id_tabela_mao_obra]").each(function() {
				                this.checked = false;
				            });
				        }
			    	});

                    $("#defeito_constatado_grupo").change(function() {
                            var grupo = $(this).val();

                            if(grupo.length > 0){

                                    $.post("service_defeito_constatado.php", { tipo: "ajax", grupo: grupo},
                                       function(data) {
                                         $("#defeito_constatado").dropdownchecklist("destroy");
                                         $("#defeito_constatado").html(data);
                                         $("#defeito_constatado").dropdownchecklist({emptyText: "Selecione um defeito",width: 202});

                               });

                            }
                    });

                    $("#defeito_constatado").focus(function() {
                            var grupo = $("#defeito_constatado_grupo").val();

                            if(grupo.length > 0){
                                    $.post("service_defeito_constatado.php", { tipo: "ajax", grupo: grupo},
                                       function(data) {
                                         $("#defeito_constatado").dropdownchecklist("destroy");
                                         $("#defeito_constatado").html(data);
                                         $("#defeito_constatado").dropdownchecklist({emptyText: "Selecione um defeito",width: 202});
                               });
                            }
                    });

                    $(".money").priceFormat({
                            prefix: "",
                            centsSeparator: ",",
                            thousandsSeparator: "."
                    });

                    $("#tabela").dropdownchecklist({emptyText: "Selecione uma tabela"});
                    $("#familia").dropdownchecklist({emptyText: "Selecione uma familia"});
                    $("#defeito_constatado").dropdownchecklist({emptyText: "Selecione um defeito",width: 202});

                    $("ul.ul-0>li").click(function() {
                        var tabelaServico = $(this).html();

                        if(tabelaServico[0] == "-"){
                        	$(this).html("+  "+tabelaServico.substring(1));

                        }else{
                        	$(this).html("-  "+tabelaServico.substring(1));

                        }
                        $(this).parent().find("ul.ul-1").slideToggle("slow");
                    });

                    $("ul.ul-1>li").click(function() {
                        var familiaConsulta = $(this).html();

                        if(familiaConsulta[0] == "-"){
                        	$(this).html("+  "+familiaConsulta.substring(1));

                        }else{
                        	$(this).html("-  "+familiaConsulta.substring(1));

                        }

                        $(this).parent().find("ul.ul-2").slideToggle("slowf");

                    });

					$(".ul-2 li div input").click(function(){
						var li =  $(this).parents("tr").first();
						var valorMaoDeObra = li.find("[name=valorMaoDeObra]").val();
						var valorMaoDeObraPure = li.find("[name=valorMaoDeObra]");
						var diagnosticoId = li.find("[name=diagnosticoId]").val();
						var botaoGravar = li.find("[value=Gravar]");
						var consultaValue = $(this).val();
						var consultaValueThis = $(this);


						$.post("service_salva_defeito_constatado.php", { valorMaoDeObra: valorMaoDeObra, diagnosticoId: diagnosticoId, consultaValue:consultaValue},
						function(data) {
							if(data == 1){
								alert("Inativado com sucesso");
								li.css("background-color","red");
								consultaValueThis.val("Ativar");
								botaoGravar.css("display","none");
								valorMaoDeObraPure.attr("disabled",true);

							}
							if(data == 4){
								alert("Ativado com sucesso");
								li.css("background-color","#F9F9F9");
								consultaValueThis.val("Inativar");
								botaoGravar.css("display","inline");
								valorMaoDeObraPure.removeAttr("disabled");
							}
							if(data == 3){
								alert("Gravado com sucesso");
							}

						});

					});

				});
			</script>';
		return $jquerySource;

	}

	##################################### styleSource
	private function styleSource(){
		$style = '
		<link rel="stylesheet" type="text/css" href="js/jquery-ui-1.8rc3.custom.css">
		<style type="text/css">
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
		    text-align:left;
		}

		table.tabela tr td{
		    font-family: verdana;
		    font-size: 11px;
		    border-collapse: collapse;
		    border:1px solid #596d9b;
		}

		.sucesso{
		    background-color:#008000;
		    font: bold 14px "Arial";
		    color:#FFFFFF;
		    text-align:center;
		}

		.ui-dropdownchecklist-item{
			padding: 5px 5px 5px 5px;
		}
		.ul-0{
			display:block;
			float:left;
			width: 700px;
			text-align:left;
			list-style-type: none;
			left:10px;
			margin-left: 0px;
			padding-left: 0px;

		}
		.ul-0 li{
			cursor:pointer;
			border: 1px solid #e6e6e6;
			padding: 3px 3px 3px 3px;
			font-size:16px;
			background-color: #F0F0F0;
			height: 25px;

		}
		.ul-1{
			display:block;
			text-align:left;
			width: 690px;
			float:left;
			list-style-type: none;
			padding-left: 10px;

		}
		.ul-1 li{
			cursor:pointer;
			font-size:14px;
			background-color: #F3F3F3;
			height: 25px;

		}
		.ul-2{
			display:block;
			text-align:left;
			width: 680px;
			float:left;
			list-style-type: none;
			cursor: default;
			padding-left: 10px;

		}
		.ul-2 li{
			background-color: #F9F9F9;
			font-size:12px;
			display: table;
		}

		</style>';

		return $style;

	}

	##################################### geraForm
	private function geraForm(){

		global $con;
		global $login_fabrica;

		$form = NULL;

		if (!empty($this->msg_erro)) {
			$form .=	'<div class="msg_erro" style="width:700px; margin:auto;">';
			$form .= $this->msg_erro;
			$form .= "</div>";
		}
		if (!empty($this->msg_sucesso)) {
			$form .='<div class="sucesso" style="width:700px; margin:auto;">';
			$form .= $this->msg_sucesso;
			$form .= "</div>";
		}
		$form .= '<form name="frm_pesquisa" id="frm_pesquisa" method="post">
			<input type="hidden" name="diagnostico" value="'.$diagnostico.'" />
			<table width="700px" border="0" align="center" cellpadding="3" cellspacing="0" class="formulario">
					<tr>
						<td colspan="4" class="titulo_tabela">Cadastro</td>
					</tr>

					<tr>
						<td width="100px">&nbsp;</td>
						<td width="250px">&nbsp;</td>
						<td width="250px">&nbsp;</td>
						<td width="100px">&nbsp;</td>
					</tr>

					<tr>
						<td>&nbsp;</td>
						<td>
							Tabela de serviço<br />
				';

				$sql = "SELECT
							tabela_mao_obra,
							sigla_tabela,
							descricao
						FROM tbl_tabela_mao_obra
						WHERE tbl_tabela_mao_obra.fabrica = ".$login_fabrica." AND tbl_tabela_mao_obra.ativo is TRUE
						ORDER BY tbl_tabela_mao_obra.sigla_tabela;";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$form .=  "<select class='frm' multiple='multiple' id='tabela' name='tabela' style='width: 200px' >";

						for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
							$tabela_mao_obra = trim(pg_fetch_result($res,$x,tabela_mao_obra));
							$sigla_tabela  = trim(pg_fetch_result($res,$x,sigla_tabela));
							$descricao  = trim(pg_fetch_result($res,$x,descricao));
							$selected = ($tabela_mao_obra == $_POST['tabela']) ? " selected='selected' " : "" ;

							$form .=  "<option value='".$tabela_mao_obra."' title='".$sigla_tabela." - ".$descricao."' ".$selected." label='".$sigla_tabela."'>".$sigla_tabela." - ".$descricao."</option>";
						}

					$form .=  "</select>";
				}
	 			$form .= '	
						</td>
						<td>
							Familia<br />';

				$sql = "SELECT
							familia,
							descricao
						FROM tbl_familia
						WHERE fabrica = ".$login_fabrica." AND ativo is TRUE";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$form .=  "<select name='familia' multiple='multiple' id='familia' class='frm' style='width: 200px'>";
					$form .= "<option value=0>Sem tabela</option>";
					for ($i=0;$i < pg_num_rows($res);$i++) {
						$familia 				= pg_fetch_result($res,$i,'familia');
						$descricao 					= pg_fetch_result($res,$i,'descricao');

						$selected = ($familia == $_POST['familia']) ? " selected = 'selected' " : "";

						$form .=  "<option value='".$familia."' ".$selected." >".$descricao."</option>";
					}
					$form .=  "</select>";
				}

					$form .= '
						</td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							Grupo Defeito Constatado<br />';

				$sql = "SELECT
							grupo_codigo,
							descricao,
							defeito_constatado_grupo
						FROM tbl_defeito_constatado_grupo
						WHERE fabrica = ".$login_fabrica." AND ativo IS TRUE
						order by descricao";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$form .=  "<select name='defeito_constatado_grupo' id='defeito_constatado_grupo' class='frm' style='width: 200px'>";
					$form .=  "<option value='' selected='selected' >Selecione</option>";
					for ($i=0;$i < pg_num_rows($res);$i++) {
						$x_grupo_codigo 				= pg_fetch_result($res,$i,'grupo_codigo');
						$x_descricao 					= pg_fetch_result($res,$i,'descricao');
						$x_defeito_constatado_grupo 	= pg_fetch_result($res,$i,'defeito_constatado_grupo');

						$selected = ($x_defeito_constatado_grupo == $_POST['defeito_constatado_grupo']) ? " selected = 'selected' " : "";

						$form .=  "<option value='".$x_defeito_constatado_grupo."' ".$selected." >".$x_descricao."</option>";
					}
					$form .=  "</select>";
				}

					$form .= '
						</td>
						<td>
							Defeito Constatado<br />';

								$sql = "SELECT
											defeito_constatado,
											codigo,
											descricao
										FROM tbl_defeito_constatado
										WHERE
											fabrica = ".$login_fabrica."
											AND defeito_constatado_grupo = ".$_POST['defeito_constatado_grupo']."
											AND ativo
										ORDER BY descricao ASC;";
										$res = @pg_query($con,$sql);

								$form .= "<select class='frm' id='defeito_constatado' multiple='multiple' name='defeito_constatado' style='width: 200px'>";
									if (@pg_num_rows($res) > 0) {

										for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
											$x_defeito_constatado = trim(pg_fetch_result($res,$i,'defeito_constatado'));
											$x_nome  = trim(pg_fetch_result($res,$i,'descricao'));

											$selected = ($x_defeito_constatado == $_POST['defeito_constatado']) ? " selected='selected' " : "" ;

											$form .= "<option value='".$x_defeito_constatado."' ".$selected." label='".$x_nome."'>".$x_nome."</option>";
										}

									}
								$form .= "</select>";
	 				$form .= '
						</td>

						<td>&nbsp;</td>
					</tr>
					<tr>

						<td colspan="4" align="center">
							Valor Mão de Obra<br />
							<input class="frm money" id="mao_de_obra" name="mao_de_obra" value="'.$_POST['mao_de_obra'].'" style="width: 200px" />
						</td>
					</tr>
					<tr>
						<td colspan="4" style="padding: 20px; text-align: center">
							<input type="submit"  name="btn_acao" value="Gravar" />
							<input type="submit"  name="btn_acao" value="Consultar" />
						</td>
					</tr>
			</table>
		</form><br />';

			return $form;

	}

	##################################### geraTabela
	private function geraTabela($Array){

		$tabela = NULL;

		$qtArray = count($Array);

		if(empty($Array[0])){
				$tabela .= "<table align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
				$tabela .= "<tr>";
				$tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
				$tabela .= "<strong>Consulta de todos registros</strong>";
				$tabela .= "</td>";
				$tabela .= "</tr>";
				$tabela .= "<tr>";
				$tabela .= "<td>";
				$tabela .= "Não existem registros!";
				$tabela .= "</td>";
				$tabela .= "</tr>";
				$tabela .= "</table>";
			return $tabela;
		}

		$tabela .= "<script>
						function move_i(what) { what.style.background='#D9E2EF'; }
						function move_o(what) { what.style.background='#FFFFFF'; }
					</script>";
		$tabela .= "<table align=\"center\" width=\"695px\" cellspacing=\"1\" id=\"tabelaRegistro\">";
		$tabela .= "<tr>";
		$tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
		$tabela .= "<strong>Consulta de todos registros</strong>";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "<tr>";
		$tabela .= "<td>";

		$tabela .= "<form method=\"post\">";
		$tabela .= "<table align=\"center\" width=\"700\" >";
		$tabela .= "<tr style=\"border: 1px solid silver;background-color:#596D9B; color: #FFFFFF;\">";
		$tabela .= "<td style=\"border: 1px solid silver;\"/>";
		$tabela .= "<input  type=\"checkbox\" id=\"selecionarTodos\" />";
		$tabela .= "</td>";

		foreach ($Array[0] as $key => $value) {

			$key = ($key == "mao_de_obra") ? "Mão de obra (R$)" : $key;
			$key = ($key == "familia") ? "Familia" : $key;
			$key = ($key == "grupo") ? "Grupo de defeito" : $key;
			$key = ($key == "defeito_constatado") ? "Defeito constatado" : $key;
			$key = ($key == "sigla_tabela") ? "Sigla da tabela de serviço" : $key;

			if(empty($countArray) && $key != "diagnostico"){	
				$tabela .= "<td style=\"border: 1px solid silver;\"><strong>".$key."</strong></td>";
			}
		}
		$tabela .= "</tr>";

		for($countArray = 0; $qtArray>$countArray;$countArray++){

				$tabela .= "<tr style=\"border: 1px solid silver;\"  onMouseOver='move_i(this)' onMouseOut='move_o(this)'>";
				$tabela .= "<td style=\"border: 1px solid silver;\">";
				$tabela .= "<input type=\"checkbox\" name=\"id_tabela_mao_obra\" id=\"id_tabela_mao_obra\" value=\"".$Array[$countArray]['diagnostico']."\" />";
				$tabela .= "</td>";
				foreach ($Array[$countArray] as $key => $value) {
					if($key != "diagnostico"){

						$tabela .= "<td style=\"border: 1px solid silver;\">".$value."</td>";
					}
				}
				$tabela .= "</tr>";

		}

		$tabela .= "</table>";
		$tabela .= "<table align=\"center\" width=\"700\" >";

		$tabela .= "<tr style=\"border: 1px solid silver;\">";
		$tabela .= "<td style=\"border: 1px solid silver;\" align=\"left\"/>";
		$tabela .= "<select  name=\"consultaAction\">
						<option value=\"excluir\">Excluir</option>
					</select>
					<input type=\"submit\" value=\"Gravar\" />";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "</table>";
		$tabela .= "</form>";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "</table>";
		return $tabela;

	}

	##################################### geraTabelaDinamica
	private function geraTabelaDinamica($Array,$cond){

		global $login_fabrica;
		global $con;
	    $tabelaDinamica = NULL;
	    $qtArray = count($Array);

	    if(empty($Array[0])){
				$tabelaDinamica .= "<table align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
				$tabelaDinamica .= "<tr>";
				$tabelaDinamica .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
				$tabelaDinamica .= "<strong>Escolha a Tabela de Serviços que você deseja analisar</strong>";
				$tabelaDinamica .= "</td>";
				$tabelaDinamica .= "</tr>";
				$tabelaDinamica .= "<tr>";
				$tabelaDinamica .= "<td>";
				$tabelaDinamica .= "Não existem registros!";
				$tabelaDinamica .= "</td>";
				$tabelaDinamica .= "</tr>";
				$tabelaDinamica .= "</table>";
			return $tabelaDinamica;
		}
	    $tabelaDinamica .= "<table align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
	    $tabelaDinamica .= "<tr>";
	    $tabelaDinamica .= "<td colspan=\"3\" align=\"\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
	    $tabelaDinamica .= "<strong>Escolha a Tabela de Serviços que você deseja analisar</strong>";
	    $tabelaDinamica .= "</td>";
	    $tabelaDinamica .= "</tr>";
	    $tabelaDinamica .= "<tr>";
	    $tabelaDinamica .= "<td>";

        //foreach para um item especifico
        $titleText = "sigla_tabela";
        for($counter = 0,$qtInd = 0; $counter<$qtArray; $counter++){

       		$sigla = $Array[$counter][$titleText];

       		$varX = TRUE;
      		for($ctArray = ($counter+1); $ctArray<$qtArray; $ctArray++){
      			if($sigla == $Array[$ctArray][$titleText]){
      				$varX = FALSE;
      			}
      		}

      		if($varX == TRUE){
      			if(!empty($sigla)){
      				$item[$qtInd] = $sigla." - ".$Array[$counter]["descricao"];
      			}
      			$qtInd++;
      		}

        }

        for($ctInd = 0; $ctInd<$qtInd; $ctInd++){
        	if(empty($item[$ctInd])){

				$tabelaDinamica .= "<ul  class='ul-0'>";
	            $tabelaDinamica .= "<li>-  Sem tabela</li>";

			$cmdSQL =  "SELECT DISTINCT tbl_familia.familia
				       	FROM tbl_diagnostico
	    				JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
			    		WHERE tbl_diagnostico.fabrica = ".$login_fabrica."
					AND   tabela_mao_obra is NULL
					AND defeito_constatado is NOT NULL
					$cond;";

	    		$resSQL = pg_query($con,$cmdSQL);
                $fetchDbSQL = pg_fetch_all($resSQL);
                foreach ($fetchDbSQL as $valueSQL) {
                		$tabelaDinamica .= "<ul style='display:none;' class='ul-1'>";
                        foreach ($valueSQL as $value) {
                        	if($value != "Geral"){
                        		$tabelaDinamica .= "<li>-  ".$value."</li>";

                                $cmdSQLDefeito =  "SELECT DISTINCT tbl_defeito_constatado.descricao, tbl_diagnostico.mao_de_obra,tbl_diagnostico.diagnostico, tbl_diagnostico.ativo, tbl_defeito_constatado_grupo.descricao AS defeito_grupo FROM tbl_diagnostico
                                JOIN tbl_defeito_constatado USING(defeito_constatado)
                                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia 
				WHERE tbl_diagnostico.fabrica = ".$login_fabrica."
			       	AND tabela_mao_obra is NULL
				AND tbl_familia.familia = '".$value."'
				$cond
				ORDER BY tbl_diagnostico.diagnostico DESC;";

                                $resSQLDefeito = pg_query($con,$cmdSQLDefeito);
                                $fetchDbSQLDefeito = pg_fetch_all($resSQLDefeito);
                                $tabelaDinamica .= "<ul style='display:none;'  class='ul-2'>";
                                $tabelaDinamica .= "<li style='cursor:default;'>

	                                        	<div style='float:right;'>

	                                        	<table style='width:670px;'>
	                                        		<tr  style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">
	                                        			<td align='center'>Grupo de Defeito Constatado</td>
	                                        			<td align='center'>Defeito Constatado</td>
	                                        			<td align='center'>Valor</td>
	                                        			<td align='center'>Ações</td>
	                                        		</tr>";
                                foreach($fetchDbSQLDefeito as $valueDefeito){
										if($valueDefeito['ativo'] == 'f'){
                                			$style = "style='background-color:red;'";
                                			$disabled = "disabled = 'disabled'";
                                			$botaoAtivarInativar = "<input type='submit' name='consultaValue' style='display:none;' value='Gravar' /><input type='submit' name='consultaValue' value='Ativar' />";
                                		}

                                		if($valueDefeito['ativo'] == 't'){
                                			$style = "style='background-color:#F9F9F9;'";
                                			$disabled = "";
                                			$botaoAtivarInativar = "<input type='submit'  style='display:inline;' name='consultaValue' value='Gravar' /><input type='submit' name='consultaValue' value='Inativar' />";
                                		}

                                		$valorDiagnostico = (strpos($valueDefeito['mao_de_obra'], ".")) ? $valueDefeito['mao_de_obra'] : $valueDefeito['mao_de_obra'].".00";

                                		$explodeDiagnostico = explode('.', $valorDiagnostico);

                                		if(strlen($explodeDiagnostico[0]) == 1){
                                			$explodeDiagnostico[0] = "0".$explodeDiagnostico[0];
                                		}
                                		if(strlen($explodeDiagnostico[1]) == 1){
                                			$explodeDiagnostico[1] = $explodeDiagnostico[1]."0";
                                		}

                                		$valorDiagnostico = $explodeDiagnostico[0].".".$explodeDiagnostico[1];

                                        $tabelaDinamica .= "

	                                        		<tr ".$style.">
	                                        			<td>".$valueDefeito['defeito_grupo']."</td>
	                                        			<td>".$valueDefeito['descricao']."</td>
	                                        			<td><input type='hidden' name='diagnosticoId' value ='".$valueDefeito['diagnostico']."' /><input type='text' ".$disabled." name='valorMaoDeObra' value='".$valorDiagnostico."' class='money' size='5' /></td>
	                                        			<td>

	                                        				".$botaoAtivarInativar."
	                                        				<img src=\"imagens/help.png\" style='cursor:help;' title=\"Ao inativar, o sistema irá ignorar o cadastro feito.\" />
	                                        			</td>
	                                        		</tr>
	                                        	";

                        		}
                        		$tabelaDinamica .= "</table>

	                                        	</div>
                                        </li>";

                        	}
                        	if($value == "Geral"){
                        		$tabelaDinamica .= "<li>-  Sem familia</li>";

					$cmdSQLDefeito =  "SELECT DISTINCT tbl_diagnostico.ativo,tbl_defeito_constatado.descricao, tbl_diagnostico.mao_de_obra,tbl_diagnostico.diagnostico, tbl_defeito_constatado_grupo.descricao AS defeito_grupo FROM tbl_diagnostico
				JOIN tbl_familia USING(familia)
                                JOIN tbl_defeito_constatado USING(defeito_constatado)
                                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                                WHERE tbl_diagnostico.fabrica = ".$login_fabrica." AND
				tabela_mao_obra is NULL
				$cond
				ORDER BY tbl_diagnostico.diagnostico DESC;";

                                $resSQLDefeito = pg_query($con,$cmdSQLDefeito);
                                $fetchDbSQLDefeito = pg_fetch_all($resSQLDefeito);
                                $tabelaDinamica .= "<ul style='display:none;'  class='ul-2'>";
                                $tabelaDinamica .= "<li style='cursor:default;'>

	                                        	<div style='float:right;'>

	                                        	<table style='width:670px;'>
	                                        		<tr  style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">
	                                        			<td align='center'>Grupo de Defeito Constatado</td>
	                                        			<td align='center'>Defeito Constatado</td>
	                                        			<td align='center'>Valor</td>
	                                        			<td align='center'>Ações</td>
	                                        		</tr>";
                                foreach($fetchDbSQLDefeito as $valueDefeito){

                                		if($valueDefeito['ativo'] == 'f'){
                                			$style = "style='background-color:red;'";
                                			$disabled = "disabled = 'disabled'";
                                			$botaoAtivarInativar = "<input type='submit' name='consultaValue' style='display:none;' value='Gravar' /><input type='submit' name='consultaValue' value='Ativar' />";
                                		}

                                		if($valueDefeito['ativo'] == 't'){
                                			$style = "style='background-color:#F9F9F9;'";
                                			$disabled = "";
                                			$botaoAtivarInativar = "<input type='submit' name='consultaValue'  style='display:inline;' value='Gravar' /><input type='submit' name='consultaValue' value='Inativar' />";
                                		}

                                		$valorDiagnostico = (strpos($valueDefeito['mao_de_obra'], ".")) ? $valueDefeito['mao_de_obra'] : $valueDefeito['mao_de_obra'].".00";

                                		$explodeDiagnostico = explode('.', $valorDiagnostico);

                                		if(strlen($explodeDiagnostico[0]) == 1){
                                			$explodeDiagnostico[0] = "0".$explodeDiagnostico[0];
                                		}
                                		if(strlen($explodeDiagnostico[1]) == 1){
                                			$explodeDiagnostico[1] = $explodeDiagnostico[1]."0";
                                		}

                                		$valorDiagnostico = $explodeDiagnostico[0].".".$explodeDiagnostico[1];
                                        $tabelaDinamica .= "

	                                        		<tr ".$style.">
	                                        			<td>".$valueDefeito['defeito_grupo']."</td>
	                                        			<td>".$valueDefeito['descricao']."</td>
	                                        			<td><input type='hidden' name='diagnosticoId' value ='".$valueDefeito['diagnostico']."' /><input type='text' ".$disabled." name='valorMaoDeObra' value='".$valorDiagnostico."' class='money' size='5' /></td>
	                                        			<td>
	                                        				".$botaoAtivarInativar."
	                                        				<img src=\"imagens/help.png\" style='cursor:help;' title=\"Ao inativar, o sistema irá ignorar o cadastro feito.\" />
	                                        			</td>
	                                        		</tr>
	                                        	";

                        		}
                        		$tabelaDinamica .= "</table>
	                                        	</div>
                                        </li>";
							}
						$tabelaDinamica .= "</ul>";
					}
					$tabelaDinamica .= "</ul>";
				}
				$tabelaDinamica .= "</ul>";   

            }  
        	if(!empty($item[$ctInd])){
        		$explodeItem = explode(" - ", $item[$ctInd]);
	        	$tabelaDinamica .= "<ul  class='ul-0'>";
	            $tabelaDinamica .= "<li>-  ".$item[$ctInd]."</li>";

	    		$cmdSQL =  "SELECT DISTINCT tbl_familia.descricao FROM tbl_diagnostico
	    		JOIN tbl_tabela_mao_obra USING(tabela_mao_obra)
	    		JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = ".$login_fabrica."
			WHERE tbl_diagnostico.fabrica = ".$login_fabrica."
			AND sigla_tabela ILIKE '".$explodeItem[0]."%'
			$cond;";

	    		$resSQL = pg_query($con,$cmdSQL);
	                $fetchDbSQL = pg_fetch_all($resSQL);

	                foreach ($fetchDbSQL as $valueSQL) {
	                		$tabelaDinamica .= "<ul style='display:none;' class='ul-1'>";
	                        foreach ($valueSQL as $value) {
	                                $tabelaDinamica .= "<li>-  ".$value."</li>";

	                                $cmdSQLDefeito =  "SELECT DISTINCT tbl_defeito_constatado.descricao, tbl_diagnostico.mao_de_obra,tbl_diagnostico.diagnostico, tbl_diagnostico.ativo, tbl_defeito_constatado_grupo.descricao AS defeito_grupo FROM tbl_diagnostico
	                                JOIN tbl_defeito_constatado USING(defeito_constatado)
	                                JOIN tbl_tabela_mao_obra USING(tabela_mao_obra)
	                                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
	                                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = ".$login_fabrica."
	                                WHERE tbl_diagnostico.fabrica = ".$login_fabrica." AND
	                                sigla_tabela ILIKE '".$explodeItem[0]."%' AND
					tbl_familia.descricao = '".$value."'
					$cond
					ORDER BY tbl_diagnostico.diagnostico DESC;";

	                                $resSQLDefeito = pg_query($con,$cmdSQLDefeito);
	                                $fetchDbSQLDefeito = pg_fetch_all($resSQLDefeito);
	                                $tabelaDinamica .= "<ul style='display:none;'  class='ul-2'>";
	                               $tabelaDinamica .= "<li style='cursor:default;'>

	                                        	<div style='float:right;'>

	                                        	<table style='width:670px;'>
	                                        		<tr  style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">
	                                        			<td align='center'>Grupo de Defeito Constatado</td>
	                                        			<td align='center'>Defeito Constatado</td>
	                                        			<td align='center'>Valor</td>
	                                        			<td align='center'>Ações</td>
	                                        		</tr>";
                                foreach($fetchDbSQLDefeito as $valueDefeito){

                                		if($valueDefeito['ativo'] == 'f'){
                                			$style = "style='background-color:red;'";
                                			$disabled = "disabled = 'disabled'";
                                			$botaoAtivarInativar = "<input type='submit' name='consultaValue'  style='display:none;' value='Gravar' /><input type='submit' name='consultaValue' value='Ativar' />";
                                		}

                                		if($valueDefeito['ativo'] == 't'){

                                			$style = "style='background-color:#F9F9F9;'";
                                			$disabled = "";
                                			$botaoAtivarInativar = "<input type='submit' name='consultaValue'  style='display:inline;' value='Gravar' /><input type='submit' name='consultaValue' value='Inativar' />";
                                		}

                                		$valorDiagnostico = (strpos($valueDefeito['mao_de_obra'], ".")) ? $valueDefeito['mao_de_obra'] : $valueDefeito['mao_de_obra'].".00";

                                		$explodeDiagnostico = explode('.', $valorDiagnostico);

                                		if(strlen($explodeDiagnostico[0]) == 1){
                                			$explodeDiagnostico[0] = "0".$explodeDiagnostico[0];
                                		}
                                		if(strlen($explodeDiagnostico[1]) == 1){
                                			$explodeDiagnostico[1] = $explodeDiagnostico[1]."0";
                                		}

                                		$valorDiagnostico = $explodeDiagnostico[0].".".$explodeDiagnostico[1];

                                        $tabelaDinamica .= "

	                                        		<tr ".$style.">
	                                        			<td>".$valueDefeito['defeito_grupo']."</td>
	                                        			<td>".$valueDefeito['descricao']."</td>
	                                        			<td><input type='hidden' name='diagnosticoId' value ='".$valueDefeito['diagnostico']."' /><input type='text' ".$disabled."  name='valorMaoDeObra' value='".$valorDiagnostico."' class='money' size='5' /></td>
	                                        			<td>

	                                        				".$botaoAtivarInativar."
	                                        				<img src=\"imagens/help.png\" style='cursor:help;' title=\"Ao inativar, o sistema irá ignorar o cadastro feito.\" />
	                                        			</td>
	                                        		</tr>
	                                        	";

                        		}
                        		$tabelaDinamica .= "</table>

	                                        	</div>
                                        </li>";
	                                $tabelaDinamica .= "</ul>";

	                        }
	                    $tabelaDinamica .= "</ul>";
                    }
              $tabelaDinamica .= "</ul>";   
            }

        }

        //foreach para um item especifico

        $tabelaDinamica .= "</td>";
        $tabelaDinamica .= "</tr>";
        $tabelaDinamica .= "</table>";
        return $tabelaDinamica;
	}

	##################################### set
	public function set($prop,$value){
        $this->$prop = $value;
    }

    ##################################### main
	public function main(){

		global $con;
		global $login_fabrica;
		global $PHP_SELF;

		echo $this->jquerySource();
		echo $this->styleSource();

		$tipo 		= trim($_POST['tipo']);
		$btn_acao 	= trim($_POST['btn_acao']);
		$diagnosticoGet = $_GET['diagnostico'];

		if($_POST['consultaAction'] == "excluir"){
			$postDelete = file_get_contents("php://input");

			$explodePost = explode("&", $postDelete);

			$qtPost = count($explodePost);

			$cmdDelete =  NULL;

			$cmdDelete .= "DELETE FROM tbl_diagnostico WHERE diagnostico in (";

			if($qtPost==1){
				$this->set("msg_erro", "Você deve selecionar algum item para excluir");
			}

			if($qtPost>1){
				for($countPost = 0; $countPost<($qtPost-1); $countPost++){

					$numPost = explode("=", $explodePost[$countPost]);

					if(($countPost+1) == ($qtPost-1)){
						$cmdDelete .= $numPost[1]."";
					}else{
						$cmdDelete .= $numPost[1].",";
					}

				}
				$cmdDelete .= ") AND fabrica = ".$login_fabrica.";";

				$resDelete = pg_query($con, $cmdDelete);
				$fetchDbDelete = pg_fetch_all($resDelete);

				$this->set("msg_sucesso", "".($countPost)." registro(s) excluídos com sucesso!");

			}

		}

		if ($btn_acao == "Gravar") {

			$postGravar = file_get_contents("php://input");

			$explodePost = explode("&", $postGravar);

			$qtPost = count($explodePost);

			for($countPost = 0, $qtTabela = 0; $countPost<=$qtPost; $countPost++){
				$postTabela = explode("=",$explodePost[$countPost]);
				if($postTabela[0] == "tabela" && $postTabela[1] != ""){
					$tabelaServico[$countPost] = $postTabela[1];
					$qtTabela++;
				}
			}

			for($countPost = 0, $qtFamilia = 0; $countPost<=$qtPost; $countPost++){
				$postFamilia = explode("=",$explodePost[$countPost]);
				if($postFamilia[0] == "familia" && $postFamilia[1] != ""){
					$familiaPost[$countPost] = $postFamilia[1];
					$qtFamilia++;
				}
			}

			for($countPost = 0, $qtDefeito = 0; $countPost<=$qtPost; $countPost++){
				$postDefeito = explode("=",$explodePost[$countPost]);
				if($postDefeito[0] == "defeito_constatado" && $postDefeito[1] != ""){
					$defeitoPost[$countPost] = $postDefeito[1];
					$qtDefeito++;
				}
			}

			$defeito_constatado 		= (int) $_POST['defeito_constatado'];
			$familia 		= (int) $_POST['familia'];
			$defeito_constatado_grupo 	= (int) $_POST['defeito_constatado_grupo'];
			$tbl_diagnostico			= (int) $_POST['diagnostico'];
			$mao_de_obra				= trim($_POST['mao_de_obra']);
			$mao_de_obra = str_replace(".", "", $mao_de_obra);
	 		$mao_de_obra = str_replace(",", ".", $mao_de_obra);

	 		if($defeito_constatado == 0 AND empty($this->msg_erro))  {
	 			$this->set('msg_erro','Selecione um defeito constatado');

	 		}

	 		if(($mao_de_obra == 0 OR empty($mao_de_obra)) AND empty($this->msg_erro))  {
	 			$this->set('msg_erro','Informe o valor da mão de obra');

	 		}

	 		if(empty($familiaPost) && !empty($tabelaServico))  {
	 			$this->set('msg_erro','É necessário selecionar uma familia ou remover as tabelas');

	 		}

	 		if(!empty($familiaPost) && empty($tabelaServico))  {
	 			$this->set('msg_erro','É necessário selecionar uma tabela ou remover as familias');

	 		}

	 		if($diagnosticoGet == 0){
	 			$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE linha = {$linha} AND defeito_constatado = {$defeito_constatado} AND fabrica = {$login_fabrica}";
	 			$res = @pg_query($con, $sql);
	 			if(pg_num_rows($res))
	 				$diagnostico = pg_fetch_result($res, 0, 'diagnostico');
	 		}else{   //verifica se o diagnostico é válido!
	 			$sql = "SELECT diagnostico FROM tbl_diagnostico WHERE diagnostico = {$diagnostico};";
	 			$res = @pg_query($con, $sql);
	 			if(pg_num_rows($res) == 0){
	 				$diagnostico = 0;
	 			}
	 		}

	 		if(empty($this->msg_erro)){

		 		if(empty($diagnosticoGet)){
		 			if(empty($qtTabela) && empty($qtFamilia)){
		 				$itensErro = NULL;
		 				foreach ($defeitoPost as $valueDefeito) {
			 				$sqlVerf = "SELECT diagnostico, tbl_defeito_constatado.descricao
			 				FROM tbl_diagnostico
			 				JOIN tbl_defeito_constatado USING(defeito_constatado)
			 				WHERE defeito_constatado = $valueDefeito AND tbl_diagnostico.familia = 0 AND tabela_mao_obra is NULL; ";

			 				$resVerf = pg_query($con, $sqlVerf);
			 				$fecthVerf = pg_fetch_all($resVerf);

			 				if(!empty($fecthVerf)){
	 							$itensErro .= $fecthVerf[0]['descricao']."<br />";

			 				}
		 				}
		 				if(empty($itensErro)){
			 				foreach ($defeitoPost as $valueDefeito) {
				 				$sql = "INSERT INTO tbl_diagnostico ( defeito_constatado, familia, fabrica, mao_de_obra) VALUES ( $valueDefeito, $familia, $login_fabrica, '$mao_de_obra');";
				 				$sqlVerf = "SELECT diagnostico, tbl_defeito_constatado.descricao
				 				FROM tbl_diagnostico
				 				JOIN tbl_defeito_constatado USING(defeito_constatado)
				 				WHERE defeito_constatado = $valueDefeito AND tbl_diagnostico.familia = 0 AND tabela_mao_obra is NULL; ";

				 				$resVerf = pg_query($con, $sqlVerf);
				 				$fecthVerf = pg_fetch_all($resVerf);

				 				if(empty($fecthVerf)){
					 				if(pg_query($con, $sql)){
					 					$this->set('msg_sucesso','Gravado com sucesso');
					 					unset($_POST);
					 				}else{
					 					$this->set('msg_erro','Falha ao inserir!');
					 				}
				 				}
			 				}
		 				}

		 				if(!empty($itensErro)){
		 					$this->set('msg_erro','Ação cancelada, defeito(s) constatado(s) já cadastrados:<br /> '.$itensErro.'<br /> ');
		 				}
		 			}else{
		 				foreach ($tabelaServico as $valueTabela) {
		 					foreach ($familiaPost as $valueFamilia){
		 						foreach ($defeitoPost as $valueDefeito) {

		 							$sqlVerf = "SELECT diagnostico, tbl_defeito_constatado.descricao
			 						FROM tbl_diagnostico
			 						JOIN tbl_defeito_constatado USING(defeito_constatado)
			 						WHERE defeito_constatado = $valueDefeito AND tabela_mao_obra = $valueTabela AND tbl_diagnostico.familia = $familia;";

			 						$resVerf = pg_query($con, $sqlVerf);
			 						$fecthVerf = pg_fetch_all($resVerf);

			 						if(!empty($fecthVerf)){
				 						$itensErro .= $fecthVerf[0]['descricao']."<br />";

				 					}

		 						}
		 						if(empty($itensErro)){
			 						foreach ($defeitoPost as $valueDefeito) {
			 							$sql = "INSERT INTO tbl_diagnostico ( tabela_mao_obra, familia, defeito_constatado, fabrica, mao_de_obra) VALUES ( $valueTabela, $valueFamilia, $valueDefeito, $login_fabrica, '$mao_de_obra');";

			 							$sqlVerf = "SELECT diagnostico, tbl_defeito_constatado.descricao
				 						FROM tbl_diagnostico
				 						JOIN tbl_defeito_constatado USING(defeito_constatado)
				 						WHERE defeito_constatado = $valueDefeito AND tabela_mao_obra = $valueTabela AND tbl_diagnostico.familia = $familia;";

				 						$resVerf = pg_query($con, $sqlVerf);
				 						$fecthVerf = pg_fetch_all($resVerf);

				 						if(empty($fecthVerf)){
						 					if(pg_query($con, $sql)){
							 					$this->set('msg_sucesso','Gravado com sucesso');
							 					unset($_POST);
						 					}else{
						 						$this->set('msg_erro','Falha ao inserir!');
						 					}
					 					}else{
					 						$itensErro .= $fecthVerf[0]['descricao']."<br />";

					 					}

			 						}
		 						}
		 						if(!empty($itensErro)){
		 							$this->set('msg_erro','Ação cancelada, defeito(s) constatado(s) já cadastrados:<br /> '.$itensErro.'<br />');
		 						}
		 					}
		 				}
		 			}
		 		}else{
		 			$sql = "UPDATE tbl_diagnostico SET mao_de_obra = '{$mao_de_obra}', linha = {$linha}, defeito_constatado = {$defeito_constatado} WHERE diagnostico = {$diagnosticoGet};";
		 		}

		 		//echo $sql;
		 		//

	 		}

		}
		echo $this->geraForm();

		if ($btn_acao == "Consultar") {
			$postDelete = file_get_contents("php://input");

			$explodePost = explode("&", $postDelete);
			$qtPost = count($explodePost);

			for($countPost = 0, $qtTabela = 0; $countPost<=$qtPost; $countPost++){
				$postTabela = explode("=",$explodePost[$countPost]);
				if($postTabela[0] == "tabela" && $postTabela[1] != ""){
					$tabelaPost[$countPost] = $postTabela[1];
					$qtTabela++;
				}
			}

			for($countPost = 0, $qtFamilia = 0; $countPost<=$qtPost; $countPost++){
				$postFamilia = explode("=",$explodePost[$countPost]);
				if($postFamilia[0] == "familia" && $postFamilia[1] != ""){
					$familiaPost[$countPost] = $postFamilia[1];
					$qtFamilia++;
				}
			}

			if(count($tabelaPost) == 0 and count($familiaPost) == 0){
				$this->set('msg_erro','É necessário selecionar uma familia or tabela para fazer a consulta');
				$form .=	'<div class="msg_erro" style="width:700px; margin:auto;">';
				$form .= $this->msg_erro;
				$form .= "</div>";

			}

			if(count($tabelaPost) > 0){
				$cond = " AND tbl_diagnostico.tabela_mao_obra in (0";
				foreach($tabelaPost as $tabela) {
					$cond .= ",$tabela";
				}
				$cond .= ") ";
			}

			if(count($familiaPost) > 0) {
				if(count($familiaPost) > 3) {
					$this->set('msg_erro','Selecione até 3 familias para fazer a consulta');
					$form .=	'<div class="msg_erro" style="width:700px; margin:auto;">';
					$form .= $this->msg_erro;
					$form .= "</div>";
				}
				$cond .= " AND tbl_familia.familia in (0";
				foreach($familiaPost as $familia) {
					$cond .= ",$familia";
				}
				$cond .= ") ";
			}

			$sqlTabela = "SELECT
							tbl_diagnostico.diagnostico,
							tbl_defeito_constatado.descricao 		AS defeito_constatado,
							tbl_defeito_constatado_grupo.descricao	AS grupo,
							tbl_diagnostico.mao_de_obra 			AS mao_de_obra,
							tbl_familia.descricao        			AS familia,
							tbl_tabela_mao_obra.sigla_tabela        AS sigla_tabela,
							tbl_tabela_mao_obra.descricao           AS descricao
						FROM tbl_diagnostico
							JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
							LEFT JOIN tbl_tabela_mao_obra ON tbl_tabela_mao_obra.tabela_mao_obra = tbl_diagnostico.tabela_mao_obra
							JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = ".$login_fabrica."
							JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = ".$login_fabrica."
							WHERE tbl_diagnostico.fabrica = ".$login_fabrica." 
							$cond
							ORDER BY tbl_diagnostico.familia ASC;";
			if(empty($this->msg_erro)){
				$resTabela = pg_query($con, $sqlTabela);
				$fetchDbTabela = pg_fetch_all($resTabela);
				echo $this->geraTabelaDinamica($fetchDbTabela,$cond);
			}else{
				echo $form;
			}
			//echo $this->geraTabela($fetchDbTabela);
		}

	}

}

$pagGenerate = new pagGenerate();

$admin_privilegios = "cadastros";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = 'cadastro';

$title = "MANUTENÇÃO DE MÃO-DE-OBRA";

include 'cabecalho.php';

$pagGenerate->main();

include "rodape.php";

 ?>
