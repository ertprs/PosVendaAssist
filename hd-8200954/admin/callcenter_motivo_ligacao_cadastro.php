<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';
	include 'funcoes.php';
	include_once dirname(__FILE__) . '/../class/AuditorLog.php';

	$AuditorLog = new AuditorLog;

	$text_prov = "Providência";
	$text_class = "Classificação";
	if ($login_fabrica == 189) {

		$text_class = "Registro Ref. a";
		$text_prov = "Ação";
	} 
	
	$descricao         	= $_REQUEST['descricao'];
	$ativo             	= $_REQUEST['ativo'];
	$hd_chamado_motivo 	= $_REQUEST['hd_chamado_motivo'];
	$texto_email       	= $_REQUEST['texto_email'];
	$texto_email_admin  = $_REQUEST['texto_email_admin'];
	$texto_sms			= $_REQUEST['texto_sms'];
	$prazo				= $_REQUEST['prazo'];
	$prazo   	 		= (strlen($prazo)) ? $prazo : 0;
	$prazo_horas        = $_REQUEST['prazo_horas'];
	$prazo_horas        = (strlen($prazo_horas)) ? $prazo_horas : 0;
	$classificacao		= $_REQUEST['classificacao'];
	$sub_classificacao  = $_REQUEST['sub_classificacao'];
	$abre_os 			= $_REQUEST['abre_os'];
	$obriga_os 			= $_REQUEST['obriga_os'];
	$contas_nacionais   = $_REQUEST['contas_nacionais'];
	$os_cortesia 		= $_REQUEST['os_cortesia'];
	$situacao 			= $_REQUEST['situacao'];


	if($_POST['ajax_tipo_protocolo_classificacao']){

	    $tipo_protocolo = $_POST['tipo_protocolo'];

	    $sql = "SELECT tbl_hd_classificacao.hd_classificacao,
	                    tbl_hd_classificacao.descricao
	            FROM tbl_hd_classificacao
	            JOIN tbl_hd_tipo_chamado_vinculo USING(hd_classificacao)
	            JOIN tbl_hd_tipo_chamado USING(hd_tipo_chamado)
	            WHERE tbl_hd_tipo_chamado.fabrica = {$login_fabrica}
	            AND tbl_hd_tipo_chamado_vinculo.hd_tipo_chamado = {$tipo_protocolo}";
	    $res = pg_query($con,$sql);

	    if(pg_num_rows($res) > 0){

	        $retorno = "<option value=''>Escolha</option>";

	        foreach (pg_fetch_all($res) as $key => $value) {
	            $retorno .= "<option value='".$value['hd_classificacao']."'>".$value['descricao']."</option>";
	        }

	    }

	    echo $retorno; exit;
	}


	if($_GET['ajax_classificacao_aba']){

		$classificacao = $_GET['classificacao'];

		$sql = "SELECT tbl_hd_tipo_chamado.descricao 
					FROM tbl_hd_tipo_chamado
				JOIN tbl_hd_tipo_chamado_vinculo USING(hd_tipo_chamado)
				WHERE tbl_hd_tipo_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_tipo_chamado_vinculo.hd_classificacao = {$classificacao}
				AND lower(fn_retira_especiais(tbl_hd_tipo_chamado.descricao)) = 'ecommerce'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$cond = " AND nome = 'informacao' ";
		}

		$sql = "SELECT
                       natureza, nome, descricao
					FROM tbl_natureza
					WHERE fabrica = {$login_fabrica}
					AND ativo IS TRUE
					$cond
					ORDER by descricao ASC";
		$res = pg_query($con,$sql);

		$ret = "<option value=''>Escolha</option>";
		if(pg_num_rows($res) > 0){

			for($i = 0; $i < pg_num_rows($res); $i++){

				$natureza = pg_fetch_result($res, $i, 'natureza');
				$descricao = pg_fetch_result($res, $i, 'descricao');

				$ret .= "<option value='{$natureza}'>{$descricao}</option>"; 

			}
		}

		echo $ret;
		exit;

	}

	if($_GET['ajax_campos']){
		$hd_motivo_ligacao = $_GET['hd_motivo_ligacao'];
		$sql = "SELECT descricao, ativo, prazo_dias, prazo_horas, texto_email, texto_email_admin, texto_sms, hd_classificacao, natureza, abre_os, os_obrigatoria, tipo_registro, categoria, campos_adicionais
			FROM tbl_hd_motivo_ligacao
			WHERE hd_motivo_ligacao = {$hd_motivo_ligacao}
			AND fabrica = {$login_fabrica}";
		$res = pg_query($con,$sql);

		$descricao   = pg_result($res,0,'descricao');
		$ativo       = pg_result($res,0,'ativo');
		$hd_motivo   = pg_result($res,0,'hd_motivo_ligacao');
		$texto_email = pg_result($res,0,'texto_email');
		$texto_email_admin = pg_result($res,0,'texto_email_admin');
		$texto_sms   = pg_result($res,0,'texto_sms');
		$prazo       = pg_result($res,0,'prazo_dias');
		$prazo_horas       = pg_result($res,0,'prazo_horas');
		$hd_subclassificacao = pg_fetch_result($res, 0, 'natureza');
		$abre_os = pg_fetch_result($res, 0, 'abre_os');
		$obriga_os = pg_fetch_result($res, 0, 'os_obrigatoria');
		$contas_nacionais = pg_fetch_result($res, 0, 'tipo_registro');
		$os_cortesia = pg_fetch_result($res, 0, 'categoria');
		$categoria   = pg_result($res,0,'hd_classificacao');
		$campos_adicionais   = pg_result($res,0,'campos_adicionais');
		$categoria   = explode("|",$categoria);
		$categoria   = implode("||",$categoria);

		if (in_array($login_fabrica, array(151))) {
			$resposta = "ok|$descricao|$ativo|$texto_email|$texto_sms|$prazo|$categoria|$texto_email_admin";
		}elseif(in_array($login_fabrica, array(169,170))){
			$resposta = "ok|$descricao|$ativo|$texto_email|$texto_sms|$prazo|$categoria|$hd_subclassificacao|$abre_os|$obriga_os|$contas_nacionais|$os_cortesia";
		}elseif(in_array($login_fabrica, array(189))){
			$resposta = "ok|$descricao|$ativo|$texto_email|$texto_sms|$prazo|$categoria|$prazo_horas";
		}else{
			$resposta = "ok|$descricao|$ativo|$texto_email|$texto_sms|$prazo|$categoria|$campos_adicionais";
		}
		echo $resposta;
		exit;
	}
	if ($login_fabrica == 151) {

		if($_GET['ajax_email']){
			$hd_motivo = $_GET['hd_motivo'];
			$linha = $_GET['linha'];
			$sql = "SELECT destinatarios FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = $hd_motivo ;";
			$res = pg_query($con,$sql);

			$html = "  <td colspan='3' align='center'  id='div_email_{$linha}'>
	                         <table width='100%'>
	                              <thead>
	                                  <tr class='titulo_coluna'>
	                                      <th>e-mail(s)</th>
	                                      <th>Ações</th>
	                                  </tr>
	                              </thead>
	                              <tbody>";

			if (pg_num_rows($res) > 0) {
				$destinatarios = pg_fetch_result($res, 0, destinatarios);
				$destinatarios = json_decode($destinatarios,true);
				foreach ($destinatarios as $chave => $value) {
					$html .= "<tr align='center'>
								<td>
									<input type='text' name='email_{$hd_motivo}_{$chave}' id='email_{$hd_motivo}_{$chave}' value='{$value}' class='frm' readonly style='width:90% !important;'>
								</td>
								<td >
									<input type='button' value='Excluir' id='btn_excluir_email_{$linha}_{$chave}' onclick='excluiEmail({$linha},{$hd_motivo},{$chave})'>
								</td>

						</tr>";
				}
			}

			$html .= "  <tr align='center'>
	                    <td>
	                    	E-mail : <input type='text' name='cad_email_{$linha}' id='cad_email_{$linha}' value='' class='frm' style='width:80% !important;'>
	               		<td>
	               		<input type='button' value='Gravar' id='btn_grava_email_{$linha}' onclick='gravaEmail({$linha},{$hd_motivo})' >


	                                   		</td>
	                              </tr>
	                         </tbody>
	                    </table>
	                </td>
			";
			echo $html;
			exit;
		}

		if ($_GET['ajax_gravar_email']) {
			include '../class/communicator.class.php';
			$mail = new TcComm();

			$hd_motivo = $_GET['hd_motivo'];
			$email = $_GET['email'];

			$emails = $mail->parseEmail($email);

			if (!empty($emails[0]) ) {
				$sql = "SELECT destinatarios
							FROM tbl_hd_motivo_ligacao
							WHERE hd_motivo_ligacao = $hd_motivo;";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$destinatario = pg_fetch_result($res, 0, destinatarios);
					$destinatario = json_decode($destinatario,true);
					$destinatario[] = $emails[0];
					$destinatario = json_encode($destinatario);

				} else {
					$destinatario[] = $emails[0];
					$destinatario = json_encode($destinatario);
				}
				pg_query($con,'BEGIN');
				$sql = "UPDATE tbl_hd_motivo_ligacao SET destinatarios = '{$destinatario}' WHERE hd_motivo_ligacao = {$hd_motivo};";
				$res = pg_query($con,$sql);

				if (pg_last_error($con) == 0) {
					pg_query($con,'COMMIT');
					echo "ok|E-mail cadastrado com sucesso!";
				}else{
					pg_query($con,'ROLLBACK');
					echo "erro|Erro no cadastro do e-mail!";
				}

			} else {
				echo "erro|E-mail Inválido!";
			}
			exit;
		}

		if ($_GET['ajax_excluir_email']) {
			$hd_motivo = $_GET['hd_motivo'];
			$email = $_GET['email'];
			$chave = $_GET['chave'];
			$linha = $_GET['linha'];

			$dest_exclui[$chave] = $email;

			if (!empty($email) ) {
				$sql = "SELECT destinatarios
							FROM tbl_hd_motivo_ligacao
							WHERE hd_motivo_ligacao = $hd_motivo;";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) {
					$destinatario = pg_fetch_result($res, 0, destinatarios);
					$destinatario = json_decode($destinatario,true);
				}
				$destinatario = array_diff($destinatario, $dest_exclui);

				if (empty($destinatario)) {
					$destinatario = "null";
				}else{
					$destinatario = json_encode($destinatario);
					$destinatario = "'{$destinatario}'";
				}

				pg_query($con,'BEGIN');
				$sql = "UPDATE tbl_hd_motivo_ligacao SET destinatarios = {$destinatario} WHERE hd_motivo_ligacao = {$hd_motivo};";
				$res = pg_query($con,$sql);

				if (pg_last_error($con) == 0) {
					pg_query($con,'COMMIT');
					echo "ok|E-mail excluído com sucesso!";
				}else{
					pg_query($con,'ROLLBACK');
					echo "erro|Erro na exclusão do e-mail!";
				}

			} else {
				echo "erro|E-mail Inválido!";
			}
			exit;
		}
	}

	if(!empty($btn_acao)){

		if($login_fabrica >= 169 && !in_array($login_fabrica, array(172))){
			if(strlen(trim($classificacao)) == 0 && $login_fabrica != 189){
				$msg_erro .= "Selecione a {$text_class}.";
			}

			if(in_array($login_fabrica, array(169,170))){
				if(strlen(trim($sub_classificacao)) == 0){
					$msg_erro .= "Selecione a Aba.";
				}
			}
			//foreach ($classificacao as $key => $value) {
				//$sql = "SELECT descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} AND categoria ~* '{$value}' $cond";
				//$sql = "SELECT descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$classificacao} $cond";

			if(strlen(trim($msg_erro)) == 0){
				if($classificacao > 0 AND $moduloProvidencia){
					if(!empty($hd_chamado_motivo)){
						$cond = " AND hd_motivo_ligacao <> {$hd_chamado_motivo} ";
					}
					if(in_array($login_fabrica, array(169,170))){
						$cond_169_170 = " AND natureza = {$sub_classificacao} ";
					}

					$sql = "SELECT descricao FROM tbl_hd_motivo_ligacao
							WHERE fabrica = {$login_fabrica}
							AND hd_classificacao = {$classificacao}
							AND descricao = '{$descricao}'
							$cond";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res) > 0){
						$msg_erro .= "Já existe essa {$text_prov} cadastrada";
					}

				}
			}
		}else{
			if($classificacao > 0 AND $moduloProvidencia AND !in_array($login_fabrica, array(125))){
				if(!empty($hd_chamado_motivo)){
					$cond = " AND hd_motivo_ligacao <> {$hd_chamado_motivo} ";
				}
				$sql = "SELECT descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} AND hd_classificacao = {$classificacao} $cond";

				$res = pg_query($con,$sql);
				$desc_prov = pg_fetch_result($res, 0, 'descricao');

				if(pg_num_rows($res) > 0){

					$sql = "SELECT descricao FROM tbl_hd_classificacao WHERE hd_classificacao = {$classificacao}";
					$res = pg_query($con,$sql);
					$msg_erro .= "A {$text_class} ". pg_fetch_result($res, 0, 'descricao') ." já foi selecionada para a {$text_prov} {$desc_prov} <br />";

				}
			}
		}

		$ativo = (empty($ativo)) ? "f" : "t";
		$classificacao = (empty($classificacao)) ? "null" : $classificacao;

		if(strlen($msg_erro) == 0){

			//$classificacao = implode("|",$classificacao);
			$query_admin = "";
			$value_admin = "";

			if (in_array($login_fabrica, array(151))  ) {
				$query_admin = "texto_email_admin,";
				$value_admin = "'$texto_email_admin',";
				$up_admin = "texto_email_admin = '$texto_email_admin',";

			}

			if(in_array($login_fabrica, array(169,170))){
				if(empty($abre_os)){
					$abre_os = 'f';
				}

				if (empty($obriga_os)) {
					$obriga_os = 'f';
				}
				if (empty($contas_nacionais)){
					$contas_nacionais = 'f';
				}
				if (empty($os_cortesia)){
					$os_cortesia = 'f';
				}
				$insert_169_170 = " natureza, tipo_registro, categoria, ";
				$value_169_170 	= " $sub_classificacao, '$contas_nacionais' , '$os_cortesia' ,";
				$update_169_170 = " natureza = {$sub_classificacao}, tipo_registro = '{$contas_nacionais}', categoria = '{$os_cortesia}' ,";
			}else{
				$insert_169_170 = "";
				$value_169_170 	= "";
				$update_169_170 = "";
				$abre_os = "f";
				$obriga_os = "f";
			}

			$insert_189 = "";
			$value_189 	= "";
			$update_189 = "";
			if(in_array($login_fabrica, array(189))){
				$insert_189 = ",prazo_horas";
				$value_189 	= ",'$prazo_horas'";
				$update_189 = ",prazo_horas='$prazo_horas'";
			}

			if(count($situacao) > 0){
				

				if($btn_acao == "cadastrar"){
					$arr['situacoes'] = $situacao;
					$situacao = json_encode($arr);

					$campos_adicionais = ",campos_adicionais";
					$value_campos_adicionais = ",'$situacao'";
				}else if($btn_acao == "atualizar"){
					$sql = "SELECT campos_adicionais FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = $hd_chamado_motivo";
					$res = pg_query($con,$sql);
					$array_campos_adicionais = pg_fetch_result($res, 0, 'campos_adicionais');

					if(!empty($array_campos_adicionais)){
						$array_campos_adicionais = json_decode($array_campos_adicionais,true);
					}

					$array_campos_adicionais['situacoes'] = $situacao;
					$array_campos_adicionais = json_encode($array_campos_adicionais);
					$campos_adicionais = ",campos_adicionais = '{$array_campos_adicionais}'";
				}
			}

			$sql_auditor = "SELECT 	descricao,
						categoria,
						fabrica,
						tipo_registro,
						ativo,
						texto_email,
						texto_sms,
						prazo_dias,
						data_input,
						destinatarios,
						texto_email_admin,
						hd_classificacao,
						abre_os,
						natureza,
						os_obrigatoria,
						prazo_horas,
						campos_adicionais 
					FROM tbl_hd_motivo_ligacao 
					WHERE hd_motivo_ligacao = {$hd_chamado_motivo}";
			$res_auditor = pg_query($con,$sql_auditor);
			$auditor_antes = pg_fetch_assoc($res_auditor);
			$AuditorLog->retornaDadosSelect($sql_auditor);

			if($btn_acao == "cadastrar"){
				if(empty($descricao)){
					$msg_erro = "Informe a descrição do motivo";
				} else{
					if($login_fabrica == 50){
						$valida_descricao = strtoupper($descricao);

						$sql = "SELECT hd_motivo_ligacao,descricao,ativo,texto_email,texto_email_admin, texto_sms,prazo_dias,prazo_horas,categoria FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica ORDER BY ativo DESC, descricao ASC";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							for ($i=0; $i < $res; $i++) {
								$res_descricao = strtoupper(pg_fetch_result($res, $i, 'descricao'));

								if($valida_descricao == $res_descricao){
									$msg_erro = "Já existe esse Tipo de Atendimento cadastrado.";
								}
							}
						}
					}

					if(strlen(trim($msg_erro)) == 0){

						$sql = "INSERT INTO tbl_hd_motivo_ligacao (
								 fabrica,
								 descricao,
								 hd_classificacao,
								 ativo,
								 texto_email,
								 $query_admin
								 texto_sms,
								 $insert_169_170
								 prazo_dias,
								 abre_os,
								 os_obrigatoria
								 $insert_189
								 $campos_adicionais
								) VALUES (
								 $login_fabrica,
								 '$descricao',
								 $classificacao,
								 '$ativo',
								 '$texto_email',
								 $value_admin
								 '$texto_sms',
								 $value_169_170
								 $prazo,
								 '$abre_os',
								 '$obriga_os'
								 $value_189
								 $value_campos_adicionais
								) RETURNING hd_motivo_ligacao";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						if(empty($msg_erro)){
							$hd_chamado_motivo = pg_fetch_result($res,0,'hd_motivo_ligacao');
			            	
							$AuditorLog->retornaDadosSelect()->EnviarLog("insert", 'tbl_hd_motivo_ligacao',"$login_fabrica*$hd_chamado_motivo");
							$msg = "Cadastrado com sucesso";
						}
					}
				}

			} else if($btn_acao == "atualizar"){

								$sql = "UPDATE tbl_hd_motivo_ligacao SET
									descricao 	= '$descricao',
									ativo 		= '$ativo',
									texto_email = '$texto_email',
									$up_admin
									texto_sms 	= '$texto_sms',
									$update_169_170
									prazo_dias 	= $prazo,
									hd_classificacao = $classificacao,
									abre_os = '$abre_os',
									os_obrigatoria = '$obriga_os'
									$update_189
									$campos_adicionais
						WHERE hd_motivo_ligacao = $hd_chamado_motivo
						AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if(empty($msg_erro)){

					$sql_auditor = "SELECT 	descricao,
											categoria,
											fabrica,
											tipo_registro,
											ativo,
											texto_email,
											texto_sms,
											prazo_dias,
											data_input,
											destinatarios,
											texto_email_admin,
											hd_classificacao,
											abre_os,
											natureza,
											os_obrigatoria,
											prazo_horas,
											campos_adicionais 
								FROM tbl_hd_motivo_ligacao 
								WHERE hd_motivo_ligacao = {$hd_chamado_motivo}";
					$res_auditor = pg_query($con,$sql_auditor);
			        	$auditor_depois = pg_fetch_assoc($res_auditor);
	            			$auditor_depois['data_alteracao'] = date('d-m-Y h:i:s');
	            			$auditor_depois['admin'] = $login_admin;
	            			$AuditorLog->retornaDadosSelect()->EnviarLog("update", 'tbl_hd_motivo_ligacao',"$login_fabrica*$hd_chamado_motivo");

					$msg = "Atualizado com sucesso";
				}

			} else if($btn_acao == "excluir"){
				if(empty($hd_chamado_motivo)){
					$msg_erro = "Informe o motivo a ser excluído";
				} else {
					$sql = "DELETE FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = $hd_chamado_motivo and fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					if(empty($msg_erro)){
						$msg = "Excluído com sucesso";
					} else {
						$msg_erro = "Motivo já cadastrado em atendimento, não é possível excluí-lo";
					}
				}

			}
           	if (!empty($btn_acao)) {
               unset($descricao);
               unset($texto_email);
               unset($texto_email_admin);
               unset($texto_sms);
               unset($ativo);
               unset($hd_chamado_motivo);
               unset($sub_classificacao);
			   unset($abre_os);
			   unset($obriga_os);
			   unset($contas_nacionais);
			   unset($os_cortesia);
               $classificacao = '';
               $prazo = 0;
               $prazo_horas = 0;
           	}
		}

	}

	$title = ($login_fabrica == 74 )? "Cadastro de Classe de Atendimento":"Cadastro de Motivos de Ligação Call Center";
	$title = (in_array($login_fabrica,array(30,151)))? "Cadastro de Providência de Atendimento": $title;
	if($login_fabrica == 50){ $title = " Cadastro de Tipo Atendimento Call-Center"; }
	if($login_fabrica == 162){ $title = "Cadastro Providência de Atendimento"; }
	if($login_fabrica == 189){ $title = "Cadastro {$text_prov} de Atendimento"; }

	$nome_cadastro = ($login_fabrica == 74 )? "Classe de Atendimento":"Motivo Ligação";
	$nome_cadastro = ($moduloProvidencia)? "{$text_prov} de Atendimento": $nome_cadastro;
	if($login_fabrica == 50){ $nome_cadastro = "Tipo de Atendimento Call-Center"; }
	if($login_fabrica == 162){ $nome_cadastro = "Providência"; }
	if(in_array($login_fabrica, array(169,170))){
		$nome_cadastro = "Providência Call-Center";
	}
	$layout_menu = 'cadastro';
	include 'cabecalho.php';

?>
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

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.col_left{
	padding-left: 200px;
	width:200px;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso table tr td{
    font: 12px Arial; color: rgb(89, 109, 155);
    border-collapse: collapse;
}

div.list_multiple_checkbox{
    width: 270px;
    margin: 0;
    padding: 5px 2px;
    text-align: center;
    overflow:auto;
}

.list_multiple_checkbox legend{
    padding: 0 5px;
}

.list_multiple_checkbox ul{
    margin: 0;
    padding: 0;
    list-style: none;
}
.list_multiple_checkbox ul li{
    margin: 0;
    padding: 0;
    list-style: none;
    width: 135px;
    text-align: left;
    float: left;
    font-size: 8px;
    font-weight: bold;
}
</style>

<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript">

	$(function(){

		Shadowbox.init();

		$( "#texto_sms" ).keyup(function() {
	        var caracteresDigitados = parseInt($(this).val().length);
	        var divisao = caracteresDigitados /160;
	        var qtdeSms = Math.ceil(divisao);
	        var textoMostrar = caracteresDigitados+"/"+qtdeSms;
	        $("#qtde_caracteres").text(textoMostrar);

	        var texto = $(this).val();
	        if(texto.match(/\[_tabela_produtos_\]/g)){
	        	var replace = texto.replace("[_tabela_produtos_]", "");
	        	$(this).val(replace);
        		alert("Não é possível utilizar essa tag para envio de SMS");
    		}
	      
		});

		$('#prazo').numeric();
		$('#prazo_horas').numeric();

		<?php
		if (in_array($login_fabrica, array(169, 170))) {
		?>
			$("#abre_os").change(function() {
				if ($(this).is(":checked") && $("#obriga_os").is(":checked")) {
					$("#obriga_os").prop({ checked: false });
				}
			});

			$("#obriga_os").change(function() {
				if ($(this).is(":checked") && $("#abre_os").is(":checked")) {
					$("#abre_os").prop({ checked: false });
				}
			});

			$("#classificacao").change(function(){

				var id = $(this).val();

				$.ajax({
					url: "callcenter_motivo_ligacao_cadastro.php",
					type: "GET",
					data: {ajax_classificacao_aba: "true", classificacao : id},
					complete: function(data){
						var retorno = data.responseText;
						$("#sub_classificacao").html(retorno);
					}
				});
			});
		<?php
		}
		?>

		$("#linkAuditor").click(function(){

			var id = $("#hd_chamado_motivo").val();
			
			Shadowbox.open({
                content: "relatorio_log_alteracao_new.php?parametro=tbl_hd_motivo_ligacao&id="+id,
                player: "iframe",
                width: 900,
                height: 450
            });
		})

	});

	function carregaCampos(id){
		$('html, body').animate({scrollTop: 100}, 200, 'linear');

		$.ajax({
			url: "callcenter_motivo_ligacao_cadastro.php",
			type: "GET",
			data: {ajax_campos: "true", hd_motivo_ligacao : id},
			complete: function(data){
				var retorno = data.responseText;
				retorno = retorno.split("|");

				if(retorno[0] == "ok"){
					$("#descricao").val(retorno[1]);
					$("#hd_chamado_motivo").val(id);
					$("#btn_acao").val('atualizar');
					$('.btn_gravar').val('Atualizar');
					$('#btn-excluir').show();
					$('#btn-novo').show();
					$('.sucesso').hide();
					$('.msg_erro').hide();
					if(retorno[2] == "t"){
						$("#ativo").attr("checked",true);
					} else {
						$("#ativo").attr("checked",false);
					}

					if(retorno[3] != ""){
						$("#texto_email").val(retorno[3]);
					}else{
						$("#texto_email").val("");
					}

					if(retorno[4] != ""){
						$("#texto_sms").val(retorno[4]);
					}else{
						$("#texto_sms").val("");
					}

					if(retorno[5] != ""){
						$("#prazo").val(retorno[5]);
					}else{
						$("#prazo").val("");
					}

					if(retorno[6] != ""){
						$('#classificacao').val(retorno[6]);
					}else{
						$('#classificacao').val('');
					}

					<?php
					if (in_array($login_fabrica, array(151))) { ?>
						if(retorno[7] != ""){
							$("#texto_email_admin").val(retorno[7]);
						}else{
							$("#texto_email_admin").val("");
						}
					<?php
					}

					if(in_array($login_fabrica, array(169,170))){
					?>
						$("#sub_classificacao").val(retorno[7]);
						if(retorno[8] == "t"){
							$("#abre_os").attr("checked",true);
						} else {
							$("#abre_os").attr("checked",false);
						}
						if (retorno[9] == "t") {
							$("#obriga_os").attr("checked", true);
						} else {
							$("#obriga_os").attr("checked", false);
						}

						if (retorno[10] == "t") {
							$("#contas_nacionais").attr("checked", true);
						} else {
							$("#contas_nacionais").attr("checked", false);
						}
						if (retorno[11] == 't'){
							$("#os_cortesia").attr("checked", true);
						}else{
							$("#os_cortesia").attr("checked", false);
						}
					<?php
					}
					?>

					<?php if (in_array($login_fabrica, array(189))) { ?>
						if (retorno[7] != "") {
							$("#prazo_horas").val(retorno[7]);
						} else {
							$("#prazo_horas").val("");
						}
					<?php }?>

					<?php if (in_array($login_fabrica, array(30))) { ?>
						if (retorno[7] != "") {
							var situacao = JSON.parse(retorno[7]);
							var situacoes = situacao.situacoes;
							$("input[name^=situacao]").attr({checked : false});

							situacoes.forEach(function(item){
								$("#"+item).attr({checked : true});
							});
						} 

						$("#log").show();
					<?php }?>
					
				}
			}
		});

	}

	function carregaCampos2(descricao,ativo,hd_chamado_motivo,texto_email,texto_sms,prazo,categoria,texto_email_admin){

		$("#descricao").val(descricao);
		$("#hd_chamado_motivo").val(hd_chamado_motivo);
		$("#btn_acao").val('atualizar');

		if(ativo == "t"){
			$("#ativo").attr("checked",true);
		} else {
			$("#ativo").attr("checked",false);
		}

		if(texto_email != ""){
			$("#texto_email").val(texto_email);
		}else{
			$("#texto_email").val("");
		}

		if(texto_email_admin != ""){
			$("#texto_email_admin").val(texto_email_admin);
		}else{
			$("#texto_email_admin").val("");
		}

		if(texto_sms != ""){
			$("#texto_sms").val(texto_sms);
		}else{
			$("#texto_sms").val("");
		}

		if(prazo != ""){
			$("#prazo").val(prazo);
		}else{
			$("#prazo").val("");
		}

		$("input[name^=classificacao]").attr({"checked" : false});

		if(categoria != ""){
			categoria = categoria.split("|");

			for (i = 0; i < categoria.length; i++) {
			    $("input[value="+categoria[i]+"]").attr({"checked":"checked"});
			}
		}
	}

	<?php
	if ($login_fabrica == 151) { ?>
		function atualizaEmail(linha,hd_motivo){
			$("#div_email_"+linha).remove();
			$.ajax({
				url: "callcenter_motivo_ligacao_cadastro.php",
				type: "GET",
				data: {
					ajax_email: "true",
					linha:linha,
					hd_motivo:hd_motivo
				},
				beforeSend: function(){
					$("#div_detalhe_"+linha).after("<tr id='img_"+linha+"'><td colspan = 3 ><img src='a_imagens/ajax-loader.gif'></td></tr>");
				},
				complete: function(data){
					var dados = data.responseText;

					$("#img_"+linha).remove();
					$("#div_detalhe_"+linha).after(dados);
					$("#div_sinal_"+linha).html("-");
				}
			});
		}


		function chamaAjax(linha,hd_motivo) {

			if ($("#div_sinal_" + linha).html() == '+') {
				$.ajax({
					url: "callcenter_motivo_ligacao_cadastro.php",
					type: "GET",
					data: {
						ajax_email: "true",
						linha:linha,
						hd_motivo:hd_motivo
					},
					beforeSend: function(){
						$("#div_detalhe_"+linha).after("<tr id='img_"+linha+"'><td colspan = 3 ><img src='a_imagens/ajax-loader.gif'></td></tr>");
					},
					complete: function(data){
						var dados = data.responseText;

						$("#img_"+linha).remove();
						$("#div_detalhe_"+linha).after(dados);
						$("#div_sinal_"+linha).html("-");
					}
				});
			} else {
				$("#div_email_"+linha).remove();
				$("#div_sinal_" + linha).html('+');
			}
		}

		function gravaEmail(linha,hd_motivo) {
			var email = $("#cad_email_"+linha).val();
			if (email != '' ) {
				$.ajax({
					url: "callcenter_motivo_ligacao_cadastro.php",
					type: "GET",
					data: {
						ajax_gravar_email: "true",
						email:email,
						hd_motivo:hd_motivo
					},
					beforeSend: function(){
						$("#btn_grava_email_"+linha).attr('disabled',true);
					},
					complete: function(data){
						var dados = data.responseText;
						dados = dados.split("|");

						if (dados[0] == 'ok') {
							alert(dados[1]);
							atualizaEmail(linha,hd_motivo);
						} else {
							alert(dados[1]);
						}
						$("#btn_grava_email_"+linha).removeAttr('disabled');
					}
				});
			} else {
				alert('Favor informar o e-mail!');
				$("#cad_email_"+linha).focus();
			}
		}

		function excluiEmail(linha,hd_motivo,chave) {
			var email = $("#email_"+hd_motivo+"_"+chave).val();
			if (email != '' ) {
				$.ajax({
					url: "callcenter_motivo_ligacao_cadastro.php",
					type: "GET",
					data: {
						ajax_excluir_email: "true",
						email:email,
						hd_motivo:hd_motivo,
						chave:chave,
						linha:linha
					},
					beforeSend: function(){
						$("#btn_excluir_email_"+linha+"_"+chave).attr('disabled',true);
					},
					complete: function(data){
						var dados = data.responseText;
						dados = dados.split("|");

						if (dados[0] == 'ok') {
							alert(dados[1]);
							atualizaEmail(linha,hd_motivo);
						} else {
							alert(dados[1]);

						}
						$("#btn_excluir_email_"+linha+"_"+chave).removeAttr('disabled');
					}
				});
			} else {
				alert('Favor informar o e-mail!');
				$("#cad_email_"+linha).focus();
			}
		}
	<?php
	}
	?>

	<?php if(in_array($login_fabrica, array(169,170))){ ?>

                function tipoProtocloClassificacaoOrigem(){
                    
                    var obj = $("#tipo_protocolo");

                    $.ajax({
                        url: "callcenter_motivo_ligacao_cadastro.php",
                        type: "POST",
                        data: {"ajax_tipo_protocolo_classificacao": true, tipo_protocolo: $(obj).val()},
                        complete: function(data){

                            var retorno = data.responseText;

                            if(retorno.length > 0){
                                $("#classificacao").html(retorno);
                            }
                        }
                    });
                }

                    $(function(){tipoProtocloClassificacaoOrigem()});
            <? } ?>


</script>

<?php
	if($moduloProvidencia){
?>
		<div class="texto_avulso">

			<p><b>Para os textos que serão enviados por Email e SMS deverão ser utilizados alguns coringas para que possam ser substiuídos automaticamente com os dados do atendimento:</b></p>
			<table align="center">
				<tr>
					<td align="left"> <b>[_consumidor_]</b> </td>
					<td align="left"> - Para que seja informado o nome do consumidor na mensagem</td>
				</tr>
				<tr>
					<td align="left"> <b>[_protocolo_]</b> </td>
					<td align="left"> - Para que seja informado o número do protocolo na mensagem</td>
				</tr>
				<tr>
					<td align="left"> <b>[_rastreio_]</b> </td>
					<td align="left"> - Para que seja informado o número de objeto fornecido pelos Correios na mensagem</td>
				</tr>
				<tr>
					<td align="left"> <b>[_codigo_postagem_]</b> </td>
					<td align="left"> - Para que seja informado o código de postagem fornecido pelos Correios na mensagem</td>
				</tr>
				<tr>
					<td align="left"> <b>[_ordem_servico_]</b> </td>
					<td align="left"> - Para que seja informado o número da ordem de serviço na mensagem</td>
				</tr>
				<?php if($login_fabrica == 151) : ?>
					<tr>
						<td align="left"> <b>[_voucher_]</b> </td>
						<td align="left"> - Para que seja informado o código do vocuher gerado no protocolo na mensagem</td>
					</tr>
					<tr>
						<td align="left"> <b>[_produto_]</b> </td>
						<td align="left"> - Para que seja informado o código e a descrição do produto na mensagem </td>
					</tr>
					<tr>
						<td align="left"> <b>[_quantidade_produto_]</b> </td>
						<td align="left"> - Para que seja informado a quantidade do produto na mensagem</td>
					</tr>
					<tr>
						<td align="left"> <b>[_tabela_produtos_]</b> </td>
						<td align="left"> - Para que seja mostrado uma tabela com o código, descrição e quantidade dos produtos (Utilizado quando há mais de um produto no atendimento)</td>
					</tr>
				<?php endif; ?>
			</table>
		</div>
		<br />
<?php
	}
?>

<?php if(!empty($msg_erro)){?>
	<table align="center" width="700" class="msg_erro" style="margin-bottom: 1px;">
		<tr><td><?php echo $msg_erro; ?></td></tr>
	</table>
<?php } ?>

<?php if(!empty($msg)){?>
	<table align="center" width="700" class="sucesso" style="margin-bottom: 1px;">
		<tr><td><?php echo $msg; ?></td></tr>
	</table>
<?php } ?>

<form name="frm_cadastro" method="post">
	<table align="center" class="formulario" width="700" border="0">

		<caption class="titulo_tabela">Cadastro <?=$nome_cadastro?></caption>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<td class="col_left">
				Descrição <br>
				<input type="text" name="descricao" id="descricao" value="<?=$descricao?>" size="45" class="frm">
			</td>
		<?php if(!$moduloProvidencia){ ?>
			<td>
				<?php
					$checked = (empty($ativo)) ? "f" : "checked";
				?>
				Ativo <br>
				<input type="checkbox" name="ativo" id="ativo" value="t" class="frm" <?=$checked?>>
			</td>
		<? } ?>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>
	<?php
		if($moduloProvidencia){ 

			if(in_array($login_fabrica, array(169,170))){ ?>

			<tr>
				<td class="col_left">
	            	Tipo Protocolo <br />
	            	<select id='tipo_protocolo' name='tipo_protocolo' style="width: 275px " class="frm" onchange="tipoProtocloClassificacaoOrigem()">
	                	<option value=''></option>
	                    <?php
	                        $sql = "SELECT 	hd_tipo_chamado, 
	                        				descricao 
	                        			FROM tbl_hd_tipo_chamado 
	                        			WHERE fabrica = {$login_fabrica} 
	                        			AND ativo 
	                        		ORDER BY descricao";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res)>0) {

								while($dados = pg_fetch_array($res)){
									$selected = '';
									if ($tipo_protoolo == $dados[0]) {
										$selected = 'selected';
									}
									echo "<option value='$dados[0]' $selected>$dados[1]</option>";
								}
							}
	                    ?>
	   				</select>
	            </td>
			</tr>
	<?php }

		if(!in_array($login_fabrica, [90,189])){ ?>
		<tr>
            <td class="col_left">
               <?php echo $text_class;?> <br />
               <select id='classificacao' name='classificacao' style="width: 275px " class="frm">
                   <option value=''></option>
                   <?php
                   		
                   		$sql = "SELECT
                                   hd_classificacao,
                                   descricao
								FROM tbl_hd_classificacao WHERE fabrica = {$login_fabrica} ORDER by descricao";

                        	
						$res = pg_query($con,$sql);
						if (pg_num_rows($res)>0) {
							while($dado = pg_fetch_array($res)){
								$selected = '';
								if ($classificacao == $dado[0]) {
									$selected = 'selected';
								}
								echo "<option value='$dado[0]' $selected>$dado[1]</option>";
							}
						}

                   ?>
   				</select>
            </td>
        </tr>
        <?php } ?>
        <?php if(in_array($login_fabrica, array(169,170))){ ?>
        	<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="col_left">
	            	Aba <br />
	            	<select id='sub_classificacao' name='sub_classificacao' style="width: 275px " class="frm">
	                	<option value=''></option>
	                    <?php
	                        $sql = "SELECT
	                                   natureza, nome, descricao
									FROM tbl_natureza
									WHERE fabrica = {$login_fabrica}
									AND ativo IS TRUE
									ORDER by descricao ASC";
							$res = pg_query($con,$sql);
							if (pg_num_rows($res)>0) {
								while($dados = pg_fetch_array($res)){
									$selected = '';
									if ($sub_classificacao == $dados[0]) {
										$selected = 'selected';
									}
									echo "<option value='$dados[0]' $selected>$dados[2]</option>";
								}
							}
	                    ?>
	   				</select>
	            </td>
			</tr>
		<?php } ?>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td class="col_left">
				<table>
					<?php if ($login_fabrica != 189) {?>
					<tr>
						<td>
							Prazo de retorno (Dias) <br>
							<input type="number" style='text-align: center;' min="0" name="prazo" id="prazo" value="<?=$prazo?>" class="frm">
						</td>
						<td>
							<?php
							    $checked = (empty($ativo)) ? "f" : "checked";
							?>
							Ativo <br>
							<input type="checkbox" name="ativo" id="ativo" value="t" class="frm" <?=$checked?>>
						</td>

						<?php if($login_fabrica == 30){ 
								$sql = "SELECT status FROM tbl_hd_status WHERE fabrica = {$login_fabrica}";
								$res = pg_query($con,$sql);
								$situacoes = pg_fetch_all($res);
						?>
								<td>
									Situação <br>
									<?php 
										foreach ($situacoes as $key => $value) {
											echo "<input type='checkbox' name='situacao[]' id='{$value['status']}' value='{$value['status']}'>{$value['status']}<br>";
										}
									?>

								</td>
						<?php } ?>
					</tr>
					<?php } else { ?>
					<tr>
						<td>
							Prazo de retorno (horas) <br>
							<input type="number" style='text-align: center;' min="0" name="prazo_horas" id="prazo_horas" value="<?=$prazo_horas?>" class="frm">
						</td>
						<td>
							<?php
							    $checked = (empty($ativo)) ? "f" : "checked";
							?>
							Ativo <br>
							<input type="checkbox" name="ativo" id="ativo" value="t" class="frm" <?=$checked?>>
						</td>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<?php
		if (in_array($login_fabrica, array(151))) { ?>
			<tr>
				<td colspan="2" class="col_left">
					Texto Email Consumidor<br>
					<textarea name="texto_email" id="texto_email" class="frm" cols="42" rows="4"><?=$texto_email?></textarea>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class="col_left">
					Texto Email Admin<br>
					<textarea name="texto_email_admin" id="texto_email_admin" class="frm" cols="42" rows="4"><?=$texto_email_admin?></textarea>
				</td>
			</tr>
		<?php
		} else if(!in_array($login_fabrica, [90])) { ?>
			<tr>
				<td colspan="2" class="col_left">
					Texto Email <br>
					<textarea name="texto_email" id="texto_email" class="frm" cols="42" rows="4"><?=$texto_email?></textarea>
				</td>
			</tr>

		<?php
		}
		if (!in_array($login_fabrica, [90,178,184,198,200])) { ?>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class="col_left">
					Texto SMS <br>
					<textarea name="texto_sms" id="texto_sms" class="frm" cols="42" rows="4"><?=$texto_sms?></textarea>
					<label id="qtde_caracteres"></label>
				</td>
			</tr>
		<?php } ?>
		<?php if(in_array($login_fabrica, array(169,170))){ ?>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class='col_left'>
					<?php
						$checked_abre_os = (empty($abre_os)) ? "f" : "checked";
					?>
					Abre Ordem de serviço/Pré-OS &nbsp;&nbsp;
					<input type="checkbox" name="abre_os" id="abre_os" value="t" class="frm" <?=$checked_abre_os?>>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class='col_left'>
					<?php
						$checked_contas_nacionais = (empty($contas_nacionais)) ? "f" : "checked";
					?>
					Garantia - Contas Nacionais &nbsp;&nbsp;
					<input type="checkbox" name="contas_nacionais" id="contas_nacionais" value="t" class="frm" <?=$checked_contas_nacionais?>>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class='col_left'>
					<?php
						$checked_obriga_os = (empty($obriga_os)) ? "f" : "checked";
					?>
					Obrigar vínculo de Ordem de serviço &nbsp;&nbsp;
					<input type="checkbox" name="obriga_os" id="obriga_os" value="t" class="frm" <?=$checked_obriga_os?>>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td colspan="2" class='col_left'>
					<?php
						$checked_os_cortesia = (empty($os_cortesia)) ? "f" : "checked";
					?>
					Abre OS cortesia &nbsp;&nbsp;
					<input type="checkbox" name="os_cortesia" id="os_cortesia" value="t" class="frm" <?=$checked_os_cortesia?>>
				</td>
			</tr>
		<?php } ?>
		<tr><td colspan="2">&nbsp;</td></tr>
	<?php
		}
	?>

		<tr>
			<td colspan="2" align="center">
				<input type="hidden" name="btn_acao" id="btn_acao" value="">

				<input type="hidden" name="hd_chamado_motivo" id="hd_chamado_motivo" value="<?=$hd_chamado_motivo?>">

				<input type="button" style='display:none;' id='btn-novo' value="Novo" onclick="javascript: location.href=window.location.href;">

				<input type="button" class='btn_gravar' value="Gravar" onclick="javascript: if(document.frm_cadastro.btn_acao.value ==''){document.frm_cadastro.btn_acao.value='cadastrar'; document.frm_cadastro.submit();} else{document.frm_cadastro.btn_acao.value='atualizar'; document.frm_cadastro.submit();}">
				<?php if(!in_array($login_fabrica, array(169,170))){ ?>
					<input type="button" id='btn-excluir' value="Excluir" style="display:none;" onclick="javascript: if(confirm('Deseja realmente excluir esse motivo?')){document.frm_cadastro.btn_acao.value='excluir';document.frm_cadastro.submit();}">
				<?php } ?>
			</td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr><td colspan="2" id="log" style="display:none;"><a rel='shadowbox' href="javascript:void(0)" id='linkAuditor' class='btn btn-mini btn-warning btn-block' name='btnAuditorLog'>Visualizar Log</a></td></tr>

</table>
</form>
<br><br>


<table align="center" width="700" class="tabela">
	<caption class="titulo_tabela"><?=$nome_cadastro?></caption>

	<tr class="titulo_coluna">
		<?php
		if ($login_fabrica == 151) { ?>
			<th></th>
		<?php
		}
		?>

		<th>Descrição</th>
		<?php if(in_array($login_fabrica, array(125, 174,183))){ ?>
			<th>Classificação</th>
		<?php } ?>
		<?php if(in_array($login_fabrica, array(169,170))){ ?>
			<th>Classificação</th>
			<th>Aba</th>
			<th>Abre Ordem de serviço/Pré-OS</th>
			<th>Obrigar vínculo de Ordem de Serviço</th>
			<th>Garantia - Contas Nacionais</th>
			<th>Os Cortesia</th>
		<?php } ?>
		<th>Status</th>
		<?php if(in_array($login_fabrica, array(30))){ ?>
				<th>Situação</th>
		<?php } ?>
	</tr>

	<?php
		if(in_array($login_fabrica, array(169,170))){
			$campos_169_170 = ", tbl_natureza.natureza, tbl_natureza.descricao AS descricao_subclassificacao,
								tbl_hd_classificacao.descricao AS descricao_classificao,
								tbl_hd_motivo_ligacao.tipo_registro
			";
			$join_169_170 = "JOIN tbl_natureza ON tbl_natureza.natureza = tbl_hd_motivo_ligacao.natureza
								AND tbl_natureza.fabrica = {$login_fabrica}
							JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_motivo_ligacao.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica}
			";
		}

		if(in_array($login_fabrica, array(125, 174,183))){
			$campo_classificacao = " , tbl_hd_classificacao.descricao AS descricao_classificao ";
			$joinClassificacao = " JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_motivo_ligacao.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica} ";
		}


		$sql = "SELECT 	tbl_hd_motivo_ligacao.hd_motivo_ligacao,
						tbl_hd_motivo_ligacao.descricao,
						tbl_hd_motivo_ligacao.ativo,
						tbl_hd_motivo_ligacao.texto_email,
						tbl_hd_motivo_ligacao.texto_sms,
						tbl_hd_motivo_ligacao.prazo_dias,
						tbl_hd_motivo_ligacao.prazo_horas,
						tbl_hd_motivo_ligacao.categoria,
						tbl_hd_motivo_ligacao.hd_classificacao,
						tbl_hd_motivo_ligacao.abre_os,
						tbl_hd_motivo_ligacao.os_obrigatoria,
						tbl_hd_motivo_ligacao.campos_adicionais
						$campos_169_170
						$campo_classificacao
					FROM tbl_hd_motivo_ligacao
					$join_169_170
					$joinClassificacao
					WHERE tbl_hd_motivo_ligacao.fabrica = $login_fabrica ORDER BY tbl_hd_motivo_ligacao.descricao ASC, tbl_hd_motivo_ligacao.ativo DESC";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			for($i = 0; $i < pg_num_rows($res); $i++){
				unset($descricao_classificao);
				unset($descricao_subclassificacao);

				$descricao   = pg_result($res,$i,'descricao');
				$ativo       = pg_result($res,$i,'ativo');
				$hd_motivo   = pg_result($res,$i,'hd_motivo_ligacao');
				$texto_email = pg_result($res,$i,'texto_email');
				$texto_email = pg_result($res,$i,'texto_email_admin');
				$texto_sms 	 = pg_result($res,$i,'texto_sms');
				$prazo 		 = pg_result($res,$i,'prazo_dias');
				$prazo_horas = pg_result($res,$i,'prazo_horas');
				$categoria	= pg_result($res,$i,'categoria');
				$campos_adicionais	= json_decode(pg_result($res,$i,'campos_adicionais'),true);
				$hd_classificacao = pg_fetch_result($res, $i, 'hd_classificacao');

				if(in_array($login_fabrica, array(125, 174,183))){
					$descricao_classificao = pg_fetch_result($res, $i, 'descricao_classificao');
				}

				if(in_array($login_fabrica, array(169,170))){
					$abre_os 	= pg_fetch_result($res, $i, 'abre_os');
					$obriga_os  = pg_fetch_result($res, $i, "os_obrigatoria");
					$descricao_classificao = pg_fetch_result($res, $i, 'descricao_classificao');
					$descricao_subclassificacao = pg_fetch_result($res, $i, 'descricao_subclassificacao');
					$contas_nacionais = pg_fetch_result($res, $i, 'tipo_registro');
					$os_cortesia = pg_fetch_result($res, $i, 'categoria');

					if($abre_os == 't'){
						$xabre_os = "SIM";
					}else{
						$xabre_os = "NÃO";
					}

					if ($obriga_os == "t") {
						$xobriga_os = "SIM";
					} else {
						$xobriga_os = "NÃO";
					}

					if ($contas_nacionais == 't'){
						$xcontas_nacionais = "SIM";
					}else{
						$xcontas_nacionais = "NÃO";
					}

					if ($os_cortesia == 't'){
						$xos_cortesia = "SIM";
					}else{
						$xos_cortesia = "NÃO";
					}
				}
				$status = ($ativo == "t") ? "Ativo" : "Inativo";

				$texto_email = (strlen(trim($texto_email))>0) ? $texto_email : null;
				$texto_email_admin = (strlen(trim($texto_email_admin))>0) ? $texto_email_admin : null;

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				//$onclick = "onclick=\"carregaCampos('$descricao', '$ativo', '$hd_motivo', '$texto_email', '$texto_sms', '$prazo', '$categoria' );\"";
				$onclick = "onclick='carregaCampos({$hd_motivo});'";
	?>
				<tr bgcolor="<?php echo $cor;?>" id='div_detalhe_<?=$i?>'>
					<?php
					if ($login_fabrica == 151) { ?>
						<td onmouseover="this.style.cursor='pointer';" onclick="chamaAjax(<?=$i?>,<?=$hd_motivo?>)">
							<div id=div_sinal_<?=$i?>>+</div>
						</td>
					<?php
					}
					?>
					<td align="left"><a href="javascript: void(0);" <?=$onclick?> ><?php echo $descricao;?></a></td>
					<?php if(in_array($login_fabrica, array(125, 174,183))){ ?>
						<td align="left"><?=$descricao_classificao?></td>
					<?php } ?>
					<?php if(in_array($login_fabrica, array(169,170))){ ?>
						<td align="left"><?=$descricao_classificao?></td>
						<td align="left"><?=$descricao_subclassificacao?></td>
						<td align="center"><?=$xabre_os?></td>
						<td align="center"><?=$xobriga_os?></td>
						<td align="center"><?=$xcontas_nacionais?></td>
						<td align="center"><?=$xos_cortesia?></td>
					<?php } ?>
					<td><?php echo $status;?></td>
					<?php if(in_array($login_fabrica, array(30))){ ?>
						<td align="left"><?=implode(', ',$campos_adicionais['situacoes'])?></td>
					<?php } ?>
				</tr>
	<?php
			}
		}
	?>
</table>
<?php include 'rodape.php'; ?>
