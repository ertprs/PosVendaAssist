<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_admin.php';

switch($_GET["tipo"]) {
	case "publicar":
		$informativo = intval($_GET["informativo"]);
		$sql=" UPDATE tbl_informativo SET publicar = NOW() WHERE informativo={$informativo} AND publicar IS NULL  RETURNING TO_CHAR(publicar, 'DD/MM/YYYY HH24:MI')";
		@$res = pg_query($con, $sql);
		if(pg_affected_rows($res) > 0){
			$publicar = pg_fetch_result($res, 0, 0);
			echo "{$informativo}|{$publicar}";
			}
		else{
			$publicar=0;
			echo $publicar;
			}
	break;
	
	case "desativar":
		$informativo = intval($_GET["informativo"]);
		$sql="UPDATE tbl_informativo SET publicar=NULL WHERE informativo={$informativo} RETURNING publicar";
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)>0) {
		throw new Exception("Falha ao desativar informativo<erromsg='".pg_last_error($con)."'>");
		}
		if(pg_affected_rows($res) > 0){
			$publicar="";
			echo "{$informativo}|{$publicar}";
			}
	break;
	
	case "enviar":
		try {
		
		$informativo = intval($_GET["informativo"]);
		$sql = "UPDATE tbl_informativo SET enviar=NOW(), admin_enviar={$login_admin} 
		WHERE informativo={$informativo} 
		RETURNING TO_CHAR(enviar, 'DD/MM/YYYY HH24:MI'), admin_enviar";
		
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)>0) {
		throw new Exception("Falha ao atualizar informativo<erromsg='".pg_last_error($con)."'>");
		}
		$resultado = pg_fetch_array($res); 
		$enviar = $resultado['0'];
		$admin_numero = $resultado['1'];
		
		$sql = "SELECT nome_completo FROM tbl_admin WHERE admin={$admin_numero} ";
		
		@$res = pg_query($con, $sql);
		if (pg_last_error($con)>0) throw new Exception("Falha ao localizar administrador<erro msg='".pg_last_error($con)."'>");
		
		$resultado = pg_fetch_array($res);
		$admin_enviar = $resultado['0'];
		echo "{$informativo}|{$enviar}|{$admin_enviar}";
		
		}catch (Exception $e) {
			$msg_erro[] = $e->getMessage();
			echo (1);
			}
	break;
	
	case "excluir":
		try {
		$informativo = intval($_GET["informativo"]);
		
		$sql = "UPDATE tbl_informativo SET fabrica=0 WHERE informativo={$informativo}";
		$res = pg_query($con, $sql);
		if (pg_last_error($con)>0) {
		throw new Exception("Falha ao excluir informativo<erromsg='".pg_last_error($con)."'>");
		}
		else {
			$excluir++;
			}
		/*
		$sql="DELETE FROM tbl_informativo_modulo WHERE informativo={$informativo}";
		$res = pg_query($con, $sql);
		
		if (pg_last_error($con)>0) 
			throw new Exception("<erro msg='".pg_last_error($con)."'>");
		else $excluir++;
		
		$sql="DELETE FROM tbl_informativo WHERE informativo={$informativo}";
		$res = pg_query($con, $sql);
		//$excluir = pg_affected_rows($res);
		if (pg_last_error($con)>0) 
			throw new Exception("<erro msg='".pg_last_error($con)."'>");
		else $excluir++;
		*/
		echo $excluir;
		
		}catch (Exception $e) {
			$msg_erro[] = $e->getMessage();
			echo (0);
			}
			
	break;
}
?>
