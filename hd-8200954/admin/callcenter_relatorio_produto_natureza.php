<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';
include 'funcoes.php';

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

$env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];

	if(in_array($login_fabrica, array(88,101,162,169,170,171))){
        $origem = $_POST["origem"];
    }

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = traduz("Data Inválida");
	}

	if(strlen($produto_referencia) > 0 and strlen($produto_descricao) == 0) {
		$msg_erro = traduz("Preencha a descrição do produto");
	} else if(strlen($produto_referencia) == 0 and strlen($produto_descricao) > 0) {
		$msg_erro = traduz("Preencha a referência do produto");
	}

	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data Inválida");
	}

	if($xdata_inicial>$xdata_final)
		$msg_erro = traduz("Data Inválida");

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		$cond_3 = " tbl_hd_chamado.status = '$status'  ";
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

    if(in_array($login_fabrica, array(88,101,162,169,170,171)) and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }

    if($login_fabrica == 74){
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

	$sql_produtos = "
			select	distinct tbl_hd_chamado_extra.produto,
					tbl_produto.referencia,
					tbl_produto.descricao
			from tbl_hd_chamado
			join tbl_hd_chamado_extra using(hd_chamado)
			join tbl_produto using(produto)
			WHERE fabrica_responsavel =  $login_fabrica
							AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
							AND $cond_1
							AND $cond_2
							AND $cond_3
							AND $cond_4
							$cond_admin_fale_conosco
							$cond_origem
			AND tbl_hd_chamado.status <> 'Cancelado' order by descricao
	";
	$sql_categoria = "
			select	distinct tbl_hd_chamado.categoria
			from tbl_hd_chamado
			join tbl_hd_chamado_extra using(hd_chamado)
			join tbl_produto using(produto)
			WHERE fabrica_responsavel =  $login_fabrica
							AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
							AND $cond_1
							AND $cond_2
							AND $cond_3
							AND $cond_4
							$cond_admin_fale_conosco
							$cond_origem
			AND tbl_hd_chamado.status <> 'Cancelado'
			order by categoria desc

	";

	//echo "$sql_produtos <BR><BR><BR> $sql_categoria";
	//#echo nl2br($sql_produtos) ;
	//exit;
	$res_produtos = pg_exec($con,$sql_produtos);

}
$layout_menu = "callcenter";
$title = traduz("RELATÓRIO PRODUTO X NATUREZA");

include "cabecalho_new.php";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
//array de opção de origem
$origemOptions = array('Telefone' => 'Telefone', 'Email' => 'Email' );
switch ($login_fabrica) {
    case 2:
        $origemOptions = array_merge($origemOptions,array(
            '0800'      => 'Atendimento 0800',
            '9166'      => 'Atendimento 9166',
            'Outros'    => traduz('Outros')
        ));
        break;
    case 15:
        $origemOptions = array_merge($origemOptions,array(
            'revenda'   => traduz('Revenda'),
            'reclame'   => 'Reclame Aqui',
            'redes'     => 'Facebook',
            'gov'       => 'Consumidor Gov.',
            'globo'     => 'O Globo',
            'fale'      => traduz('Fale Conosco')
        ));
        break;
    case 59:
        $origemOptions = array_merge($origemOptions,array(
            'Chat'              => 'Chat',
            'Facebook'          => 'Facebook',
            'LASA'              => 'LASA',
            'NAJ'               => 'NAJ',
            'ReclameAqui'       => 'Reclame Aqui',
            'Relacionamento'    => traduz('Relacionamento')
        ));
        break;
    case 88:
        $origemOptions = array_merge($origemOptions,array(
            'whatsApp'      => 'WhatsApp',
            'facebook'      => 'Facebook',
            'reclame_aqui'  => 'Reclame Aqui',
            'procon'        => 'Procon',
            'jec'           => 'JEC/Justiça Comum',
            'auditoria'     => traduz('Auditoria')
        ));
        break;
    case 101:
        $origemOptions = array_merge($origemOptions,array(
            'whatsApp'      => 'WhatsApp',
            'facebook'      => 'Facebook',
            'reclame_aqui'  => 'Reclame Aqui',
            'procon'        => 'Procon',
            'jec'           => 'JEC/Justiça Comum',
            'ecommerce'     => "E-Commerce"
        ));
        break;
    case 124:
        $origemOptions['faleconosco'] = 'Fale Conosco';
        break;
    case 136:
        $origemOptions = array_merge($origemOptions,array(
            'whatsApp' => 'WhatsApp',
            'facebook' => 'Facebook'
        ));
        break;
    case 151:
        $origemOptions = array_merge($origemOptions,array(
            'reclame'       => 'Reclame Aqui',
            'procon'        => 'Procon',
            'redes'         => traduz('Redes Sociais'),
            'patrulha'      => traduz('Patrulha do Consumidor'),
            'faleconosco'   => traduz('Fale Conosco'),
            'midia'         => traduz('Mídia')
        ));
        break;
    case 156:
        $origemOptions = array_merge($origemOptions,array(
            'Chat'                     => 'Chat',
            'Skype'                    => 'Skype',
            'Email'                    => 'Email',
            '0800'                     => '0800',
            'Combinado com Consumidor' => traduz('Combinado com Consumidor'),
            'Revenda'                  => traduz('Revenda'),
            'Posto'                    => traduz('Posto'),
            'Software House'           => traduz('Software House')
        ));
        break;
    case 162:
        $origemOptions = array_merge($origemOptions,array(
            'Chat'      => "Chat",
            'CIP'       => "CIP",
            'Juizado'   => "Juizado",
            'Procon'    => "Procon",
            'Midias Sociais' => traduz("Midias Sociais") //HD-3352176
        ));
        break;
    case 171://hd-3624967 - fputti
        $origemOptions = array_merge($origemOptions,array(
            'facebook'   => 'Facebook',
            'Falecom'    => "Falecom",
            'presencial' => traduz('Presencial'),
            'reclame'    => 'Reclame Aqui',
        ));
        break;
}

	if($login_fabrica != 88){
		ksort($origemOptions, SORT_STRING);
	}

?>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,origem){
janela = window.open("callcenter_relatorio_produto_natureza_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+"&origem="+origem+"&status="+status , "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" charset="utf-8">
	$(function(){
		$.autocompleteLoad(Array("produto"));

		$("#data_inicial").datepicker().mask("99/99/9999");
		$("#data_final").datepicker().mask("99/99/9999");
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_produto(retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>

<script language='javascript' src='../ajax.js'></script>

<? if(strlen($msg_erro)>0){ ?>
	<div class='alert alert-danger'><h4><? echo $msg_erro; ?></h4></div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<FORM class='form-search form-inline tc_formulario' name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
	<div class='titulo_tabela'>
		<?=traduz('Parâmetros de Pesquisa')?>
	</div>
	<br />
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : "" ?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
						<div class='controls controls-row'>
							<h5 class="asteristico">*</h5>
							<input class="span5" type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial;  ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Data Inválida") ? "error" : "" ?>'>
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
						<div class='controls controls-row'>
							<h5 class="asteristico">*</h5>
							<input class="span5" type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final;?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Preencha a referência do produto") ? "error" : ""?>'>
					<label class='control-label'><?=traduz('Ref. Produto')?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" id="produto_referencia" name="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>" >
								<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
							</div>
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=($msg_erro == "Preencha a descrição do produto") ? "error" : ""?>'>
					<label class='control-label'><?=traduz('Descrição Produto')?></label>
						<div class='controls controls-row input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" size="12" class='frm' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'><?php echo ($login_fabrica == 101) ? traduz("Natureza / Motivo de Contato") : traduz("Natureza") ?></label>
						<div class='controls controls-row input-append'>
							<select name='natureza_chamado'>
								<option value=''></option>

								<?PHP
									//HD39566
									$sqlx = "SELECT nome            ,
													descricao
											FROM tbl_natureza
											WHERE fabrica=$login_fabrica
											AND ativo = 't'
											ORDER BY nome";

									$resx = pg_exec($con,$sqlx);
										if(pg_numrows($resx)>0){
											for($y=0;pg_numrows($resx)>$y;$y++){
												$nome     = trim(pg_result($resx,$y,nome));
												$descricao     = trim(pg_result($resx,$y,descricao));
												echo $nome;
												echo "<option value='$nome'";
													if($natureza_chamado == $nome) {
														echo "selected";
													}
												echo ">$descricao</option>";
											}

										}
								?>

								</select>
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'><?=traduz('Status')?></label>
						<div class='controls controls-row'>
							<select name="status">
								<option value=''></option>
								<?
									$sql = "select distinct status from tbl_hd_status where fabrica = $login_fabrica order by status";
									$res = pg_exec($con,$sql);
									if(pg_numrows($res)>0){
										for($x=0;pg_numrows($res)>$x;$x++){
											$status_option = pg_result($res,$x,status);

											$selected = ($status_option == $status) ? "SELECTED" : "";

											echo "<option value='$status_option' $selected>$status_option</option>";

										}

									}
								?>
							</select>
						</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>

		<?
		/*O select estava aparecendo para todas as fábricas,
		porém só aceitava essa condição para 3 fábricas. Sendo assim,
		Não havia motivo para aparecer em outras fábricas */
		if(in_array($login_fabrica, array(88,101,162,169,170,171))) { ?>
		<div class="row-fluid">
				<div class="span2"></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label'><?=traduz('Origem')?></label>
							<div class='controls controls-row'>
								<select name="origem" id="xorigem">
									<option value=''><?=traduz('Escolha')?></option>
									<?php
										if(in_array($login_fabrica, array(169,170))){
											$sql = "SELECT hd_chamado_origem, descricao
								    					FROM tbl_hd_chamado_origem
								    					WHERE fabrica = $login_fabrica
								    					AND ativo IS TRUE ";
								    		$res = pg_query($con, $sql);

											foreach (pg_fetch_all($res) as $key) {
												$selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
											?>
												<option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
													<?php echo $key['descricao']?>
												</option>
											<?php
											}
										}else{
											foreach($origemOptions as $chave => $origemDados){
												$selected = ($_POST["origem"] == $chave) ? "selected" : "";
												echo "<option value='$chave' {$selected} >$origemDados</option>";
											}
										}

									?>
								</select>
							</div>
					</div>
				</div>
		</div>
		<? } ?>
			<br />
			<input class="btn" type='submit' style="cursor:pointer" name='btn_acao' value='<?=traduz('Consultar')?>'>
			<br /><br />
</FORM>
<br />
<?

if(strlen($btn_acao)>0){

	if(strlen($msg_erro)==0){

		if(pg_numrows($res_produtos)>0){
			echo "<table id='tabela_produto' class='table table-striped table-bordered table-fixed'>";
			echo "<thead><TR class='titulo_coluna'>\n";
			echo "<th>".traduz('Referência')."</th>\n";
			echo "<th>".traduz('Descrição')."</th>\n";

			$res_categoria = pg_exec($con,$sql_categoria);
			if(pg_numrows($res_categoria)>0){
				for($i=0;pg_numrows($res_categoria)>$i;$i++){
					$categoria = trim(pg_result($res_categoria,$i,categoria));
					echo "<th>";
					if($categoria == 'troca_produto'){
						echo traduz("Troca do Produto");
					}elseif($categoria == 'reclamacao_produto'){
						echo traduz("Reclamação Produto");
					}elseif($categoria == 'garantia_adicional'){
						echo traduz("Garantia Adicional");
					}elseif($categoria == 'onde_comprar'){
						echo traduz("Onde Comprar");
					}elseif($categoria == 'procon'){
						echo traduz("Procon");
					}elseif($categoria == 'informacoes'){
                        echo traduz("Informações");
                    }elseif($categoria == 'pr_reclamacao_at'){
                        echo traduz("Reclamação da Assist. Técn");
                    }else{
						echo "$categoria";
					}
					echo "</th>\n";
			}
			echo "</TR ></thead>";
			if(pg_numrows($res_produtos)>5){
				$qtde_grafico = 5;
			}else{
				$qtde_grafico = pg_numrows($res_produtos);
			}
			echo "<tbody>";
			for($y=0;$y<pg_numrows($res_produtos);$y++){
				$produto = pg_result($res_produtos,$y,produto);
				$referencia    = pg_result($res_produtos,$y,referencia);
				$descricao    = pg_result($res_produtos,$y,descricao);

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap><a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$natureza_chamado','$status','$xperiodo', '$origem')\">$referencia</a></TD>\n";
				echo "<TD align='left' nowrap>$descricao</TD>\n";

				$res_categoria = pg_exec($con,$sql_categoria);
				if(pg_numrows($res_categoria)>0){
					for($i=0;pg_numrows($res_categoria)>$i;$i++){

						if($login_fabrica == 74){
				            $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
				        }

						$categoria = pg_result($res_categoria,$i,categoria);
						$sql_count = "
						SELECT COUNT(hd_chamado) as qtde
							FROM tbl_hd_chamado
							JOIN tbl_hd_chamado_extra using(hd_chamado)
							WHERE fabrica_responsavel =  $login_fabrica
							AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
							AND $cond_2
							AND $cond_3
							AND $cond_4
							$cond_admin_fale_conosco
							$cond_origem
							and tbl_hd_chamado_extra.produto = $produto
							and tbl_hd_chamado.categoria = '$categoria'	";
						$res_count = pg_exec($con,$sql_count);
						if(pg_numrows($res_count)>0){
							$qtde =  pg_result($res_count,0,qtde);
							$grafico_produto[] = $categoria ;
							$grafico_qtde[] = $qtde;
						}
						echo "<TD class='tac' align='center' nowrap>";
						$total_categoria += $qtde;
						if($qtde > 0 ){
							echo "<a href=\"javascript: 	AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$categoria','$status','$xperiodo', '$origem')\">$qtde</a>";
						}else{
							echo "$qtde";
						}
							echo "</TD>\n";
					}

				}
				echo "</TR >\n";
			}
			echo "<tbody>";
			echo "<tfoot>";
			echo "<tr class='titulo_coluna'> <td colspan='2'>".traduz('Total')."</td>";
			for($i=0;pg_numrows($res_categoria)>$i;$i++){
				$categoria = pg_result($res_categoria,$i,categoria);

				echo "<td align='center'>".$total_categoria."</td>";

			}
			echo "</tr>";
			echo "</tfoot>";
			echo "</table>";
			}

		}
	else{
		echo "<div class='alert alert-warning container'><h4>".traduz('Não foram encontrados resultados para esta pesquisa!')."</h4></div>";
	}

	}
}

?>
<script>
	$.dataTableLoad({ table: "#tabela_produto" });
</script>
<p>

<? include "rodape.php" ?>
