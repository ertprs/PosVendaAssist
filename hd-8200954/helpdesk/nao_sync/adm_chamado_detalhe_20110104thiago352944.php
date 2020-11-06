<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}

$msg_erro = '';
$msg_sucesso = 'Dados atualizados com sucesso';

function getMaiorInteracao($tabela,$hd_chamado,$rodada = false){
	
	$max = 0;
	$cond = null;
	
	if($rodada>0){
		$cond = ' AND rodada = '.$rodada;
	}
	
	$sql = 'SELECT MAX(interacao) AS maior_interacao FROM '.$tabela.' WHERE hd_chamado = '.$hd_chamado.' '.$cond.';';
	$res = pg_query($sql);
	$num = pg_num_rows($res);

	if($num){
		$max = pg_fetch_result($res,0,'maior_interacao');
	}

	if($max)
		return $max;
	else
		return 0;
	
}

if($_GET['inicio_trabalho'] == 1){
	
	$hd_chamado = (int) $_GET['hd_chamado'];
	
	$res = pg_begin();

	$sql = "UPDATE tbl_hd_chamado SET admin_desenvolvedor = $login_admin WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
	
	//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
	$sql =" UPDATE tbl_hd_chamado_item
			SET termino = current_timestamp
			WHERE hd_chamado_item in(SELECT hd_chamado_item
						 FROM tbl_hd_chamado_item
						 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
							AND termino IS NULL
						 ORDER BY hd_chamado_item DESC
						 LIMIT 1 );";
	$res = pg_query($con,$sql);

	$sql ="INSERT INTO tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin,
				status_item,
				interno
			) VALUES (
				$hd_chamado,
				'Início do Trabalho',
				$login_admin,
				'$status',
				't'
			);";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	//--======================================================================
	if (strlen($msg_erro) == 0) {

		$sql = "SELECT hd_chamado_atendente,
						hd_chamado
						FROM tbl_hd_chamado_atendente
						WHERE admin = $login_admin
						AND   data_termino IS NULL
						ORDER BY hd_chamado_atendente DESC LIMIT 1";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if ( pg_num_rows($res) > 0) {
			$hd_chamado_atendente = pg_fetch_result($res, 0, 'hd_chamado_atendente');
			$hd_chamado_atual     = pg_fetch_result($res, 0, 'hd_chamado');
		}

		if($hd_chamado_atual <> $hd_chamado){ // se tiver interagindo em outro chamado eu insiro um novo
			$sql = "INSERT INTO tbl_hd_chamado_atendente(
											hd_chamado ,
											admin      ,
											data_inicio
									)VALUES(
									$hd_chamado       ,
									$login_admin      ,
									CURRENT_TIMESTAMP
									)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		}
	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}
	
}

if($_GET['termino_trabalho'] == 1){

	$hd_chamado = $_GET['hd_chamado'];

	$res = pg_begin();

	$sql =" SELECT hd_chamado_item
			  FROM tbl_hd_chamado_item
			 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
				AND termino IS NULL
			ORDER BY hd_chamado_item desc
			LIMIT 1 ;";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	if( pg_num_rows($res)>0){

		$hd_chamado_item = pg_fetch_result($res, 0, 'hd_chamado_item');

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	$sql ="SELECT	hd_chamado_atendente  ,
					hd_chamado            ,
					data_termino
			FROM tbl_hd_chamado_atendente
			WHERE admin = $login_admin
			ORDER BY data_termino DESC
			LIMIT 1";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
		$xhd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
		$data_termino          = pg_fetch_result($res, 0, 'data_termino');
		$hd_chamado_atendente  = pg_fetch_result($res, 0, 'hd_chamado_atendente');
		if(strlen($data_termino)==0) {/*atendente estava trabalhando com algum chamado*/
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							comentario,
							interno,
							admin,
							data,
							termino,
							atendimento_telefone
						) VALUES (
							$hd_chamado,
							'Término de Trabalho',
							't',
							$login_admin,
							current_timestamp,
							current_timestamp,
							'f'
						);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			if(strlen($msg_erro)==0){
				$sql = "UPDATE tbl_hd_chamado_atendente SET data_termino=CURRENT_TIMESTAMP
						 WHERE hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}
}

#Trecho alterado por Thiago Contardi HD: 304470
#Caso seja para adicionar algum requisito, chama este pedaço em ajax para inserir mais um requisito
if($_GET['adReq'] == 1){
	$numero = $_GET['numero'];
	?>
	<tr id="requisito_<?php echo $_GET['numero'];?>">
		<td><?php echo $numero;?></td>
		<td class="sub_label">
			<textarea name="requisitos[]" style="width:450px;height:150px"></textarea>
		</td>
		<td class="sub_label" align="center">
			<select name="analiseRequisitos[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td class="sub_label" align="center">
			<select name="testeRequisitos[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delRequisito('<?php echo $_GET['numero'];?>')"> X </a>
		</td>
	</tr>
	<?php
	exit;

#Caso seja para adicionar alguma análise, chama este pedaço em ajax para inserir mais uma análise
}elseif($_GET['adAnalise'] == 1){
	$numero = $_GET['numero'];
	?>
	<tr id="analise_<?php echo $_GET['numero'];?>">
		<td><?php echo $numero;?></td>
		<td>
			<textarea name="analises[]" style="width:450px;height:150px"></textarea>
		</td>
		<td align="center">
			<select name="desenvAnalise[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td align="center">
			<select name="testeAnalise[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delAnalise('<?php echo $_GET['numero'];?>')"> X </a>
		</td>
	</tr>
	<?php
	exit;

#Caso seja para remover algum requisito, chama este pedaço em ajax para remover do banco esse requisito
}elseif($_GET['delReq'] == 1){
	$idReq = $_GET['idReq'];
	$sql = 'UPDATE tbl_hd_chamado_requisito SET excluido = TRUE WHERE hd_chamado_requisito = '.$idReq;
	$res = pg_query($con,$sql);
	exit;
#Caso seja para remover alguma análise, chama este pedaço em ajax para remover do banco essa análise
}elseif($_GET['delAnalise'] == 1){
	$idAnalise = $_GET['idAnalise'];
	$sql = 'UPDATE tbl_hd_chamado_analise SET excluido = TRUE WHERE hd_chamado_analise = '.$idAnalise;
	$res = pg_query($con,$sql);
	exit;
#Ao adicionar algula correção
}elseif($_GET['adCorrecao']==1){
	$rodadaCorrecao = $_GET['rodadaCorrecao'];
	$numero = $_GET['numero'];
	?>
	<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $numero;?>">
		<td><?php echo $numero;?></td>
		<td>
			<textarea name="descricaoCorrecaos[]" style="width:230px;height:100px"></textarea>
		</td>
		<td>
			<textarea name="analiseCorrecaos[]" style="width:230px;height:100px"></textarea>
		</td>
		<td align="center">
			<select name="gravidadeCorrecaos[]">
				<option value="1">Leve</option>
				<option value="5">Normal</option>
				<option value="10">Grave</option>
			</select>
		</td>
		<td align="center">
			<select name="atendidoCorrecaos[]">
				<option value="NULL">Não aplicável</option>
				<option value="TRUE">Sim</option>
				<option value="FALSE">Não</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delCorrecao(<?php echo $rodadaCorrecao;?>,<?php echo $numero;?>)"> X </a>
		</td>
	</tr>

	<?php
	exit;
}elseif($_GET['adRodada'] == 1){

	$rodadaCorrecao = $_GET['rodadaCorrecao'];
	$numero = ($_GET['numero']) ? $_GET['numero'] : 1;
	
	?>
	<form name='frm_correcao_<?php echo $rodadaCorrecao;?>' action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $_GET['hd_chamado'];?>" method='post'>
		<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
			<tr class="titulo_cab">
				<th align="left" colspan="5">
					Rodada <?php echo $rodadaCorrecao;?>
				</th>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<th align="left" width="35%">
					Descrição
				</th>
				<th align="left" width="35%">
					Análise
				</th>
				<th align="left">
					Gravidade
				</th>
				<th align="left">
					Atendido
				</th>
			</tr>
			<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $numero;?>">
				<td><?php echo $numero;?></td>
				<td>
					<textarea name="descricaoCorrecaos[]" style="width:230px;height:100px"></textarea>
				</td>
				<td>
					<textarea name="analiseCorrecaos[]" style="width:230px;height:100px"></textarea>
				</td>
				<td align="center">
					<select name="gravidadeCorrecaos[]">
						<option value="1">Leve</option>
						<option value="5">Normal</option>
						<option value="10">Grave</option>
					</select>
				</td>
				<td align="center">
					<select name="atendidoCorrecaos[]">
						<option value="NULL">Não aplicável</option>
						<option value="TRUE">Sim</option>
						<option value="FALSE">Não</option>
					</select>
				</td>
				<td valign="middle" align="center">
					<a href="javascript:void(0)" class="xAnalise" onclick="analise.delCorrecao(<?php echo $rodadaCorrecao;?>,<?php echo $numero;?>)"> X </a>
				</td>
			</tr>

		</table>

		<p class="finalizarCorrecoes">
			<label>
				<input type="checkbox" name="rodadaFinalizada" value="TRUE" />
				Finalizar Rodada de Correções
			</label>
		</p>

		<div class="salvarAnalise">
			<input type="hidden" name="_POST" value="correcao1" />
			<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
			<input type="hidden" name="chamado" value="<?php echo $_GET['hd_chamado'];?>" />
			<input type="submit" name="salvar" value="Salvar" />
		</div>

		</form>
	
	<input type="hidden" id="numeroCorrecao_<?php echo $rodadaCorrecao;?>" value="<?php echo ++$numero;?>" />
	[ <a href="javascript:void(0)" onclick="analise.addCorrecao(<?php echo $rodadaCorrecao;?>)">Adicionar Correção</a> ]
	<?php
	exit;

}elseif($_GET['delCorrecao'] == 1){

	$idCorrecao = $_GET['idCorrecao'];
	$sql = 'DELETE FROM tbl_hd_chamado_correcao WHERE hd_chamado_correcao = '.$idCorrecao;
	$res = pg_query($con,$sql);
	exit;

}

#Ao enviar o formulário de Requisitos, faz as validações
if($_POST['_POST'] == 'req1'){
	
	$totalRequisitos = count($_POST['requisitos']);
	#Maior interação dos requisitos
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_requisito',$hd_chamado);
	$maiorInteracao++;
	
	$res = pg_begin($con);
	
	for($i=0;$i<$totalRequisitos;$i++){

		$idRequisito = isset($_POST['idRequisitos'][$i]) ? $_POST['idRequisitos'][$i] : null;
		$requisito = $_POST['requisitos'][$i];
		$requisito = filter_var($requisito, FILTER_SANITIZE_STRING);

		$analiseRequisitos = ($_POST['analiseRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeRequisitos = ($_POST['testeRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idRequisito>0){

			$sql = 'SELECT analise,teste,admin_analise,admin_teste 
					  FROM tbl_hd_chamado_requisito 
					 WHERE hd_chamado_requisito = '.$idRequisito;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$analise = pg_fetch_result($res,0,'analise');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_analise = pg_fetch_result($res,0,'admin_analise');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminAnalise = ($analiseRequisitos == 'TRUE' && $analise == 'f') ? $login_admin : $admin_analise;
				$adminTeste = ($testeRequisitos == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminAnalise)
					$adminAnalise = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_requisito 
						   SET  analise = '.$analiseRequisitos.',
								admin_analise = '.$adminAnalise.',
								teste = '.$testeRequisitos.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_requisito = '.$idRequisito.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}else{
			
			$adminAnalise = ($analiseRequisitos == 'TRUE') ? $login_admin : 'NULL';
			$adminTeste = ($testeRequisitos == 'TRUE') ? $login_admin : 'NULL';

			$sql =	'INSERT INTO tbl_hd_chamado_requisito (
							hd_chamado,
							requisito,
							analise,
							admin_analise,
							teste,
							admin_teste,
							interacao
						) VALUES (
							'.$_POST['chamado'].',
							\''.$requisito.'\',
							'.$analiseRequisitos.',
							'.$adminAnalise.',
							'.$testeRequisitos.',
							'.$adminTeste.',
							'.$maiorInteracao.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			$maiorInteracao++;
		}

	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}

}


#Ao enviar o formulário de Requisitos, faz as validações
if($_POST['_POST'] == 'requisitoTeste1'){
	
	$totalRequisitos = count($_POST['requisitos']);

	$res = pg_begin($con);

	for($i=0;$i<$totalRequisitos;$i++){
		
		$idRequisito = isset($_POST['idRequisitos'][$i]) ? $_POST['idRequisitos'][$i] : null;
		$requisito = $_POST['requisitos'][$i];
		$requisito = filter_var(utf8_decode($requisito), FILTER_SANITIZE_STRING);
		$analiseRequisitos = ($_POST['analiseRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeRequisitos = ($_POST['testeRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idRequisito>0){

			$sql = 'SELECT analise,teste,admin_analise,admin_teste 
					  FROM tbl_hd_chamado_requisito 
					 WHERE hd_chamado_requisito = '.$idRequisito;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$analise = pg_fetch_result($res,0,'analise');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_analise = pg_fetch_result($res,0,'admin_analise');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminAnalise = ($analiseRequisitos == 'TRUE' && $analise == 'f') ? $login_admin : $admin_analise;
				$adminTeste = ($testeRequisitos == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminAnalise)
					$adminAnalise = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_requisito 
						   SET  analise = '.$analiseRequisitos.',
								admin_analise = '.$adminAnalise.',
								teste = '.$testeRequisitos.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_requisito = '.$idRequisito.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}

	}

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;

}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['_POST'] == 'analise1'){

	$totalAnalises = count($_POST['analises']);
	#Maior interação das Análise
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_analise',$hd_chamado);
	$maiorInteracao++;

	$res = pg_begin($con);

	for($i=0;$i<$totalAnalises;$i++){
		
		$idAnalise = isset($_POST['idAnalise'][$i]) ? $_POST['idAnalise'][$i] : null;
		$analise = $_POST['analises'][$i];
		$analise = filter_var($analise, FILTER_SANITIZE_STRING);
		$desenvAnalise = ($_POST['desenvAnalise'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeAnalise = ($_POST['testeAnalise'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idAnalise>0){

			$sql = 'SELECT analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste 
					  FROM tbl_hd_chamado_analise 
					 WHERE hd_chamado_analise = '.$idAnalise;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$desenvolvimento = pg_fetch_result($res,0,'desenvolvimento');
				$admin_desenvolvimento = pg_fetch_result($res,0,'admin_desenvolvimento');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminDesenvolvimento = ($desenvAnalise == 'TRUE' && $desenvolvimento == 'f') ? $login_admin : $admin_desenvolvimento;
				$adminTeste = ($testeAnalise == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminDesenvolvimento)
					$adminDesenvolvimento = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_analise 
						   SET  desenvolvimento = '.$desenvAnalise.',
								admin_desenvolvimento = '.$adminDesenvolvimento.',
								teste = '.$testeAnalise.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_analise = '.$idAnalise.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}else{
			
			$adminDesenvolvimento = ($desenvAnalise == 'TRUE') ? $login_admin : 'NULL';
			$adminTeste = ($testeAnalise == 'TRUE') ? $login_admin : 'NULL';

			$sql =	'INSERT INTO tbl_hd_chamado_analise (
							hd_chamado,
							analise,
							desenvolvimento,
							admin_desenvolvimento,
							teste,
							admin_teste,
							interacao
						) VALUES (
							'.$_POST['chamado'].',
							\''.$analise.'\',
							'.$desenvAnalise.',
							'.$adminDesenvolvimento.',
							'.$testeAnalise.',
							'.$adminTeste.',
							'.$maiorInteracao.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			$maiorInteracao++;
		}

	}

	#Alterações para a tabela de chamado
	$plano_teste = filter_var($_POST['plano_teste'], FILTER_SANITIZE_STRING);
	$analiseTexto = filter_var($_POST['analiseTexto'], FILTER_SANITIZE_STRING);

	$sql = 'UPDATE  tbl_hd_chamado 
			   SET  plano_teste = \''.$plano_teste.'\',
					analise = \''.$analiseTexto.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	
	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}
	exit;

}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['_POST'] == 'analiseTeste1'){

	$totalAnalises = count($_POST['analises']);

	$res = pg_begin($con);

	for($i=0;$i<$totalAnalises;$i++){
		
		$idAnalise = isset($_POST['idAnalise'][$i]) ? $_POST['idAnalise'][$i] : null;
		$analise = $_POST['analises'][$i];
		$analise = filter_var(utf8_decode($analise), FILTER_SANITIZE_STRING);
		$desenvAnalise = ($_POST['desenvAnalise'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeAnalise = ($_POST['testeAnalise'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idAnalise>0){

			$sql = 'SELECT analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste 
					  FROM tbl_hd_chamado_analise 
					 WHERE hd_chamado_analise = '.$idAnalise;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$desenvolvimento = pg_fetch_result($res,0,'desenvolvimento');
				$admin_desenvolvimento = pg_fetch_result($res,0,'admin_desenvolvimento');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminDesenvolvimento = ($desenvAnalise == 'TRUE' && $desenvolvimento == 'f') ? $login_admin : $admin_desenvolvimento;
				$adminTeste = ($testeAnalise == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminDesenvolvimento)
					$adminDesenvolvimento = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_analise 
						   SET  desenvolvimento = '.$desenvAnalise.',
								admin_desenvolvimento = '.$adminDesenvolvimento.',
								teste = '.$testeAnalise.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_analise = '.$idAnalise.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}

	}

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;

}

if($_POST['_POST'] == 'teste1'){

	$procedimento_teste = $_POST['procedimento_teste'];
	$procedimento_teste = filter_var(utf8_decode($procedimento_teste), FILTER_SANITIZE_STRING);
	$comentario_desenvolvedor  = $_POST['comentario_desenvolvedor'];
	$comentario_desenvolvedor = filter_var(utf8_decode($comentario_desenvolvedor), FILTER_SANITIZE_STRING);

	$sql = 'UPDATE  tbl_hd_chamado 
			   SET  procedimento_teste = \''.$procedimento_teste.'\',
					comentario_desenvolvedor = \''.$comentario_desenvolvedor.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;
}

if($_POST['_POST'] == 'efetivacao1'){

	$procedimento_efetivacao = $_POST['procedimento_efetivacao'];
	$procedimento_efetivacao = filter_var(utf8_decode($procedimento_efetivacao), FILTER_SANITIZE_STRING);
	
	$sql = 'UPDATE  tbl_hd_chamado 
			   SET  procedimento_efetivacao = \''.$procedimento_efetivacao.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;
}

if($_POST['_POST'] == 'validacao1'){
	
	$validacao = $_POST['validacao'];
	$validacao = filter_var(utf8_decode($validacao), FILTER_SANITIZE_STRING);

	$sql = 'UPDATE  tbl_hd_chamado 
			   SET  validacao = \''.$validacao.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;
}

if($_POST['_POST'] == 'orcamento1'){
	
	$horas_suporte = ($_POST['horas_suporte']) ? $_POST['horas_suporte'] : 'NULL';
	$horas_analise = ($_POST['horas_analise']) ? $_POST['horas_analise'] : 'NULL';
	$prazo_horas = ($_POST['prazo_horas']) ? $_POST['prazo_horas'] : 'NULL';
	$horas_teste = ($_POST['horas_teste']) ? $_POST['horas_teste'] : 'NULL';
	$horas_efetivacao = ($_POST['horas_efetivacao']) ? $_POST['horas_efetivacao'] : 'NULL';

	$sql = 'UPDATE  tbl_hd_chamado 
			   SET  horas_suporte = '.$horas_suporte.',
					horas_analise = '.$horas_analise.',
					prazo_horas = '.$prazo_horas.',
					horas_teste = '.$horas_teste.',
					horas_efetivacao = '.$horas_efetivacao.'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	
	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		echo $msg_sucesso;
	}
	exit;
}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['_POST'] == 'correcao1'){

	$res = pg_begin($con);

	$rodada = $_POST['rodada'];
	$chamado = $_POST['chamado'];

	$totalCorrecaos = count($_POST['descricaoCorrecaos']);
	#Maior interação das Análise
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_correcao',$chamado,$rodada);
	$maiorInteracao++;

	for($i=0;$i<$totalCorrecaos;$i++){
		
		$idCorrecao = isset($_POST['idCorrecao'][$i]) ? $_POST['idCorrecao'][$i] : null;
		$descricaoCorrecaos = $_POST['descricaoCorrecaos'][$i];
		$descricaoCorrecaos = filter_var(utf8_decode($descricaoCorrecaos), FILTER_SANITIZE_STRING);
		$analiseCorrecaos = $_POST['analiseCorrecaos'][$i];
		$analiseCorrecaos = filter_var(utf8_decode($analiseCorrecaos), FILTER_SANITIZE_STRING);
		$gravidadeCorrecaos = $_POST['gravidadeCorrecaos'][$i];
		$atendidoCorrecaos = $_POST['atendidoCorrecaos'][$i];
		$rodadaFinalizada = ($_POST['rodadaFinalizada']) ? $_POST['rodadaFinalizada'] : 'FALSE';

		if($idCorrecao>0){

			$sql = 'SELECT atendido,admin_atendido
					  FROM tbl_hd_chamado_correcao 
					 WHERE hd_chamado_correcao = '.$idCorrecao;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$atendido = pg_fetch_result($res,0,'atendido');
				$admin_atendido = pg_fetch_result($res,0,'admin_atendido');

				$adminAtendido = ($atendidoCorrecaos == 'TRUE' && $atendido == 'f') ? $login_admin : $admin_atendido;
			
				if(!$adminAtendido)
					$adminAtendido = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_correcao 
						   SET  descricao = \''.$descricaoCorrecaos.'\',
								rodada = '.$rodada.',
								analise = \''.$analiseCorrecaos.'\',
								gravidade = '.$gravidadeCorrecaos.',
								atendido = '.$atendidoCorrecaos.',
								admin_atendido = '.$adminAtendido.',
								rodada_finalizada = '.$rodadaFinalizada.'
						 WHERE  hd_chamado_correcao = '.$idCorrecao.';';
				
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}else{
			
			$adminAtendido = ($atendidoCorrecaos == 'TRUE') ? $login_admin : 'NULL';

			$sql =	'INSERT INTO tbl_hd_chamado_correcao (
							hd_chamado,
							descricao,
							rodada,
							analise,
							gravidade,
							atendido,
							admin_atendido,
							rodada_finalizada,
							interacao
						) VALUES (
							'.$chamado.',
							\''.$descricaoCorrecaos.'\',
							'.$rodada.',
							\''.$analiseCorrecaos.'\',
							'.$gravidadeCorrecaos.',
							'.$atendidoCorrecaos.',
							'.$adminAtendido.',
							'.$rodadaFinalizada.',
							'.$maiorInteracao.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
			$maiorInteracao++;
		}

	}
	
	if(strlen($msg_erro) > 0){
		if($_POST['at'] == 1){
			echo $msg_erro;
		}
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		if($_POST['at'] == 1){
			echo $msg_sucesso;
			exit;
		}
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}

}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['_POST'] == 'check1'){

	$totalCheck = count($_POST['checklist']);

	if($totalCheck>0){

		$res = pg_begin($con);
		
		$sql =	'DELETE FROM tbl_hd_chamado_checklist WHERE hd_chamado = '.$_POST['chamado'].';';
		$res = pg_query($con,$sql);

		for($i=0;$i<$totalCheck;$i++){
			
			$check = $_POST['checklist'][$i];
			$atendido = $_POST['atendido'][$i];

			$sql =	'INSERT INTO tbl_hd_chamado_checklist (
							hd_chamado,
							checklist,
							atendido
						) VALUES (
							'.$_POST['chamado'].',
							'.$check.',
							'.$atendido.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
		}
		
		if(strlen($msg_erro) > 0){
			echo $msg_erro;
			$res = pg_rollBack();
		}else{
			echo $msg_sucesso;
			$res = pg_commit();
		}
		exit;
	}

}
#Fim Trecho alterado por Thiago Contardi HD: 304470

$atualiza_hd = $_GET['atualiza_hd'];
if(strlen($atualiza_hd)==0){
	$atualiza_hd = $_POST['atualiza_hd'];
}

if(strlen($atualiza_hd)>0){
	$hd   = $_GET['hd'];
	$hr   = $_GET['hr'];
	$prazo= $_GET['prazo'];
	if(strlen($hd)>0 and strlen($hr)>0){
		$sql = "UPDATE tbl_hd_chamado set hora_desenvolvimento = $hr
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		echo (strlen($msg_erro)==0) ? "Atualizado com Sucesso!" : "Ocorreu o seguinte erro $msg_erro";
	}
	if(strlen($hd)>0 and strlen($prazo)>0){
		$sql = "UPDATE tbl_hd_chamado set prazo_horas= $prazo
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		echo (strlen($msg_erro)==0) ? "Prazo atualizado!" : "Ocorreu o seguinte erro $msg_erro";
	}
	exit;
}

$atualiza_previsao_termino = trim($_GET['atualiza_previsao_termino']);
if(strlen($atualiza_previsao_termino)==0){
	$atualiza_previsao_termino = trim($_POST['atualiza_previsao_termino']);
}

if(strlen($atualiza_previsao_termino)>0){
	$hd   = trim($_GET['hd']);
	$data = trim($_GET['data_previsao']);
	if(strlen($hd)>0 and strlen($data)>15 AND $data != "____-__-__ __:__"){
		$Xdata = substr($data,6,4)."-".substr($data,3,2)."-".substr($data,0,2)." ".substr($data,11,5);
		$sql = "UPDATE tbl_hd_chamado SET previsao_termino = '$Xdata'
				WHERE hd_chamado = $hd
				AND fabrica_responsavel = $login_fabrica";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if(strlen($msg_erro)==0){
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                   ,
							comentario                   ,
							admin
						) VALUES (
							$hd                                                  ,
							'A previsão de término do atendimento deste chamado é para $data ' ,
							$login_admin
						);";
			$res = pg_query($con,$sql);
		}
		echo (strlen($msg_erro)==0) ? "Atualizado com Sucesso!" : "Ocorreu o seguinte erro: $msg_erro";
	}
	exit;
}

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

$begin_aberto = 0;

function pg_begin($savepoint=null)
{ // BEGIN function pg_begin
	global $con, $begin_aberto;

//	Se já há um begin aberto, não abrir um outro, o banco retorna um Warning
	if ($begin_aberto > 0 and is_null($savepoint)) return false;
	$sql = (is_null($savepoint)) ? 'BEGIN TRANSACTION' :"SAVEPOINT $savepoint";
	$res = @pg_query($con, $sql);
	if (is_resource($res)) {
		$begin_aberto+= 1;
		return $res;
	}
	return false;
} // END function pg_begin

function pg_commit($savepoint=null)
{ // BEGIN function pg_commit
	global $con, $begin_aberto;
	if (!$begin_aberto) return false;
	$sql = (is_null($savepoint)) ? 'COMMIT' : "RELEASE SAVEPOINT $savepoint";
	$res = @pg_query($con, $sql);
	if (is_resource($res)) {
		$begin_aberto-= 1;
		return $res;
	}
	return false;
}

function pg_rollBack($savepoint=null)
{ // BEGIN function pg_rollBack
	global $con, $begin_aberto;
	$sql = (is_null($savepoint)) ? 'ROLLBACK TRANSACTION' : "ROLLBACK TO SAVEPOINT $savepoint";
	$res = @pg_query($con, $sql);
	if (is_resource($res)) {
		$begin_aberto-= 1;
		return $res;
	}
	return false;
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['btn_tranferir']) $btn_tranferir = trim ($_POST['btn_tranferir']);

if (strlen ($btn_tranferir) > 0) {
	if($_POST['transfere'])           { 
		$transfere         = trim ($_POST['transfere']);
	}
	$data_resolvido = "";
	if($status == 'Resolvido'){
		$data_resolvido = " data_resolvido = current_timestamp ,";
	}
	$sql =" UPDATE tbl_hd_chamado
			SET status = '$status' ,
				$data_resolvido
				atendente = $transfere
			WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
}

if(strlen($hd_chamado)>0){
	$sql =" SELECT SUM(CASE WHEN data_termino IS NULL THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio )
			FROM tbl_hd_chamado_atendente WHERE hd_chamado = $hd_chamado;";
	$res = pg_query($con, $sql);
	if( pg_num_rows($res)>0)
	$horas= pg_fetch_result($res, 0, 0);
}


if(strlen($_POST['btn_telefone']) > 0) { // HD 39347

	$res = pg_begin();

	$sql =" SELECT hd_chamado_item
		FROM tbl_hd_chamado_item
		WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
			AND termino IS NULL
		ORDER BY hd_chamado_item desc
		LIMIT 1 ;";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	if( pg_num_rows($res)>0){

		$hd_chamado_item = pg_fetch_result($res, 0, 'hd_chamado_item');

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	$sql ="SELECT	hd_chamado_atendente  ,
					hd_chamado            ,
					data_termino
			FROM tbl_hd_chamado_atendente
			WHERE admin = $login_admin
			ORDER BY data_termino DESC
			LIMIT 1";
	$res = pg_query($con,$sql);

	if( pg_num_rows($res)>0){
		$xhd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
		$data_termino          = pg_fetch_result($res, 0, 'data_termino');
		$hd_chamado_atendente  = pg_fetch_result($res, 0, $hd_chamado_atendente);
		if(strlen($data_termino)==0) {/*atendente estava trabalhando com algum chamado*/
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                   ,
							comentario                   ,
							interno                      ,
							admin                        ,
							data                         ,
							termino                      ,
							atendimento_telefone
						) VALUES (
							$xhd_chamado                                                  ,
							'Chamado interrompido para atendimento de telefone'           ,
							't'                                                           ,
							$login_admin                                                  ,
							current_timestamp                                             ,
							current_timestamp                                             ,
							't'
						);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			if(strlen($msg_erro)==0){
				$sql = "UPDATE tbl_hd_chamado_atendente SET data_termino=CURRENT_TIMESTAMP
						WHERE hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}
	$sql = "INSERT INTO tbl_hd_chamado_atendente(
				hd_chamado ,
				admin      ,
				data_inicio,
				atendimento_telefone
				)VALUES(
				$xhd_chamado       ,
				$login_admin       ,
				CURRENT_TIMESTAMP  ,
				't'
				)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$xhd_chamado");
	}
} // HD 39347

if (strlen ($btn_acao) > 0) {
	if($_POST['comentario'])          {$comentario			= trim($_POST['comentario']);}
	if($_POST['transfere'])           {$transfere			= trim($_POST['transfere']);}
	if($_POST['admin_desenvolvedor']) {
		$admin_desenvolvedor	= trim($_POST['admin_desenvolvedor']);
	}else{
		$admin_desenvolvedor	= 'NULL';
	}
	if($_POST['status'])              {$status				= trim($_POST['status']);}
	if($_POST['categoria'])           {$categoria			= trim($_POST['categoria']);}
	if($_POST['sequencia'])           {$sequencia			= trim($_POST['sequencia']);}
	if($_POST['interno'])             {$interno				= trim($_POST['interno']);}
	if($_POST['exigir_resposta'])     {$exigir_resposta		= trim($_POST['exigir_resposta']);}
	if($_POST['hora_desenvolvimento']){$hora_desenvolvimento= trim($_POST['hora_desenvolvimento']);}
	if($_POST['cobrar'])              {$cobrar				= trim($_POST['cobrar']);}
	if($_POST['prioridade'])          {$prioridade			= trim($_POST['prioridade']);}

	if($_POST['prazo_horas'])         {$prazo_horas			= trim($_POST['prazo_horas']);}
	if($_POST['tipo_chamado'])        {$tipo_chamado		= trim($_POST['tipo_chamado']);}

	if($interno){
		unset($_POST['exigir_resposta'],$exigir_resposta);
	}

	if(strlen($categoria)==0){
		$msg_erro = "Escolha a categoria";
	}
	$xprioridade  = ($prioridade=="t") ? "'t'" : "'f'";
	$xcobrar      = ($cobrar=="t") ? "'t'" : "'f'";
	$xprazo_horas = (strlen($prazo_horas)>0) ? "$prazo_horas" : "null";
	$xtipo_chamado= (strlen($tipo_chamado)>0) ? "$tipo_chamado" :  "null";

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$sql = "SELECT categoria , status, atendente FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
	$categoria_anterior = pg_fetch_result($res, 0, 'categoria');
	$status_anterior    = pg_fetch_result($res, 0, 'status');
	$atendente_anterior = pg_fetch_result($res, 0, 'atendente');

	if (strlen($comentario) < 3) $msg_erro = "Comentário muito pequeno";

	if (strlen($hora_desenvolvimento)==0){
		$hora_desenvolvimento = ' NULL ';
	}

	#-------- De Análise para Execução -------
	if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Execução" ;

	if ($sequencia == "AGUARDANDO" AND $status_anterior == "Análise") $status = "Aguard.Execução" ;

	#-------- De Execução para Resolvido -------
	if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
	}

	if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;
	if ($sequencia == "SEGUE" AND $status_anterior == "Aguard.Execução") $status = "Execução" ;

	if ($status == "Novo" AND $status_anterior == "Novo") $status = "Análise";


	$sql = "SELECT exigir_resposta FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado";
	$res = pg_query($con,$sql);
	$xexigir_resposta = pg_fetch_result($res, 0, 0);

	if (strlen($xexigir_resposta)==0) $xexigir_resposta = 'f';

	$exigir_resposta = (strlen ($exigir_resposta) > 0) ? 't' : 'f';
	$xinterno = (strlen ($interno) > 0) ? 't' : 'f';

	if (strlen($msg_erro) == 0){
		$res = pg_begin();
		//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item DESC
							 LIMIT 1 );";
		$res = pg_query($con,$sql);
		if($status == 'Resolvido'){
			$data_resolvido = " data_resolvido = current_timestamp ,";
		}
		$sql =" UPDATE tbl_hd_chamado
				SET status = '$status' ,
					$data_resolvido
					atendente = $transfere,
					admin_desenvolvedor = $admin_desenvolvedor,
					categoria = '$categoria',
					prioridade = $xprioridade,
					tipo_chamado = $xtipo_chamado,
					cobrar = $xcobrar ";
					if($xexigir_resposta=='f'){
						$sql .= ", exigir_resposta = '$exigir_resposta'  ";
					}
		$sql .= " WHERE hd_chamado = $hd_chamado";
		$res = pg_query($con,$sql);

		if ($atendente_anterior <> $transfere) {
			$transferiu = "sim";
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Chamado Transferido',$login_admin, 't')";
			$res = pg_query($con,$sql);
			$sql = "UPDATE tbl_hd_chamado set atendente = $transfere WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
		}

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para <b> $categoria </b>',$login_admin, 't')";
			$res = pg_query($con,$sql);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execução") {
			#$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
			//if($login_admin ==568)	echo "sql-9 $sql<br>";
			#$res = pg_query($con,$sql);
		}

		// HD 17195
		if($transferiu == "sim" and $status != "Cancelado" AND ($status_anterior == "Novo" OR $status_anterior == "Análise") ) {
			$sql="SELECT sv.email AS supervisor_email       ,
						 sv.nome_completo AS supervisor_nome,
						 sv.admin AS supervisor_admin       ,
						 admin.email                        ,
						 admin.nome_completo                ,
						 tbl_hd_chamado.status              ,
						 to_char(previsao_termino,'DD/MM/YYYY') as previsao_termino                   ,
						 titulo
					FROM tbl_hd_chamado
					JOIN tbl_admin sv on tbl_hd_chamado.fabrica=sv.fabrica
					JOIN tbl_admin admin on tbl_hd_chamado.admin= admin.admin
					WHERE sv.help_desk_supervisor IS TRUE
					AND   admin.admin                 <> 19
					AND   sv.email IS NOT NULL
					AND   previsao_termino IS NOT NULL
					AND   hd_chamado=$hd_chamado
					limit 1 ";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) > 0){
				for ($x = 0 ; $x <  pg_num_rows($res) ; $x++){
					$supervisor_email  = pg_fetch_result($res, $x, 'supervisor_email');
					$supervisor_nome   = pg_fetch_result($res, $x, 'supervisor_nome');
					$supervisor_admin  = pg_fetch_result($res, $x, 'supervisor_admin');
					$admin_email       = pg_fetch_result($res, $x, 'email');
					$admin_nome        = pg_fetch_result($res, $x, 'nome_completo');
					$status            = pg_fetch_result($res, $x, 'status');
					$previsao_termino  = pg_fetch_result($res, $x, 'previsao_termino');
					$titulo            = pg_fetch_result($res, $x, 'titulo');

					if(strlen($supervisor_email) > 0 and strlen($admin_email) >0 ){
						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_admin);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email." ; ".$admin_email;

						$assunto       = "O chamado n° $hd_chamado foi aprovado para desenvolvimento e está em estado $status ";

						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$admin_nome,</P>
						<P align=justify>
						Previsão do término deste chamado é $previsao_termino.
						</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
		}

		if($status== "Aprovação" AND ($status_anterior <> "Cancelado" OR $status_anterior <> "Resolvido")){

 			$res = pg_begin();
			$sql="SELECT hora_desenvolvimento,data_aprovacao
					FROM tbl_hd_chamado
					WHERE hd_chamado=$hd_chamado";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) == 0){
				$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
			}else{
				$hora_desenvolvimento = pg_fetch_result($res, 0, 'hora_desenvolvimento');
				$data_aprovacao		  = pg_fetch_result($res, 0, 'data_aprovacao');

				if($hora_desenvolvimento == 0 or strlen($hora_desenvolvimento)==0){
					$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
				}

				if(strlen($data_aprovacao) > 0){
					$sql2="UPDATE tbl_hd_chamado set data_aprovacao = null where hd_chamado=$hd_chamado";
					$res2=pg_query($con,$sql2);
					$msg_erro = pg_last_error($con);

					$sql3="SELECT to_char(current_date,'MM') as mes,to_char(current_date,'YYYY') as ano";
					$res3=pg_query($con,$sql3);
					$mes=pg_fetch_result($res3, 0, 'mes');
					$ano=pg_fetch_result($res3, 0, 'ano');

					$sql4=" UPDATE tbl_hd_franquia set
							hora_utilizada=(hora_utilizada-hora_desenvolvimento)
							FROM  tbl_hd_chamado
							WHERE tbl_hd_chamado.fabrica=tbl_hd_franquia.fabrica
							AND   hd_chamado=$hd_chamado
							AND   mes=$mes
							AND   ano=$ano
							AND   tbl_hd_franquia.periodo_fim is null";
					$res4 = pg_query($con,$sql4);
					$msg_erro = pg_last_error($con);

				}
			}
			if(strlen($msg_erro) ==0){
				$sql=" UPDATE tbl_hd_chamado SET
						data_envio_aprovacao=current_timestamp
						WHERE hd_chamado=$hd_chamado";
				$res = pg_query($con,$sql);

				$sql = "SELECT nome_completo,email,tbl_admin.admin
								FROM tbl_admin
								JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
								WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
								AND tbl_admin.help_desk_supervisor IS TRUE
								AND tbl_admin.email IS NOT NULL
								AND tbl_admin.admin                 <> 19";
				$res = pg_query($con,$sql);
				if ( pg_num_rows($res) > 0) {
					$conta = ($login_fabrica==20) ? "3" :  pg_num_rows($res);
					for($i =0;$i<$conta;$i++) {

						$supervisor_email  = pg_fetch_result($res, $i, 'email');
						$supervisor_nome   = pg_fetch_result($res, $i, 'nome_completo');
						$supervisor_adm    = pg_fetch_result($res, $i, 'admin');

						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email;

						$assunto       = "O chamado n° $hd_chamado está aguardando sua aprovação";

						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>
						Precisamos de sua aprovação em faturamento de horas para continuarmos atendendo o chamado.
						</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";

						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
 			if(strlen($msg_erro) > 0){
				$res = pg_rollBack();
			}else{
				$res = @pg_commit();
 			}
		}

		if (strlen ($comentario) > 0) {
			$sql ="INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						comentario,
						admin,
						status_item,
						interno
					) VALUES (
						$hd_chamado,
						'$comentario',
						$login_admin,
						'$status',
						'$xinterno'
					);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			$res = @pg_query($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
			$hd_chamado_item  = pg_fetch_result($res, 0, 0);

			if (strlen($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {

				$att_max_size = 2097152; // Tamanho máximo do arquivo (em bytes)

				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != ""){
				    // array_search with recursive searching, optional partial matches and optional search by key
				    function array_rfind($needle, $haystack, $partial_matches = false, $search_keys = false) {
				        if(!is_array($haystack)) return false;
				        foreach($haystack as $key=>$value) {
				            $what = ($search_keys) ? $key : $value;
				            if($needle===$what) return $key;
				            else if($partial_matches && @strpos($what, $needle)!==false) return $key;
				            else if(is_array($value) && array_rfind($needle, $value, $partial_matches, $search_keys)!==false) return $key;
				        }
				        return false;
				    }
					$a_tipos = array(
					/* Imagens */
						'bmp'	=> 'image/bmp',
						'gif'	=> 'image/gif',
						'ico'	=> 'image/x-icon',
						'jpg'	=> 'image/jpeg;image/pjpeg',
						'jpeg'	=> 'image/jpeg;image/pjpeg',
						'png'	=> 'image/png;image/x-png',
						'tif'	=> 'image/tiff',
					/* Texto */
						'csv'	=> 'text/comma-separated-values;text/csv;application/vnd.ms-excel',
						'eps'	=> 'application/postscript',
						'pdf'	=> 'application/pdf',
						'ps'	=> 'application/postscript',
						'rtf'	=> 'text/rtf',
						'tsv'	=> 'text/tab-separated-values;text/tsv;application/vnd.ms-excel',
						'txt'	=> 'text/plain',
					/* Office */
						'doc'	=> 'application/msword',
						'ppt'	=> 'application/vnd.ms-powerpoint',
						'xls'	=> 'application/vnd.ms-excel',
						'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
						'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					/* Star/OpenOffice.org */
						'odt'	=> 'application/vnd.oasis.opendocument.text;application/x-vnd.oasis.opendocument.text',
						'ods'	=> 'application/vnd.oasis.opendocument.spreadsheet;application/x-vnd.oasis.opendocument.spreadsheet',
						'odp'	=> 'application/vnd.oasis.opendocument.presentation;application/x-vnd.oasis.opendocument.presentation',
					/* Compactadores */
						'sit'	=> 'application/x-stuffit',
						'hqx'	=> 'application/mac-binhex40',
						'7z'	=> 'application/octet-stream',
						'lha'	=> 'application/octet-stream',
						'lzh'	=> 'application/octet-stream',
						'rar'	=> 'application/octet-stream;application/x-rar-compressed;application/x-compressed',
						'zip'	=> 'application/zip'
					);
					// Pega extensão do arquivo
					$a_att_info	  = pathinfo($arquivo['name']);
					$ext          = $a_att_info['extension'];
					$arquivo_nome = $a_att_info['filename']; // Tira a extensão do nome... PHP 5.2.0+
					$aux_extensao = "'$ext'";

					// Verifica o mime-type do arquivo, ou a extensão
					$tipo = ($arquivo['type'] != '') ? array_rfind($arquivo_type, $a_tipos, true) : array_key_exists($ext, $a_tipos);
					if ($arquivo['type'] == 'application/octet-stream') {
					// Tem navegadores que usam o 'application/octet-stream' para tipos desconhecidos...
						$tipo = array_key_exists($ext, $a_tipos);
					}

					if ($tipo) { // Verifica tamanho do arquivo
						if ($arquivo["size"] > $att_max_size)
							$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
					} else {
						$msg_erro = "Arquivo em formato inválido!";
					}

					if (strlen($msg_erro) == 0) { // Processa o arquivo
						//  Substituir tudo q não for caracteres aceitos para nome de arquivo para '_'
						$arquivo_nome = preg_replace("/[^a-zA-Z0-9_-]/", '_', retira_acentos($arquivo_nome));

						// Gera um nome único para a imagem
						$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item . '-' . strtolower($arquivo_nome) . '.' . $ext;

					}//fim da verificação de erro
					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						if (!move_uploaded_file($arquivo["tmp_name"], $nome_anexo)) $msg_erro = "O arquivo não foi anexado!!!";
					}//fim do upload da do anexo
				}//fim da verificação de existencia no apache
			}//fim de todo o upload

			//--======================================================================
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT hd_chamado_atendente,
								hd_chamado
								FROM tbl_hd_chamado_atendente
								WHERE admin = $login_admin
								AND   data_termino IS NULL
								ORDER BY hd_chamado_atendente DESC LIMIT 1";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				if ( pg_num_rows($res) > 0) {
					$hd_chamado_atendente = pg_fetch_result($res, 0, 'hd_chamado_atendente');
					$hd_chamado_atual     = pg_fetch_result($res, 0, 'hd_chamado');
				}

				if(($hd_chamado_atual <> $hd_chamado) or $transferiu == "sim"){
					//se eu tiver interagindo em outro chamado ou transferindo

					//fecho o chamado_item
					$sql =" UPDATE tbl_hd_chamado_item
							SET termino = current_timestamp
							WHERE hd_chamado_item in(SELECT hd_chamado_item
										 FROM tbl_hd_chamado_item
										 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
											AND termino IS NULL
										 ORDER BY hd_chamado_item desc
										 LIMIT 1 );";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					if(strlen($hd_chamado_atendente)>0){
						$sql = "UPDATE tbl_hd_chamado_atendente
										SET data_termino = CURRENT_TIMESTAMP
										WHERE hd_chamado_atendente = $hd_chamado_atendente
										AND   admin               =  $login_admin
										AND   data_termino IS NULL
										";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
					}
				}
				/*IGOR - 12/08/2008 - SE FOR SUPORTE, NÃO CONTA TEMPO DE ANALISE NO CHAMADO SE NÃO FOR Execução*/
				if($login_admin == 435 and 1==2){
					$sql =" UPDATE tbl_hd_chamado_item
							SET termino = current_timestamp
							WHERE hd_chamado_item in(SELECT hd_chamado_item
										 FROM tbl_hd_chamado_item
										 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
											AND termino IS NULL
										 ORDER BY hd_chamado_item desc
										 LIMIT 1 );";

					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					//fecha o atendimento se tiver algum aberto
					$sql = "UPDATE tbl_hd_chamado_atendente
									SET data_termino = CURRENT_TIMESTAMP
									WHERE hd_chamado_atendente = (
																	SELECT hd_chamado_atendente
																	FROM tbl_hd_chamado_atendente
																	WHERE admin = 435
																	AND   data_termino IS NULL
																	ORDER BY hd_chamado_atendente DESC LIMIT 1
																)
									AND   admin               =  $login_admin
									AND   data_termino IS NULL
									";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}


				if($hd_chamado_atual <> $hd_chamado){ // se tiver interagindo em outro chamado eu insiro um novo
						$sql = "INSERT INTO tbl_hd_chamado_atendente(
														hd_chamado ,
														admin      ,
														data_inicio
												)VALUES(
												$hd_chamado       ,
												$login_admin      ,
												CURRENT_TIMESTAMP
												)";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
						$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
						$res = pg_query($con,$sql);
						$hd_chamado_atendente =  pg_fetch_result($res, 0, 0);
				}
				if($status == 'Resolvido'){
					//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
					$sql =" UPDATE tbl_hd_chamado_item
							SET termino = current_timestamp
							WHERE hd_chamado_item in(SELECT hd_chamado_item
										 FROM tbl_hd_chamado_item
										 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
											AND termino IS NULL
										 ORDER BY hd_chamado_item desc
										 LIMIT 1 );";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					$sql = "UPDATE tbl_hd_chamado_atendente
						SET data_termino = CURRENT_TIMESTAMP
						WHERE admin                = $login_admin
						AND   hd_chamado           = $hd_chamado
						AND   hd_chamado_atendente = $hd_chamado_atendente";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);

					$sql= "UPDATE tbl_controle_acesso_arquivo SET
						data_fim = CURRENT_DATE,
						hora_fim = CURRENT_TIME,
						status   = 'finalizado'
						WHERE hd_chamado = $hd_chamado";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}
			}
		}

		$msg_erro = str_ireplace('ERROR: ', '', $msg_erro);
		if(strlen($msg_erro) > 0){
			$res = pg_rollBack();
			$msg_erro.= ($hd_chamado_item) ? ' Não foi possível gravar sua interação.' : ' Não foi possível Inserir o Chamado.';
		}else{
			$res = @pg_commit();
			if($status == 'Resolvido' OR $exigir_resposta == 't'){
				$sql="SELECT nome_completo,email,tbl_admin.admin, tbl_hd_chamado.fabrica
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin
							WHERE hd_chamado = $hd_chamado";
				$res = pg_query($con,$sql);
				$email	= pg_fetch_result($res, 0, 'email');
				$nome	= pg_fetch_result($res, 0, 'nome_completo');
				$adm	= pg_fetch_result($res, 0, 'admin');
				$fabrica= pg_fetch_result($res, 0, 'fabrica');

				$chave1	= md5($hd_chamado);
				$chave2 = md5($adm);
				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $email;
				$assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";
				$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
						NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>Seu chamado foi&nbsp;<FONT
						color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, Caso esteja com algum problema,
						<STRONG>insira um comentário para que o suporte verifique o que ocorreu. </STRONG></P>
						<P align=justify>Lembre-se: Não precisa fazer comentário de agradecimento, pois o sistema vai entender que o chamado foi mal resolvido!</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
						</P>";

				if($exigir_resposta=='t' and $status<>'Resolvido' ){

					$assunto       = "Seu chamado n° $hd_chamado está aguardando sua resposta";

					$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
							NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
							<STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>

							<P align=justify>
							Precisamos de sua posição para continuarmos atendendo o chamado.
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
							</P>";
				}

				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
					$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
				}

				#HD 16226
				if($exigir_resposta=='t' and $status<>'Resolvido' AND $xinterno=='f' and $fabrica==3){
					$sql = "SELECT nome_completo,email,tbl_admin.admin
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
							WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
							AND tbl_admin.help_desk_supervisor IS TRUE
							AND tbl_admin.admin                 <> 19";
					$res = pg_query($con,$sql);
					if ( pg_num_rows($res) > 0) {
						$surpevisor_email  = pg_fetch_result($res, 0, 'email');
						$surpevisor_nome   = pg_fetch_result($res, 0, 'nome_completo');
						$surpevisor_adm    = pg_fetch_result($res, 0, 'admin');

						$chave1 = md5($hd_chamado);
						$chave2 = md5($surpevisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $surpevisor_email;
						$assunto       = "O chamado n° $hd_chamado está aguardando resposta";
						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
								<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
								<STRONG>$titulo</STRONG>&nbsp; </P>
								<P align=left>$nome,</P>
								<P align=justify>Estamos aguardando a posição do(a) $nome para continuarmos atendendo o chamado.</P>
								<p>O seguinte comentário foi inserido no chamado: <br><i>$comentario</i></p>
								<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
								</P>";

						//<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$surpevisor_adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver o chamado</b></u></a>.</P>
						$body_top = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}
					}
				}
			}
			if($status =='Resolvido'){
				$sql = "
				SELECT
				hd_chamado_melhoria

				FROM
				tbl_hd_chamado_melhoria

				WHERE
				hd_chamado=$hd_chamado
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					//A variável abaixo armazena qual o admin responsável por gerenciar as Melhorias
					//em Programas, normalmente o Tester, ele receberá e-mails de notificações
					$admin_responsavel_melhorias = 2310;

					$sql = "
					SELECT
					email

					FROM
					tbl_admin

					WHERE
					admin=$admin_responsavel_melhorias
					";
					$res = pg_query($con, $sql);
					$email = pg_fetch_result($res, 0, 'email');

					$mensagem = "O chamado $hd_chamado possui melhorias associadas a ele e foi Resolvido nesta data.<br>
					Por favor, acessar o sistema de melhorias em programas para validar.<br>
					<br>
					Suporte Telecontrol";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";
					$headers .= "To: $email" . "\r\n";
					$headers .= "From: Telecontrol Melhorias <suporte@telecontrol.com.br>";// . "\r\n";

					$titulo = "Melhorias: Chamado $hd_chamado Resolvido";

					mail($to, $titulo, $mensagem, $headers);
				}

				?>
				<script type="text/javascript">
					if(confirm('Deseja registrar alterações no Change Log?') == true){
						window.location="change_log_insere.php?hd_chamado=<?echo $hd_chamado;?>";
					}else{
					window.location="adm_chamado_detalhe.php?hd_chamado=<?echo $hd_chamado;?>";
					}
				</script>
				<?
			}else{
				header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
			}
		}
	}
}

if(strlen($hd_chamado) > 0){
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_query($con,$sql);

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.duracao                               ,
					tbl_hd_chamado.admin_desenvolvedor					 ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.fabrica                               ,
					tbl_hd_chamado.prioridade                            ,
					tbl_hd_chamado.prazo_horas                           ,
					tbl_hd_chamado.horas_suporte,
					tbl_hd_chamado.horas_analise,
					tbl_hd_chamado.horas_teste,
					tbl_hd_chamado.horas_efetivacao,
					tbl_hd_chamado.cobrar,
					tbl_hd_chamado.tipo_chamado,
					tbl_hd_chamado.analise,
					tbl_hd_chamado.plano_teste,
					tbl_hd_chamado.procedimento_teste,
					tbl_hd_chamado.procedimento_efetivacao,
					tbl_hd_chamado.validacao,
					tbl_hd_chamado.comentario_desenvolvedor,
					tbl_hd_chamado.admin_desenvolvedor,
					tbl_hd_chamado.hora_desenvolvimento                  ,
					to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI') AS previsao_termino,
					to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
					tbl_fabrica.nome   AS fabrica_nome                   ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo                              ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					tbl_admin.grupo_admin								 ,
					atend.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
			WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	if ( pg_num_rows($res) > 0) {
		$admin                = pg_fetch_result($res, 0, 'admin');
		$data                 = pg_fetch_result($res, 0, 'data');
		$titulo               = pg_fetch_result($res, 0, 'titulo');
		$categoria            = pg_fetch_result($res, 0, 'categoria');
		$status               = pg_fetch_result($res, 0, 'status');
		$admin_desenvolvedor  = pg_fetch_result($res, 0, 'admin_desenvolvedor');
		$atendente            = pg_fetch_result($res, 0, 'atendente');
		$atendente_nome       = pg_fetch_result($res, 0, 'atendente_nome');
		$fabrica_responsavel  = pg_fetch_result($res, 0, 'fabrica_responsavel');
		$fabrica              = pg_fetch_result($res, 0, 'fabrica');
		$nome                 = pg_fetch_result($res, 0, 'nome_completo');
		$email                = pg_fetch_result($res, 0, 'email');
		$prioridade           = pg_fetch_result($res, 0, 'prioridade');
		$fone                 = pg_fetch_result($res, 0, 'fone');
		$plano_teste		  = pg_fetch_result($res, 0, 'plano_teste');
		$analiseTexto		  = pg_fetch_result($res, 0, 'analise');
		$procedimento_teste   = pg_fetch_result($res, 0, 'procedimento_teste');
		$procedimento_efetivacao= pg_fetch_result($res, 0, 'procedimento_efetivacao');
		$validacao 				= pg_fetch_result($res, 0, 'validacao');
		$comentario_desenvolvedor = pg_fetch_result($res, 0, 'comentario_desenvolvedor');
		$admin_desenvolvedor  	= pg_fetch_result($res, 0, 'admin_desenvolvedor');
		$nome_completo        	= pg_fetch_result($res, 0, 'nome_completo');
		$fabrica_nome         	= pg_fetch_result($res, 0, 'fabrica_nome');
		$login                	= pg_fetch_result($res, 0, 'login');
		$prazo_horas          	= pg_fetch_result($res, 0, 'prazo_horas');
		$horas_suporte          = pg_fetch_result($res, 0, 'horas_suporte');
		$horas_analise          = pg_fetch_result($res, 0, 'horas_analise');
		$horas_teste         	= pg_fetch_result($res, 0, 'horas_teste');
		$horas_efetivacao       = pg_fetch_result($res, 0, 'horas_efetivacao');
		$previsao_termino     	= pg_fetch_result($res, 0, 'previsao_termino');
		$previsao_termino_interna = pg_fetch_result($res, 0, 'previsao_termino_interna');
		$hora_desenvolvimento     = pg_fetch_result($res, 0, 'hora_desenvolvimento');
		$cobrar                   = pg_fetch_result($res, 0, 'cobrar');
		$tipo_chamado             = pg_fetch_result($res, 0, 'tipo_chamado');
		$grupo_admin			  = pg_fetch_result($res, 0, 'grupo_admin');

		//HD 218848: Criação do questionário na abertura do Help Desk
		$sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$mostra_questionario = true;
			$necessidade	= pg_fetch_result($res, 0, 'necessidade');
			$funciona_hoje	= pg_fetch_result($res, 0, 'funciona_hoje');
			$objetivo		= pg_fetch_result($res, 0, 'objetivo');
			$local_menu		= pg_fetch_result($res, 0, 'local_menu');
			$http			= pg_fetch_result($res, 0, 'http');
			$tempo_espera	= pg_fetch_result($res, 0, 'tempo_espera');
			$impacto		= pg_fetch_result($res, 0, 'impacto');
		}
	}else{
		$msg_erro="Chamado não encontrado";
	}
}

#HD 351094
$atualizacaoDados = true;
if($status =='Resolvido' || $status =='Cancelado'){
	$atualizacaoDados = false;
}

$TITULO = "ADM - Responder Chamado";
if ($hd_chamado) $TITULO.= ' nº '.$hd_chamado;
include "menu.php";
?>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<? if($login_admin ==822 or $login_admin==398 or $login_admin==1375 ) {
	echo "<script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script>";
}
?>
<script>
<? if($login_admin ==822 or $login_admin==398 or $login_admin==1375 ) { ?>
window.onload = function(){
	var oFCKeditor = new FCKeditor( 'comentario' ) ;
	oFCKeditor.BasePath = "../admin/js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Chamado' ;
	oFCKeditor.ReplaceTextarea() ;
}
<?}?>
function recuperardados(hd_chamado) {
	var programa = document.frm_chamada.programa.value;
	if(programa.length > 4 ){
		var busca = new BUSCA();
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http3 = new Array();


function atualizaHr(hd,hr){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&hr="+hr+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				/*	if (campo==false) return;
					if (campo.style.display=="block"){
						campo.style.display = "none";
					}else{
						campo.style.display = "block";
					}*/
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrazo(hd,prazo){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&prazo="+prazo+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrevisaoTermino(hd,data){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result2');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_previsao_termino=true&hd="+hd+"&data_previsao="+data+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				/*	if (campo==false) return;
					if (campo.style.display=="block"){
						campo.style.display = "none";
					}else{
						campo.style.display = "block";
					}*/
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#previsao_termino").maskedinput("99/99/9999 99:99");
	});
</script>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
<?php
#HD 351094
?>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>

<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	<?php
	#HD 351094
	?>
	$(".horas").maskMoney({symbol:"", decimal:".", thousands:"",precision:1});

	$(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
	$(".relatorio tr:even").addClass("alt");
});
</script>

<script language="JavaScript">
function abrir(URL) {
	var width = 300;
	var height = 290;
	var left = 99;
	var top = 99;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}
</script>

<style>
.resolvido{
	background: #259826;
	color: #FCFCFC;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}
.interno{
	background: #FFE0B0;
	color: #000000;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}

	table.tab_cabeca{
		border:1px solid #3e83c9;
		font-family: Verdana;
		font-size: 11px;

	}
	.titulo_cab{
		background: #C9D7E7;
		padding: 5px;
		color: #000000;
		font: bold;
	}
	.sub_label{
		background: #E7EAF1;
		padding: 5px;
		color: #000000;
		border-bottom:1px solid #ccc;
	}
	table.relatorio {
		font-family: Verdana;
		font-size: 11px;
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
		font-family: Verdana;
		font-size: 11px;
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 2px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	table.relatorio td {
		font-family: Verdana;
		font-size: 11px;
		padding: 1px 5px 5px 5px;
		border-bottom: 1px solid #95bce2;
		line-height: 15px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}
<? if($login_admin != 822) { ?>
	table.relatorio tr.over td {
		background: #bcd4ec;
	}
<? } ?>

	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}

	</style>



<table width = '750' class = 'tab_cabeca' align = 'center' border='0' cellpadding='2' cellspacing='2' >

<tr>
	<td class='titulo_cab' width='10'><strong>Título </strong></td>
	<td class='sub_label'><?= $titulo ?> </td>

	<td class='titulo_cab' width="60"><strong>Abertura </strong></td>
	<td  class='sub_label'align='center'><?= $data ?> </td>

</tr>
<tr>
	<td class='titulo_cab' ><strong>Solicitante </strong></td>
	<td  class='sub_label' ><?= $login ?> </td>
	<td class='titulo_cab' width="60" ><strong>Chamado </strong></td>
	<td  class='sub_label'align='center'><strong><font  color='#FF0033' size='4'><?=$hd_chamado?></font></strong></td>
</tr>
<tr>
	<td class='titulo_cab' ><strong>Nome </strong></td>
	<td class='sub_label'><?= $nome ?></td>
	<td class='titulo_cab' width="60"><strong>Fábrica </strong></td>
	<td  class='sub_label' align='center'><?= $fabrica_nome ?> </td>
</tr>

<tr>
	<td class='titulo_cab'><strong>e-mail </strong></td>
	<td class='sub_label'><?= $email ?></td>
	<td class='titulo_cab'><strong>Fone </strong></td>
	<td  class='sub_label' align='center'><?= $fone ?></td>
</tr>

<tr>
	<td class='titulo_cab' ><strong>Atendente </strong></td>
	<td class='sub_label'><?= $atendente_nome ?></td>
	<td class='titulo_cab'><strong>Status </strong></td>
	<td  class='sub_label' align='center'><?= $status ?></td>
</tr>
<!-- HD 218848: Criação do questionário na abertura do Help Desk -->
<?
if ($mostra_questionario) {
	$desabilita_questionario = "readonly";
	$desabilita_questionario_combo = "disabled";
?>
<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;O que você precisa que seja feito?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $necessidade; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Como funciona hoje?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $funciona_hoje; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Qual o objetivo desta solicitação? Que problema visa resolver?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $objetivo; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Esta rotina terá impacto financeiro para a empresa? Por quê?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		<? echo $impacto; ?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Em que local do sistema você precisa de alteração?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
	<?

	switch($local_menu) {
		case "admin_gerencia":
			echo "Administração: Gerência";
		break;

		case "admin_callcenter":
			echo "Administração: CallCenter";
		break;

		case "admin_cadastro":
			echo "Administração: Cadastro";
		break;

		case "admin_infotecnica":
			echo "Administração: Info Técnica";
		break;

		case "admin_financeiro":
			echo "Administração: Financeiro";
		break;

		case "admin_auditoria":
			echo "Administração: Auditoria";
		break;

		case "posto_os":
			echo "Área do Posto: Ordem de Serviço";
		break;

		case "posto_infotecnica":
			echo "Área do Posto: Info Técnica";
		break;

		case "posto_pedidos":
			echo "Área do Posto: Pedidos";
		break;

		case "posto_cadastro":
			echo "Área do Posto: Cadastro";
		break;

		case "posto_tabelapreco":
			echo "Área do Posto: Tabela Preço";
		break;
	}

	?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Quanto tempo é possível esperar por esta mudança?</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
	<?
	switch ($tempo_espera) {
		case "0":
		echo "Imediato";
		break;

		case "1":
		echo "1 Dia";
		break;

		case "2":
		echo "2 Dias";
		break;

		case "3":
		echo "3 Dias";
		break;

		case "4":
		echo "4 Dias";
		break;

		case "5":
		echo "5 Dias";
		break;

		case "6":
		echo "6 Dias";
		break;

		case "7":
		echo "1 Semana";
		break;

		case "14":
		echo "2 Semanas";
		break;

		case "21":
		echo "3 Semanas";
		break;

		case "30":
		echo "1 Mês";
		break;

		case "60":
		echo "2 Meses";
		break;

		case "90":
		echo "3 Meses";
		break;

		case "180":
		echo "6 Meses";
		break;

		case "360":
		echo "1 Ano";
		break;

		default:
			echo "$tempo_espera Dias";
	}
	?>
	</td>
</tr>

<tr>
	<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<strong>&nbsp;Endereço HTTP da tela onde está sendo solicitada a alteração:</strong>
	</td>
</tr>
<tr>
	<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
		http://<? echo $http; ?>
	</td>
</tr>
<?
}
?>
</table>


<?php
//Modificado por Thiago Contardi - HD: 304470
?>
<style>
#menu_requisitos{
	list-style:none;
	width:750px;
	border: 1px solid #3e83c9;
	margin:25px 0;
	padding:1px;
	text-align:left;
}
#menu_requisitos > li,#menu_requisitos li{
	margin:3px;
}
#menu_requisitos > li:hover{
	color:#o9f;
}
p .link{
	font-size:16px;
	cursor:pointer;
}
.menuAnalise{
	width:100%;
	padding:2px;
	margin:1px;
	cursor:pointer;
	display:block;
}
.rodada{
	border:1px solid #ccc;
	margin:0;
	padding:0;
}
.table_requisitos{
	font-family: arial; 
	font-size: 12px;
	width:720px;
	margin-top:30px;
}
.salvarAnalise input{
	float:right;
	margin:10px;
}
.salvarAnalise{
	height:35px;
}
.textareaAnalise{
	width:690px;
	height:150px;
}
.textareaEfetivacao,.textareaValidacao{
	width:690px;
	height:300px;
}
.table_analise{
	font-family: arial; 
	font-size: 12px;
	width:710px;
}
.table_analise tr,.table_analise td{
	margin:1px;
}
.xAnalise{
	padding:5px;
	text-decoration:none;
}
.xAnalise:hover{
	background-color:#999;
	text-decoration:none;
	color:#cdcdff;
}
.finalizarCorrecoes{
	font-size:10pt;
}
.greenHD{
	background-color:#85C940;
}
.yellowHD{
	background-color:#C9C940;
}
.orangeHD{
	background-color:#C98540;
}
.redHD{
	background-color:#C94040;
}
.grayHD{
	background-color:#ccc;
}
.pinkHD{
	background-color:#88B3DD;
}
.purpleHD{
	background-color:#E1CAE7;
}
</style>

<script>
$(document).ready(function() {
	$('#menu_requisitos li, #menu_requisitos table tr th').addClass('titulo_cab');
	$('#menu_requisitos li div, #menu_requisitos table tr td').addClass('sub_label');
	
	$('#menu_requisitos li > strong').click(function(){
		$(this).next().toggle();
	});
});

function muda_admin(admin,valor){
	if(admin!=valor.value){
		if(confirm("Você está alterando o responsável pelo chamado, deseja continuar?")){
			return true;
		}else{
			valor.selectedIndex = $('#indexDesenv').val();
		}
	}
}

var analise = {

	addRequisito:function(){
		var numero = $('#numeroRequisito').val();
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adReq=1&numero="+numero,
			cache: false,
			success: function(data){
				$(".naoRequisitos").remove();
				numero++;
				$('#numeroRequisito').val(numero);
				$("#frm_req").append(data);
			}
		});	
	},
	delRequisito:function(numero){
		if(confirm('Deseja mesmo remover este requisito?')){
			$('#requisito_'+numero).remove();
		}
	},
	deleteRequisito:function(id,numero){
		
		if(confirm('Deseja mesmo remover este requisito?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delReq=1&idReq="+id,
				cache: false,
				success: function(data){
					$('#requisito_'+numero).remove();
				}
			});	
			
		}
	},
	addAnalise:function(){
	
		var numero = $('#numeroAnalise').val();
		
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adAnalise=1&numero="+numero,
			cache: false,
			success: function(data){
				$(".naoAnalise").remove();
				numero++;
				$('#numeroAnalise').val(numero);
				$("#frm_ana").append(data);
			}
		});	
	},
	delAnalise:function(numero){
		if(confirm('Deseja mesmo remover esta analise?')){
			$('#analise_'+numero).remove();
		}
	},
	deleteAnalise:function(id,numero){
		
		if(confirm('Deseja mesmo remover esta analise?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delAnalise=1&idAnalise="+id,
				cache: false,
				success: function(data){
					$('#analise_'+numero).remove();
				}
			});	
			
		}
	},
	addCorrecao:function(rodada){
	
		var numero = $('#numeroCorrecao_'+rodada).val();
		
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adCorrecao=1&rodadaCorrecao="+rodada+"&numero="+numero,
			cache: false,
			success: function(data){
				$(".naoCorrecao").remove();
				numero++;
				$('#numeroCorrecao_'+rodada).val(numero);
				$("#tbl_correcao_"+rodada).append(data);
			}
		});	
	},
	addRodada:function(rodada,chamado){
	
		var numero = $('#numeroCorrecao_'+rodada).val();
		
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adRodada=1&rodadaCorrecao="+rodada+"&numero="+numero+'&hd_chamado='+chamado,
			cache: false,
			success: function(data){
				$(".naoCorrecao").remove();
				numero++;
				$('#numeroCorrecao_'+rodada).val(numero);
				$("#nova_rodada").html(data);
			}
		});	
	},
	delCorrecao:function(rodada,numero){
		if(confirm('Deseja mesmo remover esta correção?')){
			$('#correcao_'+rodada+'_'+numero).remove();
		}
	},
	deleteCorrecao:function(id,rodada,numero){
		if(confirm('Deseja mesmo remover esta correção?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delCorrecao=1&idCorrecao="+id,
				cache: false,
				success: function(data){
					$('#correcao_'+rodada+'_'+numero).remove();
				}
			});	
			
		}
	},
	showData:function(id){
		$("#"+id).toggle();
	},
	inicioTrabalho:function(chamado){
		window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?hd_chamado='+chamado+'&inicio_trabalho=1';
	},
	fimTrabalho:function(chamado){
		window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?hd_chamado='+chamado+'&termino_trabalho=1';
	},
	enviaFormulario:function(formulario){

		var dados = '';
		var total_horas = 0;
		var dadosInput = $('#'+formulario.id+' input').serialize();
		var dadosSelect = $('#'+formulario.id+' select').serialize();
		var dadosTextarea = $('#'+formulario.id+' textarea').serialize();

		if(dadosInput.length > 0)
			dados = dadosInput;
		if(dadosSelect.length > 0){
			if(dados.length > 0)
				dados = dados+'&'+dadosSelect;
			else
				dados = dadosSelect;
		}
		if(dadosTextarea.length > 0){
			if(dados.length > 0)
				dados = dados+'&'+dadosTextarea;
			else
				dados = dadosTextarea;
		}

		$.ajax({  
			type: "POST",  
			url: formulario.action,  
			data: dados, 
			beforeSend: function(){
				$('#'+formulario.id).append('<div id="msg_envio" align="right" style="padding:2px;border:1px solid #ccc;">enviando...</div>');
			},
			success: function(resposta) {  
				alert(resposta);
				$('.horas').each(function(){
					if($(this).val() == ''){
						$(this).val('0');
					}
					total_horas += parseFloat($(this).val());
				});
				$('#total_horas').html(' '+total_horas+' h ');
				$('#msg_envio').remove();
			}  
		});
		
		return false;
	}

};
</script>

	<div align="center">
		<ul id="menu_requisitos">
			
			<?php 
			#Aba de Requisitos
			?>
			<li class="greenHD">
				<strong class="menuAnalise">Requisitos do Sistema</strong>

				<div style="display:none;">

					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name="frm_requisitos" id="frm_requisitos" action="<?php echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method="post">
					<?php endif;?>

						<table border="0" cellpadding='2' class="table_requisitos" id="frm_req">
							<tr>
								<th valign='top' align="left">&nbsp;</th>
								<th valign='top' align="left" width="65%">Requisito</th>
								<th valign='top' width="13%">Análise</th>
								<th valign='top' width="13%">Teste</th>
								<th valign='top' width="3%">&nbsp;</th>
							</tr>
							<?php
							$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao
									  FROM tbl_hd_chamado_requisito
									 WHERE hd_chamado = '.$hd_chamado.'
									   AND excluido = FALSE
									 ORDER BY interacao;';
							$res = pg_query($con,$sql);
							$totalRequisitos = pg_num_rows($res);
							?>

							<?php if($totalRequisitos > 0):?>

								<?php for($i=0;$i<$totalRequisitos;$i++): ?>

									<?php
									$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
									$idRequisito = pg_fetch_result($res,$i,'hd_chamado_requisito');
									$textoRequisitos = pg_fetch_result($res,$i,'requisito');
									$analiseRequisitos = pg_fetch_result($res,$i,'analise');
									$testeRequisitos = pg_fetch_result($res,$i,'teste');
									?>

									<tr id="requisito_<?php echo $i;?>">
										<td valign='top' align="left"><?php echo $interacaoRequisito;?></td>
										<td>
											<input type="hidden" name="idRequisitos[]" value="<?php echo $idRequisito;?>" />
											<input type="hidden" name="requisitos[]" value="<?php echo $textoRequisitos;?>" />
											<?php echo nl2br($textoRequisitos);?>
										</td>
										<td align="center">

											<select name="analiseRequisitos[]">
												<option value="0" <?php echo ($analiseRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($analiseRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td align="center">
											<select name="testeRequisitos[]">
												<option value="0" <?php echo (!$testeRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($testeRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td valign="middle" align="center">
											<?php 
											#HD 351094
											if($atualizacaoDados):?>
											<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteRequisito(<?php echo $idRequisito;?>,<?php echo $i;?>)"> X </a>
											<?php endif;?>
										</td>
									</tr>
								<?php endfor;?>

							<?php else: ?>

								<?php $i=0;?>
								<tr class="naoRequisitos">
									<td colspan="4" align="center" class="sub_label">
										Nenhum Requisito Cadastrado
									</td>
								</tr>

							<?php endif;?>

						</table>

					<?php 
					
					#HD 351094
					if($atualizacaoDados):
						$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_requisito',$hd_chamado);
						?>

						<input type="hidden" id="numeroRequisito" value="<?php echo ($maiorInteracao+1);?>" />
						<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />

						[ <a href="javascript:void(0)" onclick="analise.addRequisito()">Adicionar Requisito</a> ]

						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="req1" />
							<input type="submit" name="salvar" value="Salvar">
						</div>

					</form>
					<?php endif;?>

					<?php
					$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_requisito
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = TRUE
							 ORDER BY interacao;';
					$res = pg_query($con,$sql);
					$totalRequisitos = pg_num_rows($res);
					?>

					<?php if($totalRequisitos > 0):?>

						[ <a href="javascript:void(0)" onclick="analise.showData('requisitosExcluidos')">Requisitos Excluídos</a> ]

						<div id="requisitosExcluidos" style="display:none;">
							
							<table border='0' cellpadding='2' class="table_analise">
								<tr>
									<th valign='top' align="left">&nbsp;</th>
									<th valign='top' align="left" width="65%">Requisito</th>
									<th valign='top' width="15%">Análise</th>
									<th valign='top' width="15%">Teste</th>
								</tr>

								<?php for($i=0;$i<$totalRequisitos;$i++): ?>
									<?php
									$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
									$textoRequisitos = pg_fetch_result($res,$i,'requisito');
									$analiseRequisitos = pg_fetch_result($res,$i,'analise');
									$testeRequisitos = pg_fetch_result($res,$i,'teste');
									?>

									<tr>
										<td>
											<?php echo $interacaoRequisito;?>
										</td>
										<td>
											<?php echo nl2br($textoRequisitos);?>
										</td>
										<td align="center">
											<?php echo ($analiseRequisitos=='f') ? 'Não' : 'Sim';?>
										</td>
										<td align="center">
											<?php echo (!$testeRequisitos=='f') ? 'Não' : 'Sim';?>
										</td>
									</tr>
								<?php endfor;?>
							</table>
						</div>
					<?php endif;?>
					
				</div>
			</li>

			<?php 
			#Aba de Análise
			?>
			<li class="yellowHD">
				<strong class="menuAnalise">Análise</strong>
				<div style="display:none;">
					
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name="frm_analise" id="frm_analise" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method="post">
					<?php endif;?>
						
						<div>
							<p class="titulo_cab"><strong>Texto Análise</strong></p>
							<textarea name="analiseTexto" id="analiseTexto" class="textareaAnalise"><?php echo $analiseTexto;?></textarea>
						</div>
						
						<table border='0' cellpadding='2' class="table_requisitos" id="frm_ana">
							<tr>
								<th valign='top' align="left">&nbsp;</th>
								<th valign='top' align="left" width="65%">Análise</th>
								<th valign='top' width="13%">Desenvolvimento</th>
								<th valign='top' width="13%">Teste</th>
								<th valign='top' width="3%">&nbsp;</th>
							</tr>
							<?php
							$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
									  FROM tbl_hd_chamado_analise
									 WHERE hd_chamado = '.$hd_chamado.'
									   AND excluido = FALSE
									 ORDER BY interacao;';
							$res = pg_query($con,$sql);
							$totalAnalises = pg_num_rows($res);
							?>

							<?php if($totalAnalises > 0):?>

								<?php for($i=0;$i<$totalAnalises;$i++): ?>

									<?php
									$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
									$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
									$textoAnalise = pg_fetch_result($res,$i,'analise');
									$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
									$testeAnalise = pg_fetch_result($res,$i,'teste');
									?>

									<tr id="analise_<?php echo $i;?>">
										<td><?php echo $interacaoAnalise;?></td>
										<td>
											<input type="hidden" name="idAnalise[]" value="<?php echo $idAnalise;?>" />
											<input type="hidden" name="analises[]" value="<?php echo $textoAnalise;?>" />
											<?php echo nl2br($textoAnalise);?>
										</td>
										<td align="center">
											<select name="desenvAnalise[]">
												<option value="0" <?php echo ($desenvAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($desenvAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td align="center">
											<select name="testeAnalise[]">
												<option value="0" <?php echo ($testevAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($testeAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td valign="middle" align="center">
											<?php 
											#HD 351094
											if($atualizacaoDados):?>
											<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteAnalise(<?php echo $idAnalise;?>,<?php echo $i;?>)"> X </a>
											<?php endif;?>
										</td>
									</tr>

								<?php endfor;?>

							<?php else: ?>
								<?php $i=0;?>
								<tr class="naoAnalise">
									<td colspan="4" align="center" class="sub_label">
										Nenhuma Análise Cadastrada
									</td>
								</tr>
							<?php endif;?>

						</table>
					
						<?php 
						#HD 351094
						if($atualizacaoDados):
							$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_analise',$hd_chamado);
							?>
							<input type="hidden" id="numeroAnalise" value="<?php echo ($maiorInteracao+1);?>" />
							[ <a href="javascript:void(0)" onclick="analise.addAnalise()">Adicionar Análise</a> ]
						<?php endif;?>
						
						<div>
							<p class="titulo_cab"><strong>Plano de Teste</strong></p>
							<textarea name="plano_teste" id="plano_teste" class="textareaAnalise"><?php echo $plano_teste;?></textarea>
						</div>
					<?php 
					#HD 351094
					if($atualizacaoDados):?>	
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="analise1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
					

					<?php
					#Análises excluídas
					$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_analise
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = TRUE
							 ORDER BY interacao;';
					$res = pg_query($con,$sql);
					$totalAnalises = pg_num_rows($res);
					?>

					<?php if($totalAnalises > 0):?>

						[ <a href="javascript:void(0)" onclick="analise.showData('analiseExcluidas')">Análises Excluídas</a> ]

						<div id="analiseExcluidas" style="display:none">

							<table border='0' cellpadding='2' class="table_analise">

								<tr>
									<th valign='top' align="left">&nbsp;</th>
									<th valign='top' align="left" width="65%">Análise</th>
									<th valign='top' width="13%">Desenvolvimento</th>
									<th valign='top' width="13%">Teste</th>
								</tr>

								<?php for($i=0;$i<$totalAnalises;$i++): ?>

									<?php
									$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
									$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
									$textoAnalise = pg_fetch_result($res,$i,'analise');
									$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
									$testeAnalise = pg_fetch_result($res,$i,'teste');
									?>

									<tr>
										<td><?php echo $interacaoAnalise;?></td>
										<td>
											<?php echo nl2br($textoAnalise);?>
										</td>
										<td align="center">
											<?php echo ($desenvAnalise=='f') ? 'Não' : 'Sim'; ?>
										</td>
										<td align="center">
											<?php echo ($testevAnalise=='f') ? 'Não' : 'Sim'; ?>
										</td>
									</tr>

								<?php endfor;?>

							</table>
						</div>
					<?php endif;?>
					
				</div>
			</li>

			<?php 
			#Aba de Desenvolvimento
			?>
			<li class="orangeHD">
				<strong class="menuAnalise">Desenvolvimento</strong>
				<div style="display:none;">
					
					<p class="titulo_cab link" onclick="analise.showData('checkListMinimo')">
						<a href="javascript:void(0)"><strong>Check List Mínimo</strong></a>
					</p>

					<div style="display:none;" id="checkListMinimo">
					
						<?php 
						#HD 351094
						if($atualizacaoDados):?>
						<form name='frm_check_list' id="frm_check_list" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
						<?php endif;?>
							<p class="titulo_cab"><strong>CheckList</strong></p>
							<table border='0' cellpadding='2' class="table_analise">
								<tr>
									<th align="left">Ítem do Check List</th>
									<th width="50px">Atendido</th>
								</tr>
								
								<?php
								$checklist_ok = array();

								$sql = 'SELECT tc.checklist,thcc.atendido
										  FROM tbl_hd_chamado_checklist AS thcc
										  JOIN tbl_checklist AS tc ON (thcc.checklist = tc.checklist)
										 WHERE thcc.hd_chamado = '.$hd_chamado.'
										   AND tc.ativo IS TRUE;';

								$res = pg_query($con,$sql);
								$totalCheckListOk = pg_num_rows($res);

								for($i=0;$i<$totalCheckListOk;$i++){
									$checklist_ok[pg_fetch_result($res,$i,'checklist')] = pg_fetch_result($res,$i,'atendido');
								}
								?>

								<?php
								$sql = 'SELECT checklist,item_verificar
										  FROM tbl_checklist
										 WHERE ativo IS TRUE
									  ORDER BY item_verificar;';

								$res = pg_query($con,$sql);
								$totalCheckList = pg_num_rows($res);
								?>

								<?php if($totalCheckList > 0):?>

									<?php for($i=0;$i<$totalCheckList;$i++): ?>

										<?php
										$checklist_id = pg_fetch_result($res,$i,'checklist');
										$checklist_nome = pg_fetch_result($res,$i,'item_verificar');
										?>

										<tr>
											<th align="left">
												<input type="hidden" name="checklist[]" value="<?php echo $checklist_id;?>" />
												<?php echo $checklist_nome;?>
											</th>
											<th align="center">
												<select name="atendido[]">
													<option value="NULL" <?php echo (!$checklist_ok[$checklist_id]) ? 'selected="selected"' : null;?>>Não aplicável</option>
													<option value="TRUE" <?php echo ($checklist_ok[$checklist_id] == 't') ? 'selected="selected"' : null;?>>Sim</option>
													<option value="FALSE" <?php echo ($checklist_ok[$checklist_id] == 'f') ? 'selected="selected"' : null;?>>Não</option>
												</select>
											</th>
										</tr>
									<?php endfor;?>

								<?php endif;?>
							</table>
						<?php 
						#HD 351094
						if($atualizacaoDados):?>
							<div class="salvarAnalise">
								<input type="hidden" name="_POST" value="check1" />
								<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
								<input type="submit" name="salvar" value="Salvar" />
							</div>
						</form>
						<?php endif;?>

					</div>

					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_analise_dados' id="frm_analise_dados" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post'  onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
					
						<div>
							<p class="titulo_cab"><strong>Procedimento de Teste</strong></p>
							<textarea name="procedimento_teste" id="procedimento_teste" class="textareaEfetivacao"><?php echo $procedimento_teste;?></textarea>
						</div>

						<div>
							<p class="titulo_cab"><strong>Comentários do Desenvolvedor</strong></p>
							<textarea name="comentario_desenvolvedor" id="comentario_desenvolvedor" class="textareaEfetivacao"><?php echo $comentario_desenvolvedor;?></textarea>
						</div>

					<?php 
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="teste1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>

				</div>
			</li>

			<?php 
			#Aba de Testes
			?>
			<li class="redHD">
				<strong class="menuAnalise">Testes</strong>
				<div style="display:none;">

					<?php
					#Requisitos dos Testes
					$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_requisito
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = FALSE
							   AND teste = TRUE;';
					$res = pg_query($con,$sql);
					$totalRequisitos = pg_num_rows($res);
					?>

					<?php if($totalRequisitos > 0):?>	

						<p class="titulo_cab link" onclick="analise.showData('requisitosTestes')">
							<a href="javascript:void(0)"><strong>Requisitos em Teste</strong></a>
						</p>

						<div id="requisitosTestes" style="display:none">
							<?php 
							#HD 351094
							if($atualizacaoDados):?>
							<form name='frm_analise_teste' id="frm_analise_teste" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
							<?php endif;?>
								<table border='0' cellpadding='2' class="table_analise">
									<tr>
										<th valign='top' align="left" width="3%">&nbsp;</th>
										<th valign='top' align="left">Requisito</th>
										<th valign='top' width="15%">Análise</th>
										<th valign='top' width="15%">Teste</th>
									</tr>

									<?php for($i=0;$i<$totalRequisitos;$i++): ?>

										<?php
										$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
										$idRequisito = pg_fetch_result($res,$i,'hd_chamado_requisito');
										$textoRequisitos = pg_fetch_result($res,$i,'requisito');
										$analiseRequisitos = pg_fetch_result($res,$i,'analise');
										$testeRequisitos = pg_fetch_result($res,$i,'teste');
										?>

										<tr>
											<td><?php echo $interacaoRequisito;?></td>
											<td>
												<input type="hidden" name="idRequisitos[]" value="<?php echo $idRequisito;?>" />
												<input type="hidden" name="requisitos[]" value="<?php echo $textoRequisitos;?>" />
												<?php echo nl2br($textoRequisitos);?>
											</td>
											<td align="center">
												<select name="analiseRequisitos[]">
												<option value="0" <?php echo ($analiseRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($analiseRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
											</td>
											<td align="center">
												<select name="testeRequisitos[]">
													<option value="0" <?php echo (!$testeRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($testeRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
										</tr>
									</tr>

									<?php endfor;?>

								</table>
							<?php 
							#HD 351094
							if($atualizacaoDados):?>
								<div class="salvarAnalise">
									<input type="hidden" name="_POST" value="requisitoTeste1" />
									<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
									<input type="submit" name="salvar" value="Salvar" />
								</div>
							</form>
							<?php endif;?>
						</div>
					<?php endif;?>

					<?php
					#Analises dos Testes
					?>
					<?php
					$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_analise
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = FALSE
							   AND teste = TRUE';
					$res = pg_query($con,$sql);
					$totalAnalises = pg_num_rows($res);
					?>

					<?php if($totalAnalises > 0):?>	

						<p class="titulo_cab link" onclick="analise.showData('analisesTestes')">
							<a href="javascript:void(0)"><strong>Análises em Teste</strong></a>
						</p>

						<div id="analisesTestes" style="display:none">
							<?php 
							#HD 351094
							if($atualizacaoDados):?>
							<form name='frm_requisitos_teste' id="frm_requisitos_teste" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
							<?php endif;?>
								<table border='0' cellpadding='2' class="table_analise">
									<tr>
										<th width="3%">&nbsp;</th>
										<th valign='top' align="left">Análise</th>
										<th valign='top' width="15%">Desenvolvimento</th>
										<th valign='top' width="15%">Teste</th>
									</tr>

									<?php for($i=0;$i<$totalAnalises;$i++): ?>

										<?php
										$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
										$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
										$textoAnalise = pg_fetch_result($res,$i,'analise');
										$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
										$testeAnalise = pg_fetch_result($res,$i,'teste');
										?>

										<tr>
											<td><?php echo $interacaoAnalise;?></td>
											<td>
												<input type="hidden" name="idAnalise[]" value="<?php echo $idAnalise;?>" />
												<input type="hidden" name="analises[]" value="<?php echo $textoAnalise;?>" />
												<?php echo nl2br($textoAnalise);?>
											</td>
											<td align="center">
												<select name="desenvAnalise[]">
													<option value="0" <?php echo ($desenvAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($desenvAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
											<td align="center">
												<select name="testeAnalise[]">
													<option value="0" <?php echo ($testevAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($testeAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
										</tr>

									<?php endfor;?>

								</table>

							<?php 
							#HD 351094
							if($atualizacaoDados):?>
								<div class="salvarAnalise">
									<input type="hidden" name="_POST" value="analiseTeste1" />
									<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
									<input type="submit" name="salvar" value="Salvar" />
								</div>
							</form>
							<?php endif;?>
						</div>
					<?php endif;?>

					<?php
					#Corrreções
					?>
					<p class="titulo_cab link" onclick="analise.showData('correcoes_lista')">
						<a href="javascript:void(0)"><strong>Correções Análise</strong></a>
					</p>
					<div id="correcoes_lista" style="display:none;">

						<?php
						$sql = 'SELECT DISTINCT(rodada),rodada_finalizada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res = pg_query($con,$sql);
						$totalRodada = pg_num_rows($res);

						$i=0;
						?>

						<?php if($totalRodada>0):?>
							
							<?php for($i=0;$i<$totalRodada;$i++): ?>

								<?php
								$rodadaCorrecao = pg_fetch_result($res,$i,'rodada');
								$rodadaFinalizadaCorrecao = pg_fetch_result($res,$i,'rodada_finalizada');

								$textoRodada = ($rodadaFinalizadaCorrecao == 't') ? '( Finalizada )' : null;

								$style = ($i!=($totalRodada-1)) ? 'style="display:none;"' : null;
								?>

								<?php if($rodadaFinalizadaCorrecao == 't'):?>
									
									<p class="titulo_cab link" onclick="analise.showData('rodada_lista_<?php echo $rodadaCorrecao;?>')">
										<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada; ?></strong></a>
									</p>

									<div id="rodada_lista_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>

										<table border='0' cellpadding='2' class="table_analise">
											<tr>
												<th align="left">&nbsp;</th>
												<th align="left" width="35%">
													Descrição
												</th>
												<th align="left" width="35%">
													Análise
												</th>
												<th align="left">
													Gravidade
												</th>
												<th align="left">
													Atendido
												</th>
											</tr>
											
											<?php
											$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
														   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
													  FROM tbl_hd_chamado_correcao
													 WHERE hd_chamado = '.$hd_chamado.'
													   AND rodada = '.$rodadaCorrecao.'
													 ORDER BY interacao;';
											$res2 = pg_query($con,$sql);
											$totalCorrecao = pg_num_rows($res2);
											?>

											<?php if($totalCorrecao > 0):?>

												<?php for($j=0;$j<$totalCorrecao;$j++): ?>

													<?php
													$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
													$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
													$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
													$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
													$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
													$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
													$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');

													if($gravidadeCorrecao == 1){
														$gravidadeCorrecao = 'Leve';
													}elseif($gravidadeCorrecao == 5){
														$gravidadeCorrecao = 'Normal';
													}elseif($gravidadeCorrecao == 10){
														$gravidadeCorrecao = 'Grave';
													}

													if($atendidoCorrecao == 't'){
														$atendidoCorrecao = 'Sim';
													}elseif($atendidoCorrecao == 'f'){
														$atendidoCorrecao = 'Não';
													}else{
														$atendidoCorrecao = 'Não Aplicável';
													}
													?>

													<tr>
														<td>
															<?php echo $interacaoCorrecao;?>
														</td>
														<td>
															<?php echo nl2br($descricaoCorrecao);?>
														</td>
														<td>
															<?php echo nl2br($analiseCorrecao);?>
														</td>
														<td align="center">
															<?php echo $gravidadeCorrecao;?>
														</td>
														<td>
															<?php echo $atendidoCorrecao;?>
														</td>
													</tr>

												<?php endfor;?>

											<?php endif;?>

										</table>

									</div>

								<?php else: ?>

									<?php 
									#HD 351094
									if($atualizacaoDados):?>
									<form name='frm_correcao_<?php echo $rodadaCorrecao;?>' action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post'>
									<?endif;?>

										<p class="titulo_cab link" onclick="analise.showData('rodada_lista_<?php echo $rodadaCorrecao;?>')">
											<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada; ?></strong></a>
										</p>
										
										<div id="rodada_lista_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>
											<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
												<tr>
													<th>&nbsp;</th>
													<th align="left" width="35%">
														Descrição
													</th>
													<th align="left"  width="35%">
														Análise
													</th>
													<th align="left">
														Gravidade
													</th>
													<th align="left">
														Atendido
													</th>
													<th align="left">
														&nbsp;
													</th>
												</tr>
												
												<?php
												$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
															   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
														  FROM tbl_hd_chamado_correcao
														 WHERE hd_chamado = '.$hd_chamado.'
														   AND rodada = '.$rodadaCorrecao.'
														 ORDER BY interacao;';
												$res2 = pg_query($con,$sql);
												$totalCorrecao = pg_num_rows($res2);
												?>

												<?php if($totalCorrecao > 0):?>

													<?php for($j=0;$j<$totalCorrecao;$j++): ?>

														<?php
														$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
														$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
														$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
														$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
														$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
														$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
														$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');
														?>

														<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $j;?>">
															<td><?php echo $interacaoCorrecao;?></td>
															<td>
																<input type="hidden" name="idCorrecao[]" value="<?php echo $idCorrecao;?>" />
																<input type="hidden" name="rodadaCorrecao[]" value="<?php echo $rodadaCorrecao;?>" />
																<textarea name="descricaoCorrecaos[]" style="width:230px;height:100px"><?php echo nl2br($descricaoCorrecao);?></textarea>
															</td>
															<td>
																<textarea name="analiseCorrecaos[]" style="width:230px;height:100px"><?php echo nl2br($analiseCorrecao);?></textarea>
															</td>
															<td align="center">
																<select name="gravidadeCorrecaos[]">
																	<option value="1" <?php echo ($gravidadeCorrecao == 1) ? 'selected="selected"' : null;?>>Leve</option>
																	<option value="5" <?php echo ($gravidadeCorrecao == 5) ? 'selected="selected"' : null;?>>Normal</option>
																	<option value="10" <?php echo ($gravidadeCorrecao == 10) ? 'selected="selected"' : null;?>>Grave</option>
																</select>
															</td>
															<td align="center">
																<select name="atendidoCorrecaos[]">
																	<option value="NULL" <?php echo (!$atendidoCorrecao) ? 'selected="selected"' : null;?>>Não aplicável</option>
																	<option value="TRUE" <?php echo ($atendidoCorrecao == 't') ? 'selected="selected"' : null;?>>Sim</option>
																	<option value="FALSE" <?php echo ($atendidoCorrecao == 'f') ? 'selected="selected"' : null;?>>Não</option>
																</select>
															</td>
															<td valign="middle" align="center">
																<?php 
																#HD 351094
																if($atualizacaoDados):?>
																<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteCorrecao(<?php echo $idCorrecao;?>,<?php echo $rodadaCorrecao;?>,<?php echo $j;?>)"> X </a>
																<?php endif;?>
															</td>
														</tr>

													<?php endfor;?>
												
												<?php endif;?>

											</table>

									<?php 
									#HD 351094
									if($atualizacaoDados):
										$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_correcao',$hd_chamado,$rodadaCorrecao);
										?>
											<p class="finalizarCorrecoes">
												<label>
													<input type="checkbox" name="rodadaFinalizada" value="TRUE" />
													Finalizar Rodada de Correções
												</label>
											</p>

											<div class="salvarAnalise">
												<input type="hidden" name="_POST" value="correcao1" />
												<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
												<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
												<input type="submit" name="salvar" value="Salvar" />
											</div>
											
											<input type="hidden" id="numeroCorrecao_<?php echo $rodadaCorrecao;?>" value="<?php echo ($maiorInteracao+1);?>" />
											[ <a href="javascript:void(0)" onclick="analise.addCorrecao(<?php echo $rodadaCorrecao;?>)">Adicionar Correção</a> ]

										</div>

									</form>
									<?php endif;?>

								<?php endif;?>

							<?php endfor;?>

						<?php else:?>

							<table border='0' cellpadding='2' class="table_analise" id="frm_correcao">
								<tr class="naoCorrecao">
									<td align="center" class="sub_label">
										Nenhuma Rodada Cadastrada
									</td>
								</tr>
							</table>

						<?php endif;?>
						
						<?php
						$sql = 'SELECT MAX(rodada) as maior_rodada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res3 = pg_query($con,$sql);
						$num3 = pg_num_rows($res3);
						
						$maior_rodada = pg_fetch_result($res3,0,'maior_rodada');
						$maior_rodada = ($maior_rodada) ? ($maior_rodada+1) : 1;

						#HD 351094
						if($atualizacaoDados):?>
						<div id="nova_rodada">
							<div align="center">
								<input type="hidden" id="numeroCorrecao_<?php echo $maior_rodada;?>" value="0" />
								[ <a href="javascript:void(0)" onclick="analise.addRodada(<?php echo $maior_rodada;?>,<?php echo $hd_chamado;?>)">Adicionar Nova Rodada</a> ]
							</div>
						</div>
						<?php endif;?>

					</div>

					<p class="titulo_cab link" onclick="analise.showData('correcoes_teste')">
						<a href="javascript:void(0)"><strong>Correções Teste</strong></a>
					</p>
					<div id="correcoes_teste" style="display:none;">

						<?php
						$sql = 'SELECT DISTINCT(rodada),rodada_finalizada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res = pg_query($con,$sql);
						$totalRodada = pg_num_rows($res);
						$i=0;
						?>

						<?php if($totalRodada>0):?>

							<?php for($i=0;$i<$totalRodada;$i++): ?>

								<?php
								$rodadaCorrecao = pg_fetch_result($res,$i,'rodada');
								$rodadaFinalizadaCorrecao = pg_fetch_result($res,$i,'rodada_finalizada');

								$style = ($i!=($totalRodada-1)) ? 'style="display:none;"' : null;
								$textoRodada = ($rodadaFinalizadaCorrecao == 't') ? '( Finalizada )' : null;
								?>

								<p class="titulo_cab link" onclick="analise.showData('rodada_teste_<?php echo $rodadaCorrecao;?>')">
									<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada;?></strong></a>
								</p>

								<?php 
								#HD 351094
								if($atualizacaoDados):?>
								<form name='frm_correcao_teste_<?php echo $rodadaCorrecao;?>' id="frm_correcao_teste_<?php echo $rodadaCorrecao;?>" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
								<?php endif;?>	
								
									<div id="rodada_teste_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>
										<table border='0' cellpadding='2' width="650" class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
											<tr>
												<th align="left">&nbsp;</th>
												<th align="left" style="width:250px">
													Descrição
												</th>
												<th align="left" style="width:250px">
													Análise
												</th>
												<th align="left">
													Gravidade
												</th>
												<th align="left">
													Atendido
												</th>
											</tr>
											<?php
											$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
														   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
													  FROM tbl_hd_chamado_correcao
													 WHERE hd_chamado = '.$hd_chamado.'
													   AND rodada = '.$rodadaCorrecao.'
												  ORDER BY interacao;';
											$res2 = pg_query($con,$sql);
											$totalCorrecao = pg_num_rows($res2);
											?>

											<?php if($totalCorrecao > 0):?>

												<?php for($j=0;$j<$totalCorrecao;$j++): ?>

													<?php
													$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
													$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
													$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
													$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
													$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
													$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
													$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');
													?>

													<tr id="correcao_teste_<?php echo $rodadaCorrecao;?>_<?php echo $j;?>">
														<td><?php echo $interacaoCorrecao;?></td>
														<td style="width:240px">
															<div style="border:0;max-width:230px;word-wrap:break-word;display:block;">
																<input type="hidden" name="idCorrecao[]" value="<?php echo $idCorrecao;?>" />
																<input type="hidden" name="rodadaCorrecao[]" value="<?php echo $rodadaCorrecao;?>" />
																<input type="hidden" name="descricaoCorrecaos[]" value="<?php echo $descricaoCorrecao;?>" />
																<?php echo $descricaoCorrecao;?>
															</div>
														</td>
														<td style="width:240px">
															<div style="border:0;max-width:230px;word-wrap:break-word;">
																<input type="hidden" name="analiseCorrecaos[]" value="<?php echo $analiseCorrecao;?>" />
																<?php echo $analiseCorrecao;?>
															</div>
														</td>
														<td align="center">
															<input type="hidden" name="gravidadeCorrecaos[]" value="<?php echo $gravidadeCorrecao;?>" />
															<?php 
															if($gravidadeCorrecao == 1){
																echo 'Leve';
															}elseif($gravidadeCorrecao == 5){
																echo 'Normal';
															}elseif($gravidadeCorrecao == 10){
																echo 'Grave';
															}?>
														</td>
														<td align="center">
															<select name="atendidoCorrecaos[]">
																<option value="NULL" <?php echo (!$atendidoCorrecao) ? 'selected="selected"' : null;?>>Não aplicável</option>
																<option value="TRUE" <?php echo ($atendidoCorrecao == 't') ? 'selected="selected"' : null;?>>Sim</option>
																<option value="FALSE" <?php echo ($atendidoCorrecao == 'f') ? 'selected="selected"' : null;?>>Não</option>
															</select>
														</td>
													</tr>

												<?php endfor;?>
											
											<?php endif;?>

										</table>

									<?php 
									#HD 351094
									if($atualizacaoDados):?>
										<div class="salvarAnalise">
											<input type="hidden" name="_POST" value="correcao1" />
											<input type="hidden" name="at" value="1" />
											<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
											<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
											<input type="submit" name="salvar" value="Salvar" />
										</div>
									<?php endif;?>

									</div>

								<?php 
								#HD 351094
								if($atualizacaoDados):?>
								</form>
								<?php endif;?>

							<?php endfor;?>

						<?php else:?>

							<table border='0' cellpadding='2' class="table_analise" id="frm_correcao">
								<tr class="naoCorrecao">
									<td align="center" class="sub_label">
										Nenhuma Rodada Cadastrada
									</td>
								</tr>
							</table>

						<?php endif;?>

					</div>

					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_analise_proc' id="frm_analise_proc" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
					
						<div>
							<p class="titulo_cab"><strong>Procedimento de Teste</strong></p>
							<textarea name="procedimento_teste" id="procedimento_teste" class="textareaEfetivacao"><?php echo $procedimento_teste;?></textarea>
						</div>
					
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="teste1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
					
				</div>
			</li>
			
			<?php 
			#Aba de Orçamento 
			?> 
			<li class="purpleHD">
				<strong class="menuAnalise">Orçamento</strong>
				<div style="display:none;">
					
					<?php 
					#HD 351094
					$totalHoras = ($horas_suporte+$horas_analise+$prazo_horas+$horas_teste+$horas_efetivacao);
					
					if($atualizacaoDados):?>
					<form name='frm_orcamento' id="frm_orcamento" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>

						<div>
							<p class="titulo_cab"><strong>Estimativa de Horas</strong></p>
						</div>
						
						<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
							<tr>
								<th align="left">Horas Suporte</th>
								<th align="left"  width="35%">
									<input type="text" size="2" maxlength="5" name="horas_suporte" value="<?php echo $horas_suporte;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Análise</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_analise" value="<?php echo $horas_analise;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Desenvolvimento</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="prazo_horas" value="<?php echo $prazo_horas;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Teste</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_teste" value="<?php echo $horas_teste;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Efetivação</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_efetivacao" value="<?php echo $horas_efetivacao;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Total de Horas</th>
								<th align="left" id="total_horas">
									<?php echo $totalHoras;?> h
								</th>
							</tr>
						</table>
					
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="orcamento1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>
			
			<?php 
			#Aba de Validação
			?> 
			<li class="pinkHD">
				<strong class="menuAnalise">Validação</strong>
				<div style="display:none;">
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_validacao' id="frm_validacao" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
						<div>
							<p class="titulo_cab"><strong>Procedimento de Validação</strong></p>
							<textarea name="validacao" id="validacao" class="textareaValidacao"><?php echo $validacao;?></textarea>
						</div>
					<?php 
					#HD 351094
					if($atualizacaoDados):?>	
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="validacao1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>
			
			<?php 
			#Aba de Efetivação
			?>
			<li class="grayHD">
				<strong class="menuAnalise">Efetivação</strong>
				<div style="display:none;">
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_efetivacao' id="frm_efetivacao" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
						<div>
							<p class="titulo_cab"><strong>Procedimento de Efetivação</strong></p>
							<textarea name="procedimento_efetivacao" id="procedimento_efetivacao" class="textareaEfetivacao"><?php echo $procedimento_efetivacao;?></textarea>
						</div>
					<?php 
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="_POST" value="efetivacao1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>	

		</ul>
		
		<input type="button" onclick="analise.inicioTrabalho(<?php echo $hd_chamado;?>)" value="Início do Trabalho" />
		<input type="button" onclick="analise.fimTrabalho(<?php echo $hd_chamado;?>)" value="Fim do Trabalho" />
	</div>
<?php 
//Fim modificação Thiago - HD: 304470 
?>

<BR>
<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype="multipart/form-data">
	<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

	<table width = '700px' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
		<tr>
		<td valign='top'>
			<table width = '475px' align = 'center' class='tab_cabeca' border='0' cellpadding='2'  >
				<? if ($status == "Análise") {?>
					<tr>
						<td class = 'titulo_cab'><strong>Seqüência </strong></td>
						<td class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Análise</label>
							<br>
							<input type='radio' name='sequencia' value='AGUARDANDO' id='aguardando'><label for='aguardando'>Aguard.Execução</label>
							<br>
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
						</td>
					</tr>
				<? } ?>

				<? if ($status == "Aguard.Execução") {?>
					<tr>
						<td class = 'titulo_cab' ><strong>Seqüência </strong></td>
						<td class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua Aguard.Execução</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
						</td>
					</tr>
				<? }

				if ($status == "Execução") {?>
					<tr>
						<td class = 'titulo_cab'><strong>Seqüência </strong></td>
						<td  class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Execução</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Resolvido</label>
						</td>
					</tr>
				<? } ?>
				<tr>
					<td  align='center' colspan='2'  class='sub_label'>
						<textarea name='comentario' style="width:450px;height:320px;" wrap='VIRTUAL' id='comentario'><?echo $comentario;?></textarea><br>
						<input type='checkbox' name='exigir_resposta' value='t' id='exigir_resposta'><label for='exigir_resposta'>Exigir resposta do usuário</label>
						<input type='checkbox' name='interno' value='t' id='interno'><label for='interno'>Chamado Interno</label>
					</td>
				</tr>
				<tr>
					<td align='center' colspan='2' class='sub_label'>
						Arquivo <input type='file' name='arquivo' size='50' class='frm'>
					</td>
				</tr>
				<tr>
					<td align='center' class='sub_label'>
						<center><input type='submit' name='btn_telefone' value='Telefone'>
						</center>
					</td>
					<td align='center' class='sub_label'>
						<center><input type='submit' name='btn_acao' value='Responder Chamado'>
						</center>
					</td>
				</tr>
			</table>
		</td>
		<td valign='top'>
			<table width = '225px' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >
				<tr>
					<td colspan='2' align='center' class='titulo_cab'><strong><font size='5'><?echo $hd_chamado; ?></font></strong></td>
				</tr>
				<tr>
					<td class ='sub_label'><strong>Status </strong></td>
					<td class ='sub_label'  align = 'center' >
						<select name="status" size="1"  style='width: 150px;'>
							<!--<option value=''></option>-->
							<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
							<option value='Requisitos'   <? if($status=='Requisitos')   echo ' SELECTED '?> >Requisitos</option>
							<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
							<option value='Orçamento'   <? if($status=='Orçamento')   echo ' SELECTED '?> >Orçamento</option>
							<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
							<option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
							<option value='Agendado'  <? if($status=='Agendado')  echo ' SELECTED '?> >Agendado</option>
							<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
							<option value='Teste'  <? if($status=='Teste')  echo ' SELECTED '?> >Teste</option>
							<option value='Correção'  <? if($status=='Correção')  echo ' SELECTED '?> >Correção</option>
							<option value='Validação'  <? if($status=='Validação')  echo ' SELECTED '?> >Validação</option>
							<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
							<option value='Aguard.Verifica' <? if($status=='Aguard.Verifica') echo ' SELECTED '?> >Aguard.Verificação</option>
							<option value='Aguard.Admin'  <? if($status=='Aguard.Admin')  echo ' SELECTED '?> >Aguard.Admin</option>
							<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
						</select>
					</td>
				</tr>
				<tr>
					<td  class ='sub_label'><strong>Atendente</strong></td>
					<td  class ='sub_label' align='center' >
						<?
						$sql = "SELECT  *
								FROM    tbl_admin
								WHERE   tbl_admin.fabrica = 10
								and ativo is true
								ORDER BY tbl_admin.nome_completo;";
						$res = pg_query($con,$sql);

						if ( pg_num_rows($res) > 0) {
							echo "<select class='frm' style='width: 150px;' name='transfere'>\n";
							echo "<option value=''>- ESCOLHA -</option>\n";

							for ($x = 0 ; $x <  pg_num_rows($res) ; $x++){
								$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
								$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));

								echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
							}
							echo "</select>\n";
						}
						?>
					</td>
				</tr>
				<tr>
					<td class ='sub_label' ><strong>Categoria </strong></td>
					<td  class ='sub_label' align='center'>
						<select name="categoria" size="1"  style='width: 150px;'>
							<option></option>
							<option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
							<option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
							<option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
							<option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
							<option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
							<option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
							<option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
							<option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
							<option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
							<option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
							<option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class ='sub_label'><strong>Tipo </strong></td>
					<td  class ='sub_label' align='center'>
						<select name="tipo_chamado" size="1"  style='width: 150px;'>
						<?
						$sql = "SELECT	tipo_chamado,
											descricao
									FROM tbl_tipo_chamado
									ORDER BY descricao;";
							$res = pg_query($con,$sql);
							if( pg_num_rows($res)>0){
									for($i=0; pg_num_rows($res)>$i;$i++){
										$xtipo_chamado = pg_fetch_result($res, $i, 'tipo_chamado');
										$xdescricao    = pg_fetch_result($res, $i, 'descricao');
										echo "<option value='$xtipo_chamado' ";
										if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
										echo " >$xdescricao</option>";
									}
							}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<?
					if(strlen($hd_chamado)>0){
						if($login_admin == 822 ) {
							$cond1 = " AND tbl_hd_chamado_atendente.admin = 822 ";
						}
						$wsql ="SELECT SUM(case when data_termino is null
											THEN current_timestamp
											ELSE data_termino end - data_inicio )
								FROM tbl_hd_chamado_atendente
								JOIN tbl_admin using(admin)
								WHERE hd_chamado = $hd_chamado
								$cond1
								/*AND   responsabilidade in ('Analista de Help-Desk','Programador')*/";
						$wres = pg_query($con, $wsql);
						if( pg_num_rows($wres)>0)
						$horas= pg_fetch_result($wres, 0, 0);
						if(strlen($horas)==0){
							$horas = "00:00:00";
						}
						$xhoras = explode(":",$horas);
						$horas = $xhoras[0].":".$xhoras[1];
					}
					?>

					<td  class ='sub_label'><strong>Trabalhadas </strong></td>
					<?
					echo "<td  class ='sub_label'align='center' title='";

					$sqlx = "SELECT tbl_admin.login,
									tbl_hd_chamado_atendente.data_inicio,
									TO_CHAR(tbl_hd_chamado_atendente.data_inicio,'DD/MM/YYYY hh24:mi:ss') as inicio,
									TO_CHAR(tbl_hd_chamado_atendente.data_termino,'hh24:mi:ss') as fim
							FROM tbl_hd_chamado_atendente
							JOIN tbl_admin USING(admin)
							WHERE hd_chamado = $hd_chamado
							ORDER BY tbl_hd_chamado_atendente.data_inicio";
					$resx = pg_query($con, $sqlx);

					for ($i=0;$i< pg_num_rows($resx);$i++) {
						echo pg_fetch_result($resx, $i, 'login')." (".pg_fetch_result($resx, $i, 'inicio')." - ".pg_fetch_result($resx, $i, 'fim').")\n";
					}
					echo "'> $horas h</td>";
					?>
				</tr>

				<? if($analista_hd == "sim"){ ?>
					<tr>
						<td  class ='sub_label'><strong>Horas Desenvolvimento<span title='Hora interna que será pago para o analista desenvolvedor'>(?)</span></strong></td>
						<td  class ='sub_label'align='center'>
						<input type='text' size='2' maxlength ='5' name='prazo_horas' value='<?= $prazo_horas ?>' class='caixa' onblur="javascript:checarNumero(this);atualizaPrazo('<?echo $hd_chamado;?>',this.value)"> h
						<div id='result' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:150px;'>
						</div>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label' title='Horas que será deduzida da quantidade de horas da franquia do fabricante.'><strong>Horas a cobrar(?)</strong></td>
						<td  class ='sub_label'align='center'>
						<input type='text' size='2' maxlength ='5' name='hora_desenvolvimento' value='<?= $hora_desenvolvimento ?>' <?
						?> class='caixa' onblur="javascript:checarNumero(this);atualizaHr('<?echo $hd_chamado;?>',this.value)"> h<BR>
						<input type='text' size='16' maxlength ='16' name='previsao_termino' id='previsao_termino' value='<?= $previsao_termino ?>' <?
						?> class='caixa' onblur="javascript:atualizaPrevisaoTermino('<?echo $hd_chamado;?>',this.value)"> Dt
						<div id='result2' style='position:absolute; display:none;  border: 1px solid #949494;background-color: #F1F0E7;width:100px;'>
						</div>
						</td>
					</tr>
					<tr>
						<td class ='sub_label' ><strong>Cobrar ? </strong></td>
						<td  class ='sub_label' align='center'>
						<input type='checkbox' name='cobrar' value='t' <? if ($cobrar == "t") echo "Checked";?>> Sim

						</td>
					</tr>
					<tr>
						<td class ='sub_label' ><strong>Prioridade ? </strong></td>
						<td  class ='sub_label' align='center'>
						<input type='checkbox' name='prioridade' value='t' <? if ($prioridade == "t") echo "Checked";?>> Sim

						</td>
					</tr>
				<? }else{ ?>
					<tr>
						<td  class ='sub_label'><strong>Desenvol.</strong></td>
						<td  class ='sub_label'align='center'>
							<?= $hora_desenvolvimento ?> h
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Cobrar ? </strong></td>
						<td  class ='sub_label' align='center'>
							<input type='hidden' name='cobrar' value='<? echo $cobrar;?>'>
							<? if ($cobrar == "t"){ echo "Sim";}else{ echo "Não";}?>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Prioridade ? </strong></td>
						<td  class ='sub_label' align='center'>
							<input type='hidden' name='prioridade' value='<? echo $prioridade;?>'>
							<? if ($prioridade == "t"){ echo "Sim";}else{ echo "Não";}?>
						</td>
					</tr>
				<? } ?>
					<tr>
						<td class ='sub_label'><B>Horas Pendentes</B></td>
						<td  class ='sub_label' align="center">
							<a href="javascript:abrir('adm_analistas_hora.php');"><strong>Horas por Analistas</strong></a>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Arquivo:</strong></td>
						<td   class ='sub_label' align='center'>
							<input name='programa' id='programa'value='' class='caixa' size='25' onKeyUp = 'recuperardados(<? echo $hd_chamado?>)' onblur='this.value=""'><br>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Desenvolvedor Responsável</strong></td>
						<td  class ='sub_label' align='center' >
						<?
						$sql = "SELECT  admin,nome_completo
								FROM    tbl_admin
								WHERE   tbl_admin.fabrica = 10
								AND ativo IS TRUE
								ORDER BY tbl_admin.nome_completo;";
						$res = pg_query($con,$sql);
						$totalDesenv = pg_num_rows($res);

						if ($totalDesenv  > 0) {
							echo "<select class='frm' style='width: 150px;' name='admin_desenvolvedor' onchange='return muda_admin(\"$admin_desenvolvedor\",this);'>\n";
							echo "<option value=''>- ESCOLHA -</option>\n";

							for ($x=0;$x<$totalDesenv;$x++){

								$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
								$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));
								
								if($admin_desenvolvedor == $aux_admin){
									$selected = 'selected';
									$indice = ($x+1);
								}else{
									$selected = null;
								}

								echo "<option value='$aux_admin'".$selected."> $aux_nome_completo</option>\n";
							}
							echo "</select>\n";
							echo "<input type='hidden' value='$indice' id='indexDesenv'>\n";
						}
						?>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label' colspan='2'><div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no mínimo <br>4 caracteres</div>&nbsp;</td>
					</tr>
				</TABLE>
			</td>
		</tr>
		<tr>
			<td colspan='2'>

				<?
				$sql = "SELECT
							tbl_arquivo.descricao AS arquivo,
							to_char (tbl_controle_acesso_arquivo.data_inicio,'DD/MM') AS data_inicio,
							to_char (tbl_controle_acesso_arquivo.hora_inicio,'HH24:MI') AS hora_inicio,
							to_char (tbl_controle_acesso_arquivo.data_fim,'DD/MM') AS data_fim,
							to_char (tbl_controle_acesso_arquivo.hora_fim,'HH24:MI') AS hora_fim
						FROM tbl_arquivo
						JOIN tbl_controle_acesso_arquivo USING(arquivo)
						WHERE hd_chamado=$hd_chamado
						ORDER BY tbl_controle_acesso_arquivo.data_inicio";
				$res_arquivos = pg_query($con,$sql);
				echo "<table width = '750' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >";

				if (@ pg_num_rows($res_arquivos) > 0) {
					echo "<tr  bgcolor='#D9E8FF'; style='font-family: arial ; font-size: 10px ;'>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Início</b></td>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'align='center'><b>Histórico dos Arquivos Utilizados</b></td>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Fim</b></td>\n";
					echo "</tr>\n";
					$arquivo = "";
					$data_inicio = "";
					$data_fim = "";
					for ($k = 0 ; $k <  pg_num_rows($res_arquivos) ; $k++) {
						$arquivo	.= str_replace ("/var/www/assist/www/","",pg_fetch_result($res_arquivos, $k, 'arquivo'))."<br>";
						$data_inicio.= pg_fetch_result($res_arquivos, $k, 'data_inicio')."  ".pg_fetch_result($res_arquivos, $k, 'hora_inicio')."<br>";
						$data_fim.= pg_fetch_result($res_arquivos, $k, 'data_fim')."  ".pg_fetch_result($res_arquivos, $k, 'hora_fim')."<br>";
					}
					echo "<tr style='font-family: arial ; font-size: 10px ;' height='25'>\n";
					echo "<td nowrap>$data_inicio</td>\n";
					echo "<td align='left' style='padding-left:10px'>$arquivo</td>\n";
					echo "<td nowrap>$data_fim</td>\n";
					echo "</tr>\n";
				}
				?>
				</table>
			</td>
		</tr>

	</table>
		
	<?
	if($login_admin == 822) {
		$cond = " AND tbl_hd_chamado_item.comentario not ilike 'Término de trabalho automático'
				  AND tbl_hd_chamado_item.comentario not ilike 'Chamado Transferido'
				  AND tbl_hd_chamado_item.comentario not ilike 'Categoria Alterada de%' ";
	}
	$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
			to_char (tbl_hd_chamado_item.data,'DD/MM/YY HH24:MI') AS data   ,
			tbl_hd_chamado_item.comentario                               ,
			tbl_hd_chamado_item.interno                                  ,
			tbl_admin.nome_completo                            AS autor  ,
			(select to_char(sum(termino - data),'HH24:MI') from tbl_hd_chamado_item where hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item) as a,
			tbl_hd_chamado_item.status_item
			FROM tbl_hd_chamado_item
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
			WHERE hd_chamado = $hd_chamado
			$cond
			ORDER BY hd_chamado_item DESC";
	$res = @pg_query($con,$sql);

	if (@ pg_num_rows($res) > 0) {
		echo "<BR><BR><table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
		echo "<thead>";
			echo "<tr  bgcolor='#D9E8FF'>";
			echo "<th><strong>Nº</strong></th>";
			echo "<th  nowrap><strong>Data</strong></th>";
			//echo "<th  nowrap><strong>Tmp Trab.</strong></th>";
			echo "<th><strong>  Comentário </strong></th>";
			echo "<th  ><strong> Anexo </strong></th>";
			echo "<th  ><strong>Autor </strong></th>";
			echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
	
		$total_interacoes = pg_num_rows($res);

		for ($i = 0 ; $i < $total_interacoes ; $i++) {

			$x=($total_interacoes)-$i;
			$hd_chamado_item = pg_fetch_result($res, $i, 'hd_chamado_item');
			$data_interacao  = pg_fetch_result($res, $i, 'data');
			$autor           = pg_fetch_result($res, $i, 'autor');
			$item_comentario = pg_fetch_result($res, $i, 'comentario');
			$status_item     = pg_fetch_result($res, $i, 'status_item');
			$interno         = pg_fetch_result($res, $i, 'interno');
			//$tempo_trabalho  = pg_fetch_result($res, $i, 'tempo_trabalho');

			//$autor = explode(" ",$autor);
			//$autor = $autor[0];

			echo "<tr  bgcolor='$cor'>";
			echo "<td nowrap width='25'>$x </td>";
			echo "<td nowrap width='50'>$data_interacao </td>";
			//echo "<td nowrap width='40'>$tempo_trabalho</td>";
			echo "<td  width='520'>";
			if ($status_item == 'Resolvido'){

				echo "<span class='resolvido'><b>Chamado foi resolvido nesta interação</b></span>";

			}
			if($interno == 't'){
				echo "<span class='interno'><b>Chamado interno</b></span>";

			}
			$xcomentario = strtoupper($item_comentario);
			if(strpos($xcomentario,"<DIV") > 0 or strpos($xcomentario,"<TR") > 0){
				$item_comentario = strip_tags($item_comentario,'<p><br><a>');
			}
			echo "<font size='1'>" . nl2br(str_replace($filtro,"", $item_comentario)) . "
			</td>";

			echo "<td width='25'>";

			$dir = "documentos/";
			$dh  = opendir($dir);
	//		echo "$hd_chamado_item";
			while (false !== ($filename = readdir($dh))) {
				if (strpos($filename,"$hd_chamado_item") !== false){
					$po = strlen($hd_chamado_item);
					if(substr($filename, 0,$po)==$hd_chamado_item){
						echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
					}
				}
			}
			echo "</td>";
			echo "<td >$autor</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}
	?>

<?
/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
echo "<br><DIV class='exibe' id='dados' value='1' align='center'><font size='1'>Por favor aguarde um momento, carregando os dados...<br><img src='../imagens/carregar_os.gif'></FONT></DIV>";
echo "<script language='javascript'>Exibir('dados','','','');</script>";

if($login_admin <> 822 and $login_admin <> 398 and $login_admin<>1375){
	echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";
}
echo "</form>";

?>
<? include "rodape.php" ?>