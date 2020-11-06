<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

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
			$cond_1 = " upper(tbl_cliente.nome) like '%$busca%' ";
			$xcond_1 = " upper(tbl_revenda.nome) like '%$busca%' ";
		}
		if($tipo=="cpf"){
			$cond_1 = " tbl_cliente.cpf = '$busca' ";
			$xcond_1 = " tbl_revenda.cnpj like '%$busca%' ";
		}
		if($tipo=="novo"){
			$cond_1 = " 1=2 ";
		}
		if(strlen($busca)>0 and $tipo<>"novo"){
			$sql = "SELECT tbl_cliente.cliente      ,
							tbl_cliente.nome        ,
							tbl_cliente.endereco    ,
							tbl_cliente.numero      ,
							tbl_cliente.complemento ,
							tbl_cliente.bairro      ,
							tbl_cliente.cep         ,
							tbl_cliente.cidade      ,
							tbl_cliente.fone        ,
							tbl_cliente.cpf         ,
							tbl_cliente.rg          ,
							tbl_cliente.email       ,
							tbl_cidade.nome  as cidade_nome       ,
							tbl_cidade.estado
					FROM tbl_cliente
					JOIN tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
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
						SELECT tbl_cliente.cliente as id ,
								tbl_cliente.nome         ,
								tbl_cliente.endereco     ,
								tbl_cliente.numero       ,
								tbl_cliente.complemento  , 
								tbl_cliente.bairro       ,
								tbl_cliente.cep          ,
								tbl_cliente.cidade       ,
								tbl_cliente.fone         ,
								tbl_cliente.cpf as cpf_cnpj ,
								tbl_cliente.rg           ,
								tbl_cliente.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'C' as tipo
						FROM tbl_cliente 
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE $cond_1
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

			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$cliente     = pg_result($res,0,id);
				$consumidor_nome        = pg_result($res,0,nome);
				$consumidor_endereco    = pg_result($res,0,endereco);
				$consumidor_numero      = pg_result($res,0,numero);
				$consumidor_complemento = pg_result($res,0,complemento);
				$consumidor_bairro      = pg_result($res,0,bairro);
				$consumidor_cep         = pg_result($res,0,cep);
				$cidade                 = pg_result($res,0,cidade);
				$consumidor_fone        = pg_result($res,0,fone);
				$consumidor_cpf         = pg_result($res,0,cpf_cnpj);
				$consumidor_rg          = pg_result($res,0,rg);
				$consumidor_email       = pg_result($res,0,email);
				$consumidor_estado      = pg_result($res,0,estado);
				$consumidor_cidade      = pg_result($res,0,nome_cidade);
				$tipo                   = pg_result($res,0,tipo);

			}else{
				//echo "nao encontrado";
			}
		}
?>
			<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
			<input name="consumidor_nome"  value='<?echo $consumidor_nome ;?>' class="input" type="text" size="35" maxlength="500"> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'>

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
								tbl_cliente.nome as cliente_nome
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						join tbl_cliente on tbl_cliente.cliente = tbl_hd_chamado_extra.cliente
						where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $busca";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)==0){
					$sql = "SELECT	tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado.status,
									to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
									tbl_cliente.nome as cliente_nome
							FROM tbl_hd_chamado
							JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
							join tbl_cliente on tbl_cliente.cliente = tbl_hd_chamado_extra.cliente
							where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
							and tbl_cliente.cpf = $busca";
					$res = pg_exec($con,$sql);
				
				}
			}else{
				$busca = strtoupper($busca);
				$sql = "SELECT	tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
						tbl_cliente.nome as cliente_nome
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				join tbl_cliente on tbl_cliente.cliente = tbl_hd_chamado_extra.cliente
				where tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
				and tbl_cliente.nome ilike '%$busca%'";
				$res = pg_exec($con,$sql);
			
			}
			//echo $sql;
			
			//echo $sql;
			if(pg_numrows($res)>0){
				echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
				echo "<tr>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Atendimento</strong></td>";			
				echo "<td align='center' bgcolor='#BCCACD'><strong>Data</strong></td>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Consumidor</strong></td>";
				echo "<td align='center' bgcolor='#BCCACD'><strong>Situação</strong></td>";
				echo "</tr>";	
				for($i=0;pg_numrows($res)>$i;$i++){
					$situacao   = pg_result($res,$i,status);
					$hd_chamado = pg_result($res,$i,hd_chamado);
					$data       = pg_result($res,$i,data);
					$cliente_nome = pg_result($res,$i,cliente_nome);
					echo "<tr>";
					echo "<td align='center'><a href='callcenter_interativo.php?callcenter=$hd_chamado'>$hd_chamado</a></td>";			
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
if(strlen($faq_duvida)>0){
	$produto = $_GET['produto'];
	$duvida = strtoupper($_GET['duvida']);
	$sql = "SELECT tbl_faq.faq,
					tbl_faq.produto,
					tbl_faq.situacao,
					tbl_faq_causa.causa,
					tbl_faq_solucao.solucao
			from tbl_faq
			join tbl_faq_causa on tbl_faq_causa.faq = tbl_faq.faq
			join tbl_faq_solucao on tbl_faq_causa.faq_causa = tbl_faq_solucao.faq_causa
			join tbl_produto on tbl_produto.produto = tbl_faq.produto
			where tbl_produto.referencia = '$produto'
			and (tbl_faq.situacao ilike '%$duvida%' or tbl_faq_causa.causa ilike '%$duvida%' or tbl_faq_solucao.solucao ilike '%$duvida%')";
	$res = pg_exec($con,$sql);
//echo $sql;
	if(pg_numrows($res)>0){
		echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='0' style=' font-size:10px'>";
		for($i=0;pg_numrows($res)>$i;$i++){
			$situacao = pg_result($res,$i,situacao);
			$causa    = pg_result($res,$i,causa);
			$solucao  = pg_result($res,$i,solucao);
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
		echo "<tr>";
		echo "<td>Nenhum resultado encontrado! Para cadastrar <a href='faq_situacao.php' target='blank'>clique aqui</a></td>";
		echo "</tr>";
		echo "</table>";
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
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
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
					WHERE numero = '$serie'";
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


}
exit;

?>