<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$tipo 		= $_POST["tipo"];
$produto_referencia 	= $_POST["produto_referencia"];

switch($tipo) {
	case "atendimento_pela_familia_produto" :
		if(!empty($produto_referencia)){
			$sql = "
				SELECT 
                    tbl_produto.familia
				FROM 
                    tbl_produto 
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE 
                    tbl_produto.referencia = '$produto_referencia'
                    AND tbl_linha.fabrica = $login_fabrica;";
			$res = pg_query($con, $sql);
			
			if(pg_num_rows($res) == 1){
				$familia = pg_fetch_result($res, 0, "familia");
				$sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND familia = $familia";
				$res = pg_query($con, $sql);
				
				if(pg_num_rows($res) > 0){
					echo "<option value='0' selected>selecione um atendimento</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));
						
						echo "<option value='$tipo_atendimento' label='$descricao'>$descricao</option>";
					}
				}else
                    echo "<option value='0' selected>nenhum atendimento encontrado</option>";
			}else
                echo "<option value='0' selected>produto inválido</option>";
		}

	break;
	
	case "defeito_constatado_pela_familia_produto" :
		if(!empty($produto)){
			$sql = "
				SELECT familia
				FROM tbl_produto 
				WHERE produto = $produto;";
			$res = pg_query($con, $sql);
			
			if(pg_num_rows($res) == 1){
				$familia = pg_fetch_result($res, 0, "familia");
				
				$sql = "
						SELECT DISTINCT
							tbl_defeito_constatado.defeito_constatado, 
							tbl_defeito_constatado.descricao 
						FROM tbl_diagnostico 
							JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
						WHERE 
							tbl_defeito_constatado.fabrica = $login_fabrica 
							AND tbl_diagnostico.familia = $familia ORDER BY tbl_defeito_constatado.descricao ASC;";
				$res = pg_query($con, $sql);
				
				if(pg_num_rows($res) > 0){
					echo "<option value='0' selected>selecione um defeito constatado</option>";
					for($i = 0; $i < pg_num_rows($res); $i++) {
						extract(pg_fetch_array($res));
						
						echo "<option value='$defeito_constatado' label='$descricao'>$descricao</option>";
					}
				}else
					echo "<option value='0' selected>nenhum defeito constatado encontrado</option>";
			}else
				echo "<option value='0' selected>produto não encontrado</option>";
		}

	break;
}