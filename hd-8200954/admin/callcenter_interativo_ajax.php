<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

//ajax=true&busca_cliente=tue&busca=" + busca + "&tipo="
$ajax   = $_GET['ajax'];
$origem = $_GET['origem'];
$busca_cliente = $_GET['busca_cliente'];

if(strlen($ajax)>0){
	if(strlen($busca_cliente)>0 and $tipo <> "atendimento"){
		$busca = strtoupper ($_GET['busca']);
		$tipo  = $_GET['tipo'];
		$cond_1 = " 1=1 ";
		if($tipo=="nome"){
			$cond_1 = " upper(tbl_hd_chamado_extra.nome) like '%$busca%' ";
			$xcond_1 = " upper(tbl_revenda.nome) like '%$busca%' ";
		}
		if($tipo=="cpf"){ // HD 71414
			$busca            = str_replace("/","",$busca);
			$busca            = str_replace("-","",$busca);
			$busca            = str_replace(".","",$busca);
			$busca            = str_replace(",","",$busca);
			$cond_1 = " replace(replace(tbl_hd_chamado_extra.cpf,'.',''),'-','') = '$busca' ";
			$xcond_1 = " tbl_revenda.cnpj like '%$busca%' ";
		}
		if($tipo=="telefone"){
			$cond_1 = " tbl_hd_chamado_extra.fone = '$busca' ";
			$xcond_1 = " tbl_revenda.fone like '%$busca%' ";
		}
		if($tipo=="cep"){
			$cond_1 = " tbl_hd_chamado_extra.cep = '$busca' ";
			$xcond_1 = " tbl_revenda.cep like '%$busca%' ";
		}
		if($tipo=="novo"){
			$cond_1 = " 1=2 ";
		}

		if(strlen($busca)>0 and $tipo<>"novo"){
			$sql = "SELECT tbl_hd_chamado_extra.cliente      ,
							tbl_hd_chamado_extra.nome        ,
							tbl_hd_chamado_extra.endereco    ,
							tbl_hd_chamado_extra.numero      ,
							tbl_hd_chamado_extra.complemento ,
							tbl_hd_chamado_extra.bairro      ,
							tbl_hd_chamado_extra.cep         ,
							tbl_hd_chamado_extra.cidade      ,
							tbl_hd_chamado_extra.fone        ,
							tbl_hd_chamado_extra.cpf         ,
							tbl_hd_chamado_extra.rg          ,
							tbl_hd_chamado_extra.email       ,
							tbl_cidade.nome  as cidade_nome       ,
							tbl_cidade.estado
					FROM tbl_hd_chamado_extra
					JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
					WHERE $cond_1 limit 1			";
			$sql = "SELECT	id              ,
					nome            ,
					endereco        ,
					numero          ,
					complemento     ,
					bairro          ,
					cep             ,
					cidade          ,
					fone            ,
					cpf_cnpj        ,
					rg              ,
					email           ,
					nome_cidade     ,
					estado          ,
					tipo
				FROM (
						(
						SELECT tbl_hd_chamado_extra.hd_chamado as id ,
								tbl_hd_chamado_extra.nome         ,
								tbl_hd_chamado_extra.endereco     ,
								tbl_hd_chamado_extra.numero       ,
								tbl_hd_chamado_extra.complemento  ,
								tbl_hd_chamado_extra.bairro       ,
								tbl_hd_chamado_extra.cep          ,
								tbl_hd_chamado_extra.cidade       ,
								tbl_hd_chamado_extra.fone         ,
								tbl_hd_chamado_extra.cpf as cpf_cnpj ,
								tbl_hd_chamado_extra.rg           ,
								tbl_hd_chamado_extra.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'C' as tipo
						FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado using (hd_chamado)
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE $cond_1
						AND   fabrica_responsavel= $login_fabrica
						)union(
						SELECT tbl_revenda.revenda as id ,
								tbl_revenda.nome         ,
								tbl_revenda.endereco     ,
								tbl_revenda.numero       ,
								tbl_revenda.complemento  ,
								tbl_revenda.bairro       ,
								tbl_revenda.cep          ,
								tbl_revenda.cidade       ,
								tbl_revenda.fone         ,
								tbl_revenda.cnpj  as cpf_cnpj,
								'' as rg                 ,
								tbl_revenda.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'R' as tipo
						FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE $xcond_1
						)
					) as X";

			$res = pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				$cliente     = pg_fetch_result($res,0,id);
				$consumidor_nome        = pg_fetch_result($res,0,nome);
				$consumidor_endereco    = pg_fetch_result($res,0,endereco);
				$consumidor_numero      = pg_fetch_result($res,0,numero);
				$consumidor_complemento = pg_fetch_result($res,0,complemento);
				$consumidor_bairro      = pg_fetch_result($res,0,bairro);
				$consumidor_cep         = pg_fetch_result($res,0,cep);
				$cidade                 = pg_fetch_result($res,0,cidade);
				$consumidor_fone        = pg_fetch_result($res,0,fone);
				$consumidor_cpf         = pg_fetch_result($res,0,cpf_cnpj);
				$consumidor_rg          = pg_fetch_result($res,0,rg);
				$consumidor_email       = pg_fetch_result($res,0,email);
				$consumidor_estado      = pg_fetch_result($res,0,estado);
				$consumidor_cidade      = pg_fetch_result($res,0,nome_cidade);
				$tipo                   = pg_fetch_result($res,0,tipo);

			}else{
				//echo "nao encontrado";
			}
		}
?>

<script>
<?PHP if ($login_fabrica == 3) { ?>
	window.onload = function foco(){
	document.getElementById("consumidor_nome").focus();
	}
<? } ?>
</script>
			<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<? if ($login_fabrica == 52) { ?>
			<tr>
				<td align='left'><strong>Cliente Fricon:</strong>
					<input type='hidden' name='cliente_admin' id='cliente_admin' value="<? echo $cliente_admin; ?>">
					<input name="cliente_nome_admin" id="cliente_nome_admin" value='<?echo $nome_cliente_admin ;?>' class='input_req' type="text" size="35" maxlength="50">
				</td>
			<tr>
			<?
			}
			?>
			<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
			<input name="consumidor_nome" id="consumidor_nome"  value='<?echo $consumidor_nome ;?>' class="input" type="text" size="35" maxlength="500"> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'>

            </td>
            <td align='left'><strong>Cpf:</strong></td>
            <td align='left'>
			<input name="consumidor_cpf" id="consumidor_cpf" value='<?echo $consumidor_cpf ;?>' class="input" type="text" size="14" maxlength="14">
			<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, "cpf")'>
			<input name="cliente"  class="input" value='<?echo $cliente ;?>' type="hidden">
			</td>
            <td align='left'><strong>Rg:</strong></td>
            <td align='left'>
			<input name="consumidor_rg"  value='<?echo $consumidor_rg ;?>'  class="input" type="text" size="14" maxlength="14">
            </td>
          </tr>
          <tr>
            <td align='left'><strong>E-mail:</strong></td>
            <td align='left'>
			<input name="consumidor_email"   value='<?echo $consumidor_email ;?>' class="input" type="text" size="40" maxlength="500">
			</td>
            <td align='left'><strong>Telefone:</strong></td>
            <td align='left'>
			<input name="consumidor_fone" id='consumidor_fone' value='<?echo $consumidor_fone ;?>'  class="input"  type="text" size="18" maxlength="16">
			</td>
            <td align='left'><strong>Cep:</strong></td>
            <td align='left'>
			<input name="consumidor_cep" id='consumidor_cep' value='<?echo $consumidor_cep ;?>' class="input" type="text" size="14" maxlength="10" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;">
			</td>
          </tr>
          <tr>
            <td align='left'><strong>Endereço:</strong></td>
            <td align='left'>
			<input name="consumidor_endereco"  value='<?echo $consumidor_endereco ;?>' class="input" type="text" size="40" maxlength="500">
			</td>
            <td align='left'><strong>Número:</strong></td>
            <td align='left'>
			<input name="consumidor_numero"  value='<?echo $consumidor_numero ;?>' class="input" type="text" size="18" maxlength="16">
			</td>
            <td align='left'><strong>Complem.</strong></td>
            <td align='left'>
			<input name="consumidor_complemento" value='<?echo $consumidor_complemento ;?>' class="input" type="text" size="14" maxlength="14">
			</td>
          </tr>
          <tr>
            <td align='left'><strong>Bairro:</strong></td>
            <td align='left'>
			<input name="consumidor_bairro" value='<?echo $consumidor_bairro ;?>' class="input" type="text" size="40" maxlength="30">
			</td>
            <td align='left'><strong>Cidade:</strong></td>
            <td align='left'>
			<input name="consumidor_cidade" id='consumidor_cidade' value='<?echo $consumidor_cidade ;?>'   class="input" type="text" size="18" maxlength="16">
			<input name="cidade"  class="input" value='<?echo $cidade ;?>' type="hidden">
			</td>
            <td align='left'><strong>Estado:</strong></td>
            <td align='left'>
			<select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px' >
			<? $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
			for ($i=0; $i<=26; $i++){
				echo"<option value='".$ArrayEstados[$i]."'";
				if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
					echo ">".$ArrayEstados[$i]."</option>\n";
				}
				?>
             </select>

			 </td>
          </tr>
		  <tr>
		  <td colspan='2' align='left'><strong>Melhor horário p/ contato: </strong> <input name="hora_ligacao" id='hora_ligacao' class="input" value='<?echo $hora_ligacao ;?>' type="text" maxlength='5' size='7'>
		  </td>
		 		 <td align='left'><strong>Origem:</strong></td>
            <td align='left'>
			<select name='origem' id='origem' style='width:102px;font-size:9px'>
			<option value='Telefone'>Telefone</option>
			<option value='Email'>E-mail</option>
			</select>
			</td>
            <td align='left'><strong>Tipo:</strong></td>
            <td align='left'>
			<select name="consumidor_revenda" id='consumidor_revenda' style='width:81px; font-size:9px' >
			<option value='C'<?if($tipo=="C") echo "SELECTED";?>>Consumidor</option>
			<option value='R'<?if($tipo=="R") echo "SELECTED";?>>Revenda</option>
             </select>

			 </td>
		  </tr>
		  <tr>
		   <td colspan='6' align='left'><INPUT TYPE="checkbox" NAME="receber_informacoes" <? if($receber_informacoes=="t") echo "checked";?> value='t'><strong>Aceita receber informações sobre nossos produtos? </strong>
		  </td>
		  </tr>
        </table>

<?


	}
	if(strlen($busca_cliente)>0 and $tipo == "atendimento"){
		if(strlen($busca)>0){
			if(is_numeric($busca)){
				$sql = "SELECT	tbl_hd_chamado.hd_chamado,
								tbl_hd_chamado.status,
								to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
								tbl_hd_chamado_extra.nome as cliente_nome,
								tbl_hd_chamado.categoria
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $busca";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)==0){
					$sql = "SELECT	tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado.status,
									to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
									tbl_hd_chamado_extra.nome as cliente_nome,
									tbl_hd_chamado.categoria
							FROM tbl_hd_chamado
							JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
							where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
							and tbl_hd_chamado_extra.cpf = '$busca'";
					$res = pg_query($con,$sql);

				}
			}else{
				$busca = strtoupper($busca);
				$sql = "SELECT	tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
						tbl_hd_chamado_extra.nome as cliente_nome,
						tbl_hd_chamado.categoria
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
				and tbl_hd_chamado_extra.nome ilike '%$busca%'";
				$res = pg_query($con,$sql);

			}
			if(pg_num_rows($res)>0){
				echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
				echo "<tr>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Atendimento</strong></td>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Data</strong></td>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Consumidor</strong></td>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Situação</strong></td>";
				echo "</tr>";
				for($i=0;pg_num_rows($res)>$i;$i++){
					$situacao     = pg_fetch_result($res,$i,'status');
					$hd_chamado   = pg_fetch_result($res,$i,'hd_chamado');
					$data         = pg_fetch_result($res,$i,'data');
					$cliente_nome = pg_fetch_result($res,$i,'cliente_nome');
					$categoria    = pg_fetch_result($res,$i,'categoria');
					echo "<tr>";
					echo "<td align='center'>";
						if($login_fabrica==11){
							echo "<a href='callcenter_interativo_new.php?callcenter=$hd_chamado#$categoria'>$hd_chamado</a>";
						}else{
							echo "<a href='callcenter_interativo.php?callcenter=$hd_chamado#$categoria'>$hd_chamado</a>";
						}
					echo "</td>";
					echo "<td align='center'>$data</td>";
					echo "<td align='center'>$cliente_nome</td>";
					echo "<td align='center'>$situacao</td>";
					echo "</tr>";
				}
				echo "</table>";
			}else{
				echo "<center>Nenhum resultado encontrado!</center>";

			}

		}

	}

$faq_duvida = $_GET['faq_duvida'];
if(strlen($faq_duvida)>0 OR $login_fabrica == 42){ // !faq
	$produto        = $_GET['produto'];
	$duvida         = strtoupper(trim($_GET['faq_duvida']));
	$hd_chamado     = (int) ( isset($_GET['hd_chamado']) ) ? $_GET['hd_chamado'] : 0 ;
	$buscar_marcados=  $_GET['buscar_marcados'];
	$duvidasMarcadas= array();
	if ( ! empty($hd_chamado) ) {
		// Se o chamado for informado, buscar as dúvidas já marcadas para ele (se existentes)
		$sql = "SELECT faq, faq_solucao
				FROM tbl_hd_chamado_faq
				WHERE hd_chamado = %s";
		$sql = sprintf($sql,pg_escape_string($hd_chamado));
		$res = pg_query($con,$sql);
		if ( is_resource($res) && pg_num_rows($res) > 0 ) {
			while ($row = pg_fetch_assoc($res)) {
				$duvidasMarcadas[$row['faq']][$row['faq_solucao']] = $row['faq'];
			}
		}
	}
	$sql_where = '';
    #echo $buscar_marcados;exit;
	if ( $buscar_marcados == 0 && strlen($duvida) > 0 ) {
		$sql_where = "AND   (
                                fn_retira_especiais(tbl_faq.situacao)        ILIKE '%'||fn_retira_especiais('$duvida')||'%'
                            OR  fn_retira_especiais(tbl_faq_causa.causa)     ILIKE '%'||fn_retira_especiais('$duvida')||'%'
                            OR  fn_retira_especiais(tbl_faq_solucao.solucao) ILIKE '%'||fn_retira_especiais('$duvida')||'%'
                            )
        ";
	} else {
		$sql_where = '';
	}

	if ($login_fabrica == 148) {
		$join = "INNER JOIN tbl_linha ON tbl_linha.linha = tbl_faq.linha AND tbl_linha.fabrica = {$login_fabrica} INNER JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$login_fabrica}";
	} else {

		if($login_fabrica == 42){
			$tipo_join = "LEFT";
			$complemento_sql_produto = " OR tbl_faq.produto is null ";
		}else{
			$tipo_join = "INNER";
		}

		$join = "$tipo_join JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto AND tbl_produto.fabrica_i = {$login_fabrica}";
	}


	$sql = "SELECT  tbl_faq.faq,
                    tbl_faq.produto,
                    tbl_faq.situacao,
                    tbl_faq_causa.causa,
                    tbl_faq_solucao.solucao,
                    tbl_faq_solucao.faq_solucao
			FROM    tbl_faq
       LEFT JOIN    tbl_faq_causa USING (faq)
       LEFT JOIN    tbl_faq_solucao USING (faq_causa) 
	{$join}
	WHERE tbl_faq.fabrica = {$login_fabrica}
	AND (fn_retira_especiais(tbl_produto.referencia) = fn_retira_especiais('$produto') $complemento_sql_produto ) 
	$sql_where
    ";
	$res  = pg_query($con,$sql);
	$rows = ( is_resource($res) ) ? pg_num_rows($res) : 0 ;
	if( $rows > 0 ){
		echo '<input type="hidden" name="gravar_faq" value="1" />';
		echo "<em>Selecione uma ou mais dúvidas que foram pertinentes ao chamado, se necessário <a href='faq_situacao.php?referencia=$produto' target='_blank'>clique aqui</a> para cadastrar uma nova dúvida (abre uma nova janela).</em>";
		echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>";
		for($i=0;$rows>$i;$i++){
			$faq	  = pg_fetch_result($res,$i,'faq');
			$situacao = pg_fetch_result($res,$i,'situacao');
			$causa    = pg_fetch_result($res,$i,'causa');
			$solucao  = pg_fetch_result($res,$i,'solucao');
			$faq_solucao = pg_fetch_result($res,$i,'faq_solucao');
			if ( $buscar_marcados && ! empty($hd_chamado) && ! isset($duvidasMarcadas[$faq]) ) {
				// Se for uma busca de duvidas de um chamado existente, só trazer os marcados para aquele chamado
				continue;
			}
			// HD 129655 Exibir opção de salvar faq
			?>
			<tr>
				<td rowspan="4" colspan="2" align="center">
					<input type="checkbox" class="chk_faq" name="faq[]" value="<?php echo $faq.'-'.$faq_solucao; ?>" <?php echo ( isset($duvidasMarcadas[$faq][$faq_solucao]) ) ? 'checked="checked"' : '' ; ?> />
				</td>
			</tr>
			<?php
			// fim hd 129655
			echo "<tr>";
			echo "<td bgcolor='#BCCACD'>Problema:</td>";
			echo "<td align='left'  bgcolor='#BCCACD'><strong>$situacao</strong></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td bgcolor='#FFFFFF'>Causa:</td>";
			echo "<td align='left'  bgcolor='#FFFFFF'>$causa</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td bgcolor='#FFFFFF'>Solução:</td>";
			echo "<td align='left'  bgcolor='#FFFFFF'>$solucao</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>&nbsp;</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>";
		if($login_fabrica==2){
			echo "<tr>";
			echo "<td align='left' valign='top' nowrap><strong>Descrição Dúvida :</strong></td>";
			echo "<td align='left'>";
			echo "<TEXTAREA NAME='faq_situacao' ROWS='4' COLS='110'  class='input' style='font-size:10px'>$faq_situacao</TEXTAREA>";
			echo "</td>";
			echo "</tr>";
		}
		echo "<tr>";
		echo "<td nowrap colspan='100%'>Nenhum resultado encontrado! Para cadastrar <a href='faq_situacao.php?referencia=$produto' target='_blank'>clique aqui</a>. (<em>Uma nova tela será aberta</em>)";
		if($login_fabrica==2) {
			echo " ou preeche o campo acima para cadastrar nova dúvida";
		}
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
}

if($novaTelaOs == true){
	$novo_faq = $_GET['novo_faq'];
	if($novo_faq == true){
		$referencia = $_GET['referencia'];
		$descricao  = $_GET['descricao'];
		$duvida     = $_GET['duvida'];
		$hd_chamado     = (int) ( isset($_GET['hd_chamado']) ) ? $_GET['hd_chamado'] : 0 ;
		$duvidasMarcadas = array();

		if ( ! empty($hd_chamado) ) {
			// Se o chamado for informado, buscar as dúvidas já marcadas para ele (se existentes)
			$sql = "SELECT faq FROM tbl_hd_chamado_faq
				WHERE hd_chamado = %s";
			$sql = sprintf($sql,pg_escape_string($hd_chamado));
			$res = pg_query($con,$sql);
			if ( is_resource($res) && pg_num_rows($res) > 0 ) {
				while ($row = pg_fetch_assoc($res)) {
					$duvidasMarcadas[$row['faq']] = $row['faq'];
				}
			}
		}
		
		if(in_array($login_fabrica, array(148))){
			$join = " JOIN (SELECT tbl_linha.linha, tbl_produto.referencia, tbl_produto.descricao FROM tbl_linha 
					JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = $login_fabrica
					WHERE tbl_linha.fabrica = $login_fabrica
				) AS linha_produto ON linha_produto.linha = tbl_faq.linha ";
		}else{
			$join = " JOIN (SELECT tbl_linha.linha, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto FROM tbl_linha 
					JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = $login_fabrica
					WHERE tbl_linha.fabrica = $login_fabrica
				) AS linha_produto ON linha_produto.produto = tbl_faq.produto ";
		}

		if($duvida != ""){
			$where = " AND tbl_faq.situacao ILIKE '%$duvida%' ";
		}

		$sql = "SELECT tbl_faq.faq, tbl_faq.situacao
			FROM tbl_faq
				$join
			WHERE linha_produto.referencia = '$referencia' AND linha_produto.descricao = '$descricao'
				$where";
		$res   = pg_query($con,$sql);
		$count = pg_num_rows($res);

		if($hd_chamado != 0){
			$sql = "SELECT faq FROM tbl_hd_chamado_faq WHERE hd_chamado = $hd_chamado LIMIT 1";
			$resFaq = pg_query($con,$sql);

			if(pg_num_rows($resFaq) > 0){
				$aux_faq = pg_fetch_result($resFaq, 0, "faq");
			}
		}

		if( $count > 0 ){ ?>
			<input type="hidden" name="gravar_faq" value="1" >
			<em>Selecione uma ou mais dúvidas que foram pertinentes ao chamado, se necessário <a href='faq_situacao.php' target='_blank'>clique aqui</a> para cadastrar uma nova dúvida (abre uma nova janela).</em>
			<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px; border: #485989 1px solid;'>
			<?php 
			for($i=0; $i < $count; $i++){
				$faq	  = pg_fetch_result($res,$i,'faq');
				$situacao = pg_fetch_result($res,$i,'situacao');

				if($i%2 == 0){
					$color = "";
				}else{
					$color = "#BCCACD";
				}

				?>
				<tr>
					<td rowspan="2" colspan="2" bgcolor='<?=$color?>' align="center">
						<input type="radio" class="chk_faq" name="faq_produto" value="<?=$faq?>" <?php echo ($aux_faq == $faq) ? 'checked' : '' ; ?> onclick="escolhaSituacao(<?=$faq?>)" />
					</td>
				</tr>
				<tr>
					<td align='left' bgcolor='<?=$color?>'><strong><?=$situacao?></strong></td>
				</tr>
				<?php
			} ?>
			</table>
				<?php
		}else{ ?>
			<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>
				<tr>
					<td nowrap colspan='100%'>
						Nenhum resultado encontrado! Para cadastrar <a href='faq_situacao.php' target='_blank'>clique aqui</a>. (<em>Uma nova tela será aberta</em>)
					</td>
				</tr>
			</table>
		<?php 
		}
	}

	$faq_selecionado = $_GET['faq_selecionado'];

	if($faq_selecionado == true){
		$faq        = $_GET['faq'];
		$hd_chamado = (int) ( isset($_GET['hd_chamado']) ) ? $_GET['hd_chamado'] : 0 ;

		if($hd_chamado != 0){
			$sql = "SELECT faq, faq_solucao FROM tbl_hd_chamado_faq WHERE hd_chamado = $hd_chamado";
			$resSolucao = pg_query($con,$sql);

			if(pg_num_rows($resSolucao) > 0){
				$faq = pg_fetch_result($resSolucao, 0, "faq");
				while($aux = pg_fetch_object($resSolucao)){
					$aux_faq_solucao[] = $aux->faq_solucao;
				}
			}
		}

		$count = 0;

		if (strlen($faq)) {

			$sql = "SELECT tbl_faq_solucao.faq_solucao, 
						tbl_faq_causa.causa,
						tbl_faq_causa.faq_causa,
						tbl_faq_solucao.solucao
					FROM tbl_faq_solucao
					JOIN tbl_faq_causa ON tbl_faq_causa.faq_causa = tbl_faq_solucao.faq_causa
				WHERE tbl_faq_solucao.faq = $faq";
			$res   = pg_query($con,$sql);
			$count = pg_num_rows($res);

		}

		if ($count > 0) { ?>

			<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style='font-size:10px; border: #485989 1px solid;'>
			<?php 
			$aux = "";
			for($i=0; $i < $count; $i++){
				$faq_solucao = pg_fetch_result($res,$i,'faq_solucao');
				$causa       = pg_fetch_result($res,$i,'causa');
				$solucao     = pg_fetch_result($res,$i,'solucao');

				if($i%2 == 0){
					$color = "";
				}else{
					$color = "#BCCACD";
				}
				?>
				<tr>
					<td align='left' bgcolor='<?=$color?>'><strong><?php echo $causa != $aux ? $causa : ''; ?></strong></td>
					<td rowspan="1" bgcolor='<?=$color?>' align="center">
						<input type="checkbox" class="chk_faq" name="faq_causa_<?=$faq_solucao?>" value="<?php echo $faq."|".$faq_solucao; ?>" <?php if(in_array($faq_solucao, $aux_faq_solucao)){ echo 'checked="checked"'; } ?> />
					</td>
					<td align='left' style="text-align:left;" bgcolor='<?=$color?>'><strong><?=$solucao?></strong></td>
				</tr>
				<?php

				if($aux != $causa){
					$aux = $causa;
				}

			} ?>
			</table>
				<?php
		}else{ ?>
			<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>
				<tr>
					<td nowrap colspan='100%'>
						Nenhum resultado encontrado! Para cadastrar <a href='faq_situacao.php?faq=<?=$faq?>' target='_blank'>clique aqui</a>. (<em>Uma nova tela será aberta</em>)
					</td>
				</tr>
			</table>
		<?php 
		}
	}
}


	$garantia = $_GET['garantia'];

	if(strlen($garantia)>0){
		include "conexao_hbtech.php";
		$produto_nome       = $_GET['produto_nome'];
		$produto_referencia = $_GET['produto_referencia'];
		$serie              = $_GET['serie'];

		if(strlen($produto_referencia)==0){
			echo "Favor insira o código do produto";
			exit;
		}
		if(strlen($serie)==0){
			echo "Favor insira o número de série do produto";
			exit;
		}
		$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
				WHERE tbl_linha.fabrica = $login_fabrica
				AND tbl_produto.referencia = '$produto_referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$produto = pg_fetch_result($res,0,0);
		//	echo $produto;

		}else{
			echo "Produto não encontrado";
			exit;
		}
		if(strlen($produto)>0){
			$sql = "SELECT	idNumeroSerie  ,
							idGarantia     ,
							revenda        ,
							cnpj
					FROM numero_serie
					WHERE numero = '$serie' ";
//			echo $sql;
			$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

			if(mysql_num_rows($res)>0){
				$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
				$idGarantia    = mysql_result($res,0,idGarantia);
				$revenda       = mysql_result($res,0,revenda);
				$cnpj       = mysql_result($res,0,cnpj);
				//echo " id $idNumeroSerie garantia $idGarantia  revenda $revenda";
				if(strlen($idGarantia)==0){
					if($origem=="os_cadastro"){
						echo "Garantia não cadastrada, verificar local da compra.";
						exit;
					}else{
					?>
					<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td align='right' width='140'></td>
						<td align='right' width='55'>
							<img src='imagens/ajuda_call.png' align=absmiddle>
						</td>
						<td align='center'>
							<STRONG>Garantia não cadastrada, verificar local da compra.</STRONG><BR>
							Onde o Sr.(a) comprou o produto?
						</td>
						<td align='right' width='140'></td>
					</tr>
					</table>
					<?
					}

					echo "<input name='es_id_numeroserie' id='es_id_numeroserie' value='$idNumeroSerie' type='hidden'>";
					echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style=' font-size:10px'>";
					echo "<tr>";
					echo "<td><B>Cnpj Revenda:</B></td>";
					echo "<td><input name='es_revenda_cnpj' id='es_revenda_cnpj' class='input' value='$cnpj' type='text' maxlength='14' size='15' readonly></td>";
					echo "<td><B>Nome Revenda:</B></td>";
					echo "<td><input name='es_revenda' id='es_revenda' class='input' value='$revenda' type='text' maxlength='50' size='25' readonly></td>";
					echo "<td><B>Nota Fiscal:</B></td>";
					echo "<td><input name='es_nota_fiscal' id='es_nota_fiscal' class='input' value='$es_nota_fiscal' type='text' maxlength='8' size='8'> </td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td><B>Data Compra:</B></td>";
					echo "<td><input name='es_data_compra' id='es_data_compra' class='input' value='$es_data_compra' type='text' maxlength='10' size='12'></td>";
					echo "<td><B>Municipio Compra:</B></td>";
					echo "<td><input name='es_municipiocompra' id='es_municipiocompra' class='input' value='$es_municipiocompra' type='text' maxlength='255' size='25'></td>";
					echo "<td><B>Estado Compra:</B></td>";
					echo "<td>";
					echo "<select name='es_estadocompra' id='es_estadocompra' style='width:52px; font-size:9px' >";
					 $ArrayEstados = array('AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=26; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($es_estadocompra == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}
					echo "</select>";
					echo "</td>";
					echo "</tr>";


					echo "<tr>";
					echo "<td><B>Data Nascimento:</B></td>";
					echo "<td><input name='es_data_nascimento' id='es_data_nascimento' class='input' value='$es_data_nascimento' type='text' maxlength='10' size='12'></td>";

					echo "<td><B>Estado Civil:</B></td>";
					echo "<td>";
					echo "<select name='es_estadocivil' id='es_estadocivil' style='width:100px; font-size:9px' >";
					echo "<option value=''></option>";
					echo "<option value='0'>Solteiro(a)</option>";
					echo "<option value='1'>Casado(a)</option>";
					echo "<option value='2'>Divorciado(a)</option>";
					echo "<option value='3'>Viuvo(a)</option>";
					echo "</select>";

					echo "</td>";
					echo "<td><B>Sexo:</B></td>";
					echo "<td>";
					echo "<INPUT TYPE='radio' NAME='es_sexo' value='0'>M. ";
					echo "<INPUT TYPE='radio' NAME='es_sexo' value='1'>F. ";
					echo "</td>";

					echo "</tr>";

					echo "<tr>";
					echo "<td><B>Filhos:</B></td>";
					echo "<td>";
					echo "<INPUT TYPE='radio' NAME='es_filhos' value='0'>Sim ";
					echo "<INPUT TYPE='radio' NAME='es_filhos' value='1'>Não ";
					echo "</td>";

					echo "<td><B>Fone Comercial:</B></td>";
					echo "<td><input name='es_fonecomercial' id='es_fonecomercial' class='input' value='$es_fonecomercial' type='text' maxlength='14' size='16'></td>";

					echo "<td><B>Celular:</B></td>";
					echo "<td>";
					echo "<input name='es_celular' id='es_celular' class='input' value='$es_celular' type='text' maxlength='14' size='16'>";
					echo "</td>";

					echo "</tr>";

					echo "<tr>";
					echo "<td colspan='6'><B>Preferência Musical:</B> ";
					echo "<input name='es_preferenciamusical' id='es_preferenciamusical' class='input' value='$es_preferenciamusical' type='text' maxlength='255' size='105'>";
					echo "</td>";
					echo "</tr>";


					echo "</table>";

				}else{
					echo "Garantia Estendida já cadastrada para esse produto";
				}
			}else{
				echo "Número de série não encontrado nas vendas";

			}


		}
	}

	$listar = $_GET['listar'];
	if(strlen($listar)>0){
		$produto = $_GET['produto'];
		$sql = "SELECT tbl_faq.faq,
						tbl_faq.produto,
						tbl_faq.situacao,
						tbl_faq_causa.causa,
						tbl_faq_solucao.solucao
				from tbl_faq
				LEFT JOIN tbl_faq_causa on tbl_faq_causa.faq = tbl_faq.faq
				LEFT JOIN tbl_faq_solucao on tbl_faq_causa.faq_causa = tbl_faq_solucao.faq_causa
				JOIN tbl_produto on tbl_produto.produto = tbl_faq.produto
				where tbl_produto.referencia = '$produto' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>";
			for($i=0;pg_num_rows($res)>$i;$i++){
				$situacao = pg_fetch_result($res,$i,'situacao');
				$causa    = pg_fetch_result($res,$i,'causa');
				$solucao  = pg_fetch_result($res,$i,'solucao');
				echo "<tr>";
				echo "<td bgcolor='#BCCACD'>Problema:</td>";
				echo "<td align='left'  bgcolor='#BCCACD'><strong>$situacao</strong></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td bgcolor='#FFFFFF'>Causa:</td>";
				echo "<td align='left'  bgcolor='#FFFFFF'>$causa</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td bgcolor='#FFFFFF'>Solução:</td>";
				echo "<td align='left'  bgcolor='#FFFFFF'>$solucao</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='2'>&nbsp;</td>";
				echo "</tr>";
			}
			echo "<tr>";
			echo "<td align='left' valign='top' nowrap><strong>Descrição Dúvida:</strong></td>";
			echo "<td align='left'>";
			echo "<TEXTAREA NAME='faq_situacao' ROWS='4' COLS='110'  class='input' style='font-size:10px'>$faq_situacao</TEXTAREA>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}else{
			?>
			<table width="100%" border="0" align="center" cellpadding="2" cellspacing="2" style="font-style: 10px;">
				<tr align="left">
					<td> <strong>Descrição Dúvida</strong> </td>
					<td>
						<textarea name="faq_situacao" rows="4" cols="110" class="input" style="font-size: 10px"><?php echo $faq_situacao; ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">Nenhum resultado encontrado! Para cadastrar <a href="faq_situacao.php?referencia=<?php echo $produto; ?>" target="_blank">clique aqui</a> ou preeche o campo acima para cadastrar nova dúvida</td>
				</td>
			</table>
			<?php
		}


	}

}
exit;

?>
