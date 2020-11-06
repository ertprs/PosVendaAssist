<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title       = "MÓDULO PARA CONTROLE GERENCIAL";

include 'cabecalho.php';
include "javascript_pesquisas.php";
include "javascript_calendario.php"
?>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<style type="text/css">
	/* aqui são as definições do TÍTULO DA CAIXA*/
	.Titulo {
		text-align      : center;
		font-family     : Arial;
		font-size       : 11px;
		font-weight     : bold;
		color           : #FFFFFF;
		background-color: #485989;
	}
	/* aqui são as definições do CAIXA DE ERROS */
	.Erro {
		text-align      : center;
		font-family     : Arial;
		font-size       : 12px;
		font-weight     : bold;
		color           : #FFFFFF;
		background-color: #FF0000;
	}
	/* aqui são as definições do CONTEÚDO DA CAIXA*/
	.Conteudo {
		text-align      : left;
		font-family     : Arial;
		font-size       : 11px;
		font-weight     : normal;
		color           : #000000;
	}
</style>

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script language="JavaScript">
	function toogleProd(radio){
		var obj = document.getElementsByName('radio_controle');
		if (obj[0].checked){
			$('#id_um').show("slow");
			$('#id_dois').hide("slow");
			$('#id_tres').hide("slow");
		}
		if (obj[1].checked){
			$('#id_um').hide("slow");
			$('#id_dois').show("slow");
			$('#id_tres').heide("slow");
		}
		if (obj[2].checked){
			$('#id_um').hide("slow");
			$('#id_dois').hide("slow");
			$('#id_tres').show("slow");
		}
	}
	function teste() {
		$('#id_um').show("slow");
		$('#id_dois').hide("slow");
		$('#id_tres').hide("slow");
	}
</script>

<!-- *****  INÍCIO DO PROCESSOS DE MONTAGEM DO LAY-OUT  ***** -->
<body onload="teste()">
	<form enctype = "multipart/form-data" name = 'frm_relatorio_gerencial' method = 'POST' action='<? echo $PHP_SELF?>' align='center'>
		<br>
		<table width='900' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
			<tr>
				<td class='Titulo' background='imagens_admin/azul.gif'>
					<b>
					RELATÓRIO GERENCIAL DE POSICIONAMENTE GERAL
					</b>
				</td>
				<tr>
					<td bgcolor='#DBE5F5'>
						<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
							<tr>
								<td>
									<?
										switch (count($lista) > 0) {
											case "um": 
												// Valores do RADIOBUTTON
												$mostra_os                  = " CHECKED ";
												$mostra_extrato             = "";
												$mostra_callcenter          = "";
												// VALORES DA DIV COM OS ÍTENS
												$mostra_os_item             = "display:none";
												$mostra_extrato_item        = "";
												$mostra_callcenter_item     = "";
												break;
											case "dois":
												// Valores do RADIOBUTTON
												$mostra_os                  = "";
												$mostra_extrato             = " CHECHED ";
												$mostra_callcenter          = "";
												// VALORES DA DIV COM OS ÍTENS
												$mostra_os_item             = "";
												$mostra_extrato_item        = "display:none";
												$mostra_callcenter_item     = "";
												break;
											case "tres":
												// Valores do RADIOBUTTON
												$mostra_os                  = "";
												$mostra_extrato             = "";
												$mostra_callcenter          = " CHECKED ";
												// VALORES DA DIV COM OS ÍTENS
												$mostra_os_item             = "";
												$mostra_extrato_item        = "";
												$mostra_callcenter_item     = "display:none";
												break;
										}
									?>
									<!-- ========== INÍCIO DOS MENU DE OPÇÕES DE CONTROLE DE ORDEM DE SERVIÇO ========== -->
									<div style='background-color:#F8FCDA;text-aling:center;border:1px solid #F9E780;padding:3px;margin:5px;'>
										<input type="radio" name="radio_controle" id="radio_controle" value='um' CHECKED <?=$mostra_os?>  onClick='javascript:toogleProd(this)'>
										Controle de Ordem de Serviço
										<!-- ========== INÍCIO DOS ÍTENS DO CONTROLE DE ORDEM DE SERVIÇO ========== -->
										<div id='id_um' style='<?echo $mostra_os_item;?>'>
											<table>
												<tr class="table_line">
													<td colspan="4" align="left">
														tipos de datas para filtragem
													</td>
												</tr>
											</table>
											<table>
												<tr class="table_line">
													<td colspan="5" align="left">
														<div style="float:left;">
															<input type="radio" name="radio_data_os" id="tbl_os.data_abertura">
																ABERTURA DE OS
															<input type="radio" name="radio_data_os" id="tbl_os.data_digitacao">
																DIGITAÇÃO DE OS
															<input type="radio" name="radio_data_os" id="geracao_extrado">
																GERAÇÃO DE EXTRATO
														</div>
														<div style="float:left;">
															<input type="radio" name="radio_data_os" id="tbl_os.data_nf">
																NOTA FISCAL DO PRODUTO
															<input type="radio" name="radio_data_os" id="tbl_os.data_nf_saida">
																NOTA FISCAL DE SAÍDA
															<input type="radio" name="radio_data_os" id="tbl_os.data_conserto">
																DATA DE CONSERTO
														</div>
														<div style="float:left;">
															<input type="radio" name="radio_data_os" id="tbl_os.data_fechamento">
																FECHAMENTO DE OS
															<input type="radio" name="radio_data_os" id="tbl_os.data_digitacao_fechamento">
																DIGITAÇÃO DO FECHAMENTO DA OS
															<input type="radio" name="radio_data_os" id="fechamento_extrato">
																DATA DE FECHAMENTO DE EXTRATO
														</div>
														<div style="float:left;">
															Data Inicial
															<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10"  value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" />
															Data Final
															<input type="text" name="data_final" id="data_final" size="12" maxlength="10"  value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" />
														</div>
													</td>
												</tr>
											</table>
										</div>
										<!-- ========== FIM DOS ÍTENS DO CONTROLE DE ORDEM DE SERVIÇO ========== -->
									</div>
									<!-- ========== FIM DOS MENU DE OPÇÕES DE CONTROLE DE ORDEM DE SERVIÇO ========== -->
									<!-- ========== INÍCIO DOS MENU DE OPÇÕES DE CONTROLE DE EXTRATO ========== -->
									<div style='background-color:#F8FCDA;text-aling:center;border:1px solid #F9E780;padding:3px;margin:5px;'>
										<input type="radio" name="radio_controle" value='dois'  <?=$mostra_extrato?>  onClick='javascript:toogleProd(this)'>
										Controle de Extratos
										<!-- ========== INÍCIO DOS ÍTENS DO CONTROLE DE EXTRATO ========== -->
										<div id='id_dois' style='<?echo $mostra_extrato_item;?>'>
											EXTRATO
										</div>
										<!-- ========== FIM DOS ÍTENS DO CONTROLE DE EXTRATO ========== -->
									</div>
									<!-- ========== FIM DOS MENU DE OPÇÕES DE CONTROLE DE CALL-CENTER ========== -->
									<!-- ========== INÍCIO DOS MENU DE OPÇÕES DE CONTROLE DE CALL-CENTER ========== -->
									<div style='background-color:#F8FCDA;text-aling:center;border:1px solid #F9E780;padding:3px;margin:5px;'>
										<input type="radio" name="radio_controle" value='tres'  <?=$mostra_callcenter?>  onClick='javascript:toogleProd(this)'>
										Controle de Call-Center
											<!-- ========== INÍCIO DOS ÍTENS DO CONTROLE DE CALL-CENTER ========== -->
											<div id='id_tres' style='<?echo $mostra_callcenter_item;?>'>
												CALL-CENTER
											</div>
											<!-- ========== FIM DOS ÍTENS DO CONTROLE DE ORDEM DE SERVIÇO ========== -->
									</div>
									<!-- ========== FIM DOS MENU DE OPÇÕES DE CONTROLE DE CALL-CENTER ========== -->
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</tr>
		</table>
	</form>
</body>
<!-- *****  FIM DO PROCESSOS DE MONTAGEM DO LAY-OUT  ***** -->




<?include "rodape.php";?>