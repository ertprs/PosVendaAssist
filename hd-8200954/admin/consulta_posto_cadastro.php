<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";
include "monitora.php";
include "funcoes.php";

$msg_erro = "";

if ($_POST["btn_acao"] == "submit") {

	$cidade         			= trim($_POST["cidade"]);
	$estado         			= trim($_POST["estado"]);
	$tipo_posto     			= trim($_POST["tipo_posto"]);

	$pedido_faturado        	= trim($_POST["pedido_faturado"]);
	$pedido_em_garantia     	= trim($_POST["pedido_em_garantia"]);
	$pedido_em_garantia_finalidades_diversas        = trim($_POST["pedido_em_garantia_finalidades_diversas"]);
	$coleta_peca            	= trim($_POST["coleta_peca"]);
	$reembolso_peca_estoque 	= trim($_POST["reembolso_peca_estoque"]);
	$digita_os              	= trim($_POST["digita_os"]);
	$prestacao_servico      	= trim($_POST["prestacao_servico"]);
	$gerar_xls              	= trim($_POST["gerar_xls"]);
	$credenciados           	= trim($_POST["credenciados"]);


	if(!isset($_POST['gerar_excel']) AND $login_fabrica != 117){
			//$limit = " LIMIT 501 ";
	}

	$sql = "SELECT	DISTINCT
					tbl_posto.posto                                    ,
					tbl_posto.nome                                     ,
					tbl_posto.pais                                     ,
					tbl_posto_fabrica.contato_endereco      AS endereco,
					tbl_posto_fabrica.contato_numero          AS numero,
					tbl_posto_fabrica.contato_bairro          AS bairro,
					tbl_posto.cnpj                                     ,
					tbl_posto_fabrica.contato_cidade          AS cidade,
					tbl_posto_fabrica.contato_estado          AS estado,
					tbl_posto_fabrica.contato_fone_comercial    AS fone,
					tbl_posto_fabrica.contato_cep                AS cep,
					tbl_posto_fabrica.contato_email            AS email,
					tbl_posto_fabrica.codigo_posto                     ,
					tbl_tipo_posto.descricao                           ,
					tbl_posto_fabrica.pedido_faturado                  ,
					tbl_posto_fabrica.pedido_em_garantia               ,
					tbl_posto_fabrica.coleta_peca                      ,
					tbl_posto_fabrica.reembolso_peca_estoque           ,
					tbl_posto_fabrica.digita_os                        ,
					tbl_posto_fabrica.prestacao_servico                ,
					tbl_posto_fabrica.pedido_via_distribuidor          ,
					tbl_posto_fabrica.parametros_adicionais			   ,
					tbl_posto_fabrica.credenciamento                   ,
					tbl_posto_fabrica.categoria                        ,
					tbl_posto_fabrica.desconto
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica USING (posto)";

	if (strlen($pedido_em_garantia_finalidades_diversas)>0 AND $login_fabrica==1){
		$sql .= " JOIN tbl_posto_condicao ON tbl_posto_condicao.posto = tbl_posto_fabrica.posto AND tbl_posto_condicao.condicao = 62";
	}

	$sqlLinha =	"	SELECT linha, nome
					FROM tbl_linha
					WHERE fabrica = $login_fabrica
					ORDER BY nome;";
	$resLinha = pg_query($con,$sqlLinha);
	$filtrou_linha = 0;
	if (pg_numrows($resLinha) > 0) {
		for ($y = 0 ; $y < pg_numrows($resLinha) ; $y++){
			$aux_linha      = trim(pg_result($resLinha,$y,linha));
			$aux_linha_nome = trim(pg_result($resLinha,$y,nome));
			$aux            = trim($_POST["linha_".$aux_linha]);
			if ($aux == 't'){
				$filtrou_linha++;
				if ($filtrou_linha==1){
					$sql .= " JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto AND ( tbl_posto_linha.linha = $aux_linha";
				}else{
					$sql .= " OR tbl_posto_linha.linha = $aux_linha ";
				}
			}
		}
		if ($filtrou_linha>=1){
			$sql .= " ) ";
		}
	}

	$sql .= "
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			WHERE	tbl_posto_fabrica.fabrica = $login_fabrica ";

	if (strlen($cidade)>0){
		$sql .= " AND UPPER(tbl_posto_fabrica.contato_cidade) = UPPER('$cidade')";
	}

	if (strlen($estado)>0){
		$sql .= " AND tbl_posto_fabrica.contato_estado = '$estado'";
	}

	if (strlen($tipo_posto)>0){
		$sql .= " AND tbl_posto_fabrica.tipo_posto = $tipo_posto";
	}

	if (strlen($pedido_faturado)>0){
		$sql .= " AND tbl_posto_fabrica.pedido_faturado IS TRUE";
	}

	if (strlen($pedido_em_garantia)>0){
		$sql .= " AND tbl_posto_fabrica.pedido_em_garantia IS TRUE";
	}

	if (strlen($coleta_peca)>0){
		$sql .= " AND tbl_posto_fabrica.coleta_peca IS TRUE";
	}

	if (strlen($reembolso_peca_estoque)>0){
		$sql .= " AND tbl_posto_fabrica.reembolso_peca_estoque IS TRUE";
	}

	if (strlen($digita_os)>0){
		$sql .= " AND tbl_posto_fabrica.digita_os IS TRUE";
	}

	if (strlen($prestacao_servico)>0){
		$sql .= " AND tbl_posto_fabrica.prestacao_servico IS TRUE";
	}

	if (strlen($credenciados)>0){
		$sql .= " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
	}

	if($login_fabrica == 104) {
		foreach($categoriaPosto as $categoria) {
			if(!empty($_POST["$categoria"])) {
				$categorias[$categoria] = (!empty($_POST["$categoria"])) ? $_POST["$categoria"] : "";
			}
		}
		if(count($categorias) > 0) {
			$sql .= " and tbl_posto_fabrica.categoria in ( '" .implode("','",$categorias). "') ";
		}
	}
	if($login_fabrica==20){
		if( $login_admin == (590) OR $login_admin == (364) OR $login_admin == (588))
			$sql .= " AND 1=1 ";
		else
			$sql .= " AND tbl_posto.pais = 'BR'";
		$sql .=" ORDER BY tbl_posto.pais,tbl_posto_fabrica.credenciamento, tbl_posto.nome";
	}else{
		$sql .=" ORDER BY tbl_posto_fabrica.credenciamento, tbl_posto.nome";
	}

	$sql .= " {$limit}";

	$resConsulta = pg_query($con,$sql);

	$cont = pg_num_rows($resConsulta);

	//Trativa para gerar CSV ao invés de XLS
	if(in_array($login_fabrica, array(0))){
		if(isset($_POST['gerar_excel'])){
			$_POST['gerar_csv'] = "true";
			unset($_POST['gerar_excel']);
		}
	}

	/* Gera Excel */
	if(isset($_POST['gerar_excel'])){

		$data = date("d-m-Y-H:i");

		$filename = "relatorio-postos-consulta-{$data}.xls";

		$file = fopen("/tmp/{$filename}", "w");

		$titulo_coluna = "";
		$titulo_coluna .= ($login_fabrica == 20) ? "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("pais")."</th>" : "";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Código")."</th>";
		$titulo_coluna .= ($login_fabrica == 45) ? "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Razão Social")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("CNPJ")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Endereço")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Número")."</th> <th>".traduz("Bairro")."</th>" : "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Nome")."</th>";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Cidade")."</th>";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Estado")."</th>";
		$titulo_coluna .= ($login_fabrica == 45) ? "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("CEP")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Telefone")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Linha")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("E-mail")."</th> " : "";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Tipo")."</th>";
		$titulo_coluna .= ($login_fabrica == 104) ? "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Categoria")."</th>" : "";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Descredenciado")."</th>";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Pedido Faturado")."</th>";
		$titulo_coluna .= ($login_fabrica == 1) ? "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Coleta de Peças")."</th> <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Reembolso de Peça do Estoque")."</th> " : "";
		$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Digita OS")."</th>";

		if ($login_fabrica == 104) {
			$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Desconto")."</th>";
		}

		if($login_fabrica == 20){
			$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Data Nomeação")."</th>";
			$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Nome do Proprietario")."</th>";
			$titulo_coluna .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Código do Fornecedor")."</th>";
		}

		fwrite($file, "
			<style>.text{mso-number-format:'\@';}</style>
			<table border='1'>
				<thead>
					<tr>
						<th colspan='12' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
							".traduz("RELATÓRIO DE POSTOS")."
						</th>
					</tr>
					<tr>
						".
						$titulo_coluna
						."
					</tr>
				</thead>
				<tbody>
			");

		$res = $resConsulta;

		for ($i = 0 ; $i < $cont ; $i++) {

			if($login_fabrica == 20) $pais  = pg_result($res,$i,pais);
			$endereco                = pg_result($res,$i,endereco);
			$numero                  = pg_result($res,$i,numero);
			$bairro                  = pg_result($res,$i,bairro);
			$cep                     = pg_result($res,$i,cep);
			$telefone                = pg_result($res,$i,fone);
			$email                   = pg_result($res,$i,email);
			$cidade                  = pg_result($res,$i,cidade);
			$estado                  = pg_result($res,$i,estado);
			$posto                   = pg_result($res,$i,posto);
			$nome                    = pg_result($res,$i,nome);
			$cnpj                    = pg_result($res,$i,cnpj);
			$codigo_posto            = pg_result($res,$i,codigo_posto);
			$descricao               = pg_result($res,$i,descricao);
			$credenciamento          = pg_result($res,$i,credenciamento);
			$pedido_faturado         = pg_result($res,$i,pedido_faturado) ;
			$pedido_em_garantia      = pg_result($res,$i,pedido_em_garantia);
			$coleta_peca             = pg_result($res,$i,coleta_peca);
			$reembolso_peca_estoque  = pg_result($res,$i,reembolso_peca_estoque);
			$digita_os               = pg_result($res,$i,digita_os);
			$prestacao_servico       = pg_result($res,$i,prestacao_servico);
			$pedido_via_distribuidor = pg_result($res,$i,pedido_via_distribuidor);
			$categoria               = pg_result($res,$i,'categoria');

			if($login_fabrica == 20){//hd_chamado=2890291
                $xnomeacao_data = "";
                $xnome_propietario = "";
                $xcodigo_fornecedor = "";
                $parametros_adicionais = pg_result($res, $i,"parametros_adicionais");
                if(strlen($parametros_adicionais) > 0) {
                    $parametros_adicionais  = json_decode($parametros_adicionais, true);
                    $xnomeacao_data         = $parametros_adicionais['nomeacao_data'];
                    $xnomeacao_data         = str_replace(".", "/", $xnomeacao_data);
                    $xnome_propietario      = $parametros_adicionais['nome_propietario'];
                    $xcodigo_fornecedor     = $parametros_adicionais['codigo_fornecedor'];
                }
            }

			$sql2 = "SELECT nome from tbl_linha where linha in (select linha from tbl_posto_linha join tbl_linha using(linha) where fabrica=$login_fabrica and posto=$posto) order by nome";
			$resx = pg_query($con,$sql2);

			$linha = "";
			$linhas = "";

			for ($x = 0 ; $x < pg_numrows ($resx) ; $x++) {
				$linhas .= " ".pg_result($resx,$x,0).",";
			}

			$linhas = substr($linhas, 0, -1);

			$conteudo_excel = "";

			$conteudo_excel .= "<tr>";
			$conteudo_excel .= ($login_fabrica == 20) ? "<td>$pais</td>" : "";

			$conteudo_excel .= "<td class='text'>$codigo_posto</td>";
			$conteudo_excel .= ($login_fabrica == 45) ? "<td>$nome</td> <td>$cnpj</td> <td>$endereco</td> <td>$numero</td> <td>$bairro</td>" : "<td>$nome</td>";
			$conteudo_excel .= "<td>$cidade</td>";
			$conteudo_excel .= "<td>$estado</td>";
			$conteudo_excel .= ($login_fabrica == 45) ? "<td>$cep</td> <td>$telefone</td> <td>$linhas</td> <td>$email</td> " : "";
			$conteudo_excel .= "<td>$descricao</td>";
			$conteudo_excel .= ($login_fabrica == 104) ? "<td>$categoria</td>" : "";
			$conteudo_excel .= "<td>$credenciamento</td>";
			$conteudo_excel .= "<td>";
				if($pedido_faturado == "t"){ $conteudo_excel .= "SIM"; }
			$conteudo_excel .= "</td>";

			if($login_fabrica == 1) {
				$conteudo_excel .= "<td>";
					if ($coleta_peca == "t") $conteudo_excel .= "SIM";
				$conteudo_excel .= "</td>";
				$conteudo_excel .= "<td>";
					if ( $reembolso_peca_estoque == "t") $conteudo_excel .= "SIM";
				$conteudo_excel .= "</td>";
			}

			$conteudo_excel .= "<td>";
				if($digita_os == "t"){ $conteudo_excel .= "SIM"; }
			$conteudo_excel .= "</td>";

			if($login_fabrica == 20){//hd_chamado=2890291
            	$conteudo_excel .="<td class='tac'>".$xnomeacao_data."</td>";
            	$conteudo_excel .="<td>".utf8_decode($xnome_propietario)."</td>";
                $conteudo_excel .="<td class='tac'>".$xcodigo_fornecedor."</td>";
            }

            if ($login_fabrica == 104) {
            	$desconto = pg_result($res,$i,desconto);
            	$conteudo_excel .="<td class='tac'>$desconto</td>"; 
            }

			$conteudo_excel .= "</tr>";

			fwrite($file, $conteudo_excel);

		}

		fwrite($file, "
					<tr>
						<th colspan='12' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >".traduz("Total de"). " ".pg_num_rows($resSubmit)." ".traduz("registros")."</th>
					</tr>
				</tbody>
			</table>
		");

		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}

		exit;

	}

	if(isset($_POST['gerar_csv'])){
		$data = date("d-m-Y-H:i");

		$filename = "relatorio-postos-consulta-{$data}.csv";

		$file = fopen("/tmp/{$filename}", "w");



		$titulo_coluna .= traduz("Código").";".traduz("Nome").";".traduz("Cidade").";".traduz("Estado").";".traduz("Tipo").";".traduz("Descredenciado").";".traduz("Pedido Faturado").";".traduz("Digita OS")."\n";
		fwrite($file,$titulo_coluna);

		$res = $resConsulta;

		for ($i = 0 ; $i < $cont ; $i++) {

			if($login_fabrica == 20) $pais  = pg_result($res,$i,pais);
			$endereco                = pg_result($res,$i,endereco);
			$numero                  = pg_result($res,$i,numero);
			$bairro                  = pg_result($res,$i,bairro);
			$cep                     = pg_result($res,$i,cep);
			$telefone                = pg_result($res,$i,fone);
			$email                   = pg_result($res,$i,email);
			$cidade                  = pg_result($res,$i,cidade);
			$estado                  = pg_result($res,$i,estado);
			$posto                   = pg_result($res,$i,posto);
			$nome                    = pg_result($res,$i,nome);
			$cnpj                    = pg_result($res,$i,cnpj);
			$codigo_posto            = pg_result($res,$i,codigo_posto);
			$descricao               = pg_result($res,$i,descricao);
			$credenciamento          = pg_result($res,$i,credenciamento);
			$pedido_faturado         = pg_result($res,$i,pedido_faturado) ;
			$pedido_em_garantia      = pg_result($res,$i,pedido_em_garantia);
			$coleta_peca             = pg_result($res,$i,coleta_peca);
			$reembolso_peca_estoque  = pg_result($res,$i,reembolso_peca_estoque);
			$digita_os               = pg_result($res,$i,digita_os);
			$prestacao_servico       = pg_result($res,$i,prestacao_servico);
			$pedido_via_distribuidor = pg_result($res,$i,pedido_via_distribuidor);

			$sql2 = "SELECT nome from tbl_linha where linha in (select linha from tbl_posto_linha join tbl_linha using(linha) where fabrica=$login_fabrica and posto=$posto) order by nome";
			$resx = pg_query($con,$sql2);

			$linha = "";
			$linhas = "";

			$faturado = "";
			if($pedido_faturado == "t"){
				$faturado = traduz("SIM");
			}

			$dig_os = "";
			if($digita_os == "t"){
				$dig_os = traduz("SIM");
			}

			$linha = "$codigo_posto;$nome;$cidade;$estado;$descricao;$credenciamento;$faturado;$dig_os\n";
			fwrite($file,$linha);

		}


		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}

		exit;
	}

}

$layout_menu = "auditoria";
$title = traduz("CONSULTA DE POSTOS");

include "cabecalho_new.php";

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");

?>

<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio" align='center' class='form-search form-inline tc_formulario'>

		<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
		<br/>

		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='estado'><?=traduz("Estado")?></label>
					<div class='controls controls-row'>
						<select id="estado" name="estado" class="span12 addressState">
                            <option value="" ><?=traduz("Selecione")?></option>
                            <?php
                            #O $array_estados() está no arquivo funcoes.php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cidade'><?=traduz("Cidade")?></label>
					<div class='controls controls-row'>
						<select id="cidade" name="cidade" class="span12 addressCity">
                            <option value="" ><?=traduz("Selecione")?></option>
                            <?php
                                if (strlen($estado) > 0) {
                                    $sql = "SELECT DISTINCT * FROM (
                                            SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
                                                UNION (
                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == $cidade) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                }
                            ?>
                        </select>
						<!-- <input type='text' name='cidade' value='<?=$cidade?>' class='span12'> -->
					</div>
				</div>
			</div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='tipo_posto'><?=traduz("Tipo Posto")?></label>
					<div class='controls controls-row'>

						<?
							$sqlTipoPosto =	"SELECT tipo_posto,descricao
									FROM tbl_tipo_posto
									WHERE fabrica = $login_fabrica AND ativo
									ORDER BY descricao;";
							$resTipoPosto = pg_query($con,$sqlTipoPosto);
							if (pg_numrows($resTipoPosto) > 0) {
								echo "<select name='tipo_posto' class='span12'>";
								echo "<option value=''></option>";
								for ($x = 0 ; $x < pg_numrows($resTipoPosto) ; $x++){
									$aux_tipo_posto = trim(pg_result($resTipoPosto,$x,tipo_posto));
									$aux_descricao  = trim(pg_result($resTipoPosto,$x,descricao));
									echo "<option value='$aux_tipo_posto'";
									if ($tipo_posto == $aux_tipo_posto) echo " selected";
									echo ">$aux_descricao</option>";
								}
								echo "</select>";
							}
						?>

					</div>
				</div>
			</div>
			<div class='span1'></div>
		</div>

		<div class="container">
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<label class='control-label'><?=traduz("Posto pode digitar")?></label><br />
					<label class="checkbox">
						<input type='checkbox' value='t' name='pedido_faturado' <?if ($pedido_faturado =='t') echo " CHECKED ";?>>
						<?=traduz("PEDIDO FATURADO")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='pedido_em_manual' <?if ($pedido_em_manual =='t') echo " CHECKED ";?>>
						<?=traduz("PEDIDO EM GARANTIA (Manual)")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='pedido_em_garantia_finalidades_diversas' <?if ($pedido_em_garantia_finalidades_diversas =='t') echo " CHECKED ";?>>
						<?=traduz("PEDIDO DE GARANTIA ( FINALIDADES DIVERSAS)")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='coleta_peca' <?if ($coleta_peca =='t') echo " CHECKED ";?>>
						<?=traduz("COLETA DE PEÇAS")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='reembolso_peca_estoque' <?if ($reembolso_peca_estoque =='t') echo " CHECKED ";?>>
						<?=traduz("REEMBOLSO DE PEÇA DO ESTOQUE (GARANTIA AUTOMÁTICA)")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='digita_os' <?if ($digita_os =='t') echo " CHECKED ";?>>
						<?=traduz("DIGITA OS")?>
					</label><br />

					<label class="checkbox">
						<input type='checkbox' value='t' name='prestacao_servico' <?if ($prestacao_servico =='t') echo " CHECKED ";?>>
						<?=traduz("PRESTAÇÃO DE SERVIÇO")?>
					</label><br />
				</div>
				<div class="span1"></div>
			</div>
		</div>
		<br />
<?php if($login_fabrica == 104 ) { ?>
		<div class="container">
			<div class='row-fluid'>
				<div class="span1"></div>
				<div class="span10">
					<table class="table table-fixed">
						<thead>
							<tr>
                                <th colspan="3" class="tac"><?=traduz("Categoria do Posto")?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<?
									foreach($categoriaPosto as $categoria) {
										echo "<td><input type='checkbox' name='$categoria' value='$categoria' ";
										echo ($categoria == $categorias[$categoria]) ? " checked " : "";
										echo " >$categoria</td>";
									}
								?>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="span1"></div>
			</div>
		</div>
<? } ?>
		<div class="container">
			<div class='row-fluid'>
				<div class="span1"></div>
				<div class="span10">
					<table class="table table-fixed">
						<thead>
							<tr>
								<?php
                                if ($login_fabrica == 117) {
                                        $joinElgin = "JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                  JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha";
                                        ?>
                                        <th colspan="3" class="tac"><?=traduz("Atende as Macro - Famílias")?></th>
                                <?php
                                } else { ?>
                                        <th colspan="3" class="tac"><?=traduz("Atende as linhas")?></th>
                                <?php
                                }
                                ?>
							</tr>
						</thead>
						<tbody>
							<tr>

								<?

									$sqlLinha =	"SELECT DISTINCT tbl_linha.linha, tbl_linha.nome
											FROM tbl_linha
											$joinElgin
											WHERE tbl_linha.fabrica = $login_fabrica
											AND tbl_linha.ativo
											ORDER BY tbl_linha.nome;";
									$resLinha = pg_query($con,$sqlLinha);
									if (pg_numrows($resLinha) > 0) {
										for ($x = 0 ; $x < pg_numrows($resLinha) ; $x++){
											$aux_linha      = trim(pg_result($resLinha,$x,linha));
											$aux_linha_nome = trim(pg_result($resLinha,$x,nome));
											$checado = "";
											if ($_POST['linha_'.$aux_linha]=='t'){
												$checado = " CHECKED ";
											}


											if($x > 0 AND $x%3 == 0){
												echo "</tr><tr>";
											}


											echo "<td><input type='checkbox' name='linha_".$aux_linha."' value='t' ".$checado." > ".$aux_linha_nome."</td>";
										}
									}
								?>
							</tr>
							<tr>
								<td>
									<?
									if($login_fabrica != 45){
										?>
										<input type='checkbox' value='t' name='credenciados' <?php if ($credenciados =='t') echo " CHECKED ";?>> <strong><?=traduz("APENAS CREDENCIADOS")?></strong><br />
										<?php
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="span1"></div>
			</div>
		</div>
		<p>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz("Pesquisar")?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
		<br />
</form>
</div>
<br>
<?
if ($_POST["btn_acao"] == "submit") {

	$res = $resConsulta;

	if ($cont > 0) {

		if($cont > 50){
			?>
			<script>

				$(function(){
					$.dataTableLoad({
						table: "#resultado_posto"
					});
				});

			</script>
			<?php
		}

		?>
		<table id="resultado_posto" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna'>
					<?php echo ($login_fabrica == 20) ? "<th>".traduz("pais")."</th>" : ""; ?>
					<th><?=traduz("Código")?></th>
					<?php echo ($login_fabrica == 45) ? "<th>".traduz("Razão Social")."</th> <th>".traduz("CNPJ")."</th> <th>".traduz("Endereço")."</th> <th>".traduz("Número")."</th> <th>".traduz("Bairro")."</th>" : "<th>".traduz("Nome")."</th>"; ?>
					<th><?=traduz("Cidade")?></th>
					<th><?=traduz("Estado")?></th>
					<?php echo ($login_fabrica == 45) ? "<th>".traduz("CEP")."</th> <th>".traduz("Telefone")."</th> <th>".traduz("Linha")."</th> <th>".traduz("E-mail")."</th> " : ""; ?>
					<th><?=traduz("Tipo")?></th>
					<?php echo ($login_fabrica == 104) ? "<th>".traduz("Categoria")."</th> " : ""; ?>
					<th><?=traduz("Descredenciado")?></th>
					<th><?=traduz("Pedido Faturado")?></th>
					<!-- <th>Pedido em Garantia</th> -->
					<?php echo ($login_fabrica == 1) ? "<th>".traduz("Coleta de Peças")."</th> <th>".traduz("Reembolso de Peça do Estoque")."</th> " : ""; ?>
					<th><?=traduz("Digita OS")?></th>
					<?php if($login_fabrica == 20){//hd_chamado=2890291 ?>
					<th><?=traduz("Data Nomeação")?></th>
					<th><?=traduz("Nome do Proprietario")?></th>
					<th><?=traduz("Código do Fornecedor")?></th>
					<? } ?>
					<!-- <th>Prestação de Serviço</th>
					<th>Pedido via Distribuidor</th> -->
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

					if($login_fabrica == 20) $pais  = pg_result($res,$i,'pais');
					$endereco                = pg_result($res,$i,'endereco');
					$numero                  = pg_result($res,$i,'numero');
					$bairro                  = pg_result($res,$i,'bairro');
					$cep                     = pg_result($res,$i,'cep');
					$telefone                = pg_result($res,$i,'fone');
					$email                   = pg_result($res,$i,'email');
					$cidade                  = pg_result($res,$i,'cidade');
					$estado                  = pg_result($res,$i,'estado');
					$posto                   = pg_result($res,$i,'posto');
					$nome                    = pg_result($res,$i,'nome');
					$cnpj                    = pg_result($res,$i,'cnpj');
					$codigo_posto            = pg_result($res,$i,'codigo_posto');
					$descricao               = pg_result($res,$i,'descricao');
					$credenciamento          = pg_result($res,$i,'credenciamento');
					$pedido_faturado         = pg_result($res,$i,'pedido_faturado') ;
					$pedido_em_garantia      = pg_result($res,$i,'pedido_em_garantia');
					$coleta_peca             = pg_result($res,$i,'coleta_peca');
					$reembolso_peca_estoque  = pg_result($res,$i,'reembolso_peca_estoque');
					$digita_os               = pg_result($res,$i,'digita_os');
					$prestacao_servico       = pg_result($res,$i,'prestacao_servico');
					$pedido_via_distribuidor = pg_result($res,$i,'pedido_via_distribuidor');
					$categoria               = pg_result($res,$i,'categoria');

					if($login_fabrica == 20){//hd_chamado=2890291
						$xnomeacao_data = "";
						$xnome_propietario = "";
						$xcodigo_fornecedor = "";
						$parametros_adicionais = pg_result($res, $i,"parametros_adicionais");
			            if(strlen($parametros_adicionais) > 0) {
			                $parametros_adicionais 	= json_decode($parametros_adicionais, true);
			                $xnomeacao_data        	= $parametros_adicionais['nomeacao_data'];
			                $xnomeacao_data 		= str_replace(".", "/", $xnomeacao_data);
			                $xnome_propietario      = $parametros_adicionais['nome_propietario'];
			                $xcodigo_fornecedor     = $parametros_adicionais['codigo_fornecedor'];
			            }
			        }

					$sql2 = "SELECT nome from tbl_linha where linha in (select linha from tbl_posto_linha join tbl_linha using(linha) where fabrica=$login_fabrica and posto=$posto) order by nome";
					$resx = pg_query($con,$sql2);

					$linha = "";
					$linhas = "";

					for ($x = 0 ; $x < pg_numrows ($resx) ; $x++) {
						$linhas .= " ".pg_result($resx,$x,0).",";
					}

					$linhas = substr($linhas, 0, -1);

					?>

					<tr>

						<?php echo ($login_fabrica == 20) ? "<td>$pais</td>" : ""; ?>
						<td><?=$codigo_posto?></td>
						<?php echo ($login_fabrica == 45) ? "<td>$nome</td> <td>$cnpj</td> <td>$endereco</td> <td>$numero</td> <td>$bairro</td>" : "<td><a href='posto_cadastro.php?posto=$posto' target='_blank'>$nome</a></td>"; ?>
						<td><?=$cidade?></td>
						<td class="tac"><?=$estado?></td>
						<?php echo ($login_fabrica == 45) ? "<td>$cep</td> <td>$telefone</td> <td>$linhas</td> <td>$email</td> " : ""; ?>
						<td><?=$descricao?></td>
						<?php echo ($login_fabrica == 104) ? "<td>$categoria</td> " : ""; ?>
						<td><?=$credenciamento?></td>
						<td class="tac"><?php if($pedido_faturado == "t"){ echo '<img name="visivel" src="imagens/status_verde.png">'; } ?></td>
						<!-- <td class="tac"><?php if($pedido_em_garantia == "t"){ echo '<img name="visivel" src="imagens/status_verde.png">'; } ?></td> -->

						<?php

							if($login_fabrica == 1) {
								echo "<td>";
									if ($coleta_peca == "t") echo '<img name="visivel" src="imagens/status_verde.png">';
								echo "</td>";
								echo "<td>";
									if ( $reembolso_peca_estoque == "t") echo '<img name="visivel" src="imagens/status_verde.png">';
								echo "</td>";
							}

						?>

						<td class="tac"><?php if($digita_os == "t"){ echo '<img name="visivel" src="imagens/status_verde.png">'; } ?></td>
						<!-- <td class="tac"><?php if($prestacao_servico == "t"){ echo '<img name="visivel" src="imagens/status_verde.png">'; } ?></td>
						<td class="tac"><?php if($pedido_via_distribuidor == "t"){ echo '<img name="visivel" src="imagens/status_verde.png">'; } ?></td> -->

						<?php if($login_fabrica == 20){//hd_chamado=2890291 ?>
							<td class='tac'><?=$xnomeacao_data?></td>
							<td><?=utf8_decode($xnome_propietario)?></td>
							<td class='tac'><?=$xcodigo_fornecedor?></td>
						<?php } ?>
					</tr>

				<?php
				}

				?>
			</tbody>
		</table>

		<?php $jsonPOST = excelPostToJson($_POST); ?>

		<br />

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?=traduz("Gerar Arquivo Excel")?></span>
		</div>

		<?php

	}else{
		echo '
			<div class="container">
				<div class="alert">
					<h4>Nenhum resultado encontrado</h4>
				</div>
			</div>';
	}
}?>
<script language='javascript' src='address_components.js'></script>
<?php
include "rodape.php";
?>