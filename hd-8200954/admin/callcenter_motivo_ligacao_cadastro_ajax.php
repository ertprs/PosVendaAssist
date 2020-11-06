<?php 
/**
 * Arquivo que receve as consultas ajax da página de cadastro de motivos de ligações
 * HD 59746
 *
 * @author Augusto Pascutti <augusto.hp@gmail.com>
 */
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$action = ( isset($_POST['action']) ) ? $_POST['action'] : $_GET['action'] ;
switch ($action) {
	case 'consultar': // --------------------------------------------------------------------
		if ( ! isset($_GET['id']) ||  empty($_GET['id']) ) {
			echo 'error';
			break;
		}
		$sql = "SELECT hd_motivo_ligacao as id, descricao as descr
				FROM tbl_hd_motivo_ligacao
				WHERE fabrica = %s
				AND hd_motivo_ligacao = %s";
		$sql = sprintf($sql,pg_escape_string($login_fabrica),pg_escape_string($_GET['id']));
		$res = @pg_exec($con,$sql);
		if ( is_resource($res) && pg_numrows($res) > 0 ) {
			$row = pg_fetch_assoc($res,0);
			$str = array();
			foreach ($row as $attr=>$val) {
				$attr  = utf8_decode($attr);
				$val   = utf8_decode($val);
				$str[] = "'{$attr}': '{$val}'";
			}
			echo "{".implode(',',$str)."}";
		}
		break;
	case 'deletar': // ----------------------------------------------------------------------
		pg_exec($con,'BEGIN');
		if ( ! isset($_GET['id']) || empty($_GET['id']) ) {
			echo 'error';
			break;
		}
		$sql = "DELETE FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = %s and fabrica = %s";
		$sql = sprintf($sql,pg_escape_string($_GET['id']),pg_escape_string($login_fabrica));
		$res = pg_exec($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) > 0 ) {
			pg_exec($con,'COMMIT');
			echo "ok";
		} else {
			pg_exec($con,'ROLLBACK');
			echo 'error';
		}
		break;
	case 'atualizar': // --------------------------------------------------------------------
		if ( ! isset($_POST['id']) || ! isset($_POST['descr']) || empty($_POST['id']) || empty($_POST['descr']) ) {
			echo 'error';
			break;
		}
		$id    = pg_escape_string($_POST['id']);
		$descr = pg_escape_string($_POST['descr']);
		pg_exec($con,'BEGIN');
		$sql = "UPDATE tbl_hd_motivo_ligacao
				SET descricao = '%s'
				WHERE hd_motivo_ligacao = %s
				AND fabrica = %s";
		$sql = sprintf($sql,$descr,$id,pg_escape_string($login_fabrica));
		$res = pg_exec($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) == 1 ) {
			echo 'ok';
			pg_exec($con,'COMMIT');
		} else {
			echo 'error';
			pg_exec($con,'ROLLBACK');
		}
		break;
	case 'adicionar': // --------------------------------------------------------------------
		if ( ! isset($_POST['descr']) || empty($_POST['descr']) ) {
			echo 'error';
			break;
		}
		$descricao = pg_escape_string($_POST['descr']);
		pg_exec($con,'BEGIN');
		$sql = "SELECT nextval('seq_hd_motivo_ligacao') as id";
		$res = pg_exec($con,$sql);
		if ( ! is_resource($res) || pg_numrows($res) <= 0 ) {
			echo 'error';
			break;
		}
		$id  = pg_result($res,0,'id');
		$sql = "INSERT INTO tbl_hd_motivo_ligacao (hd_motivo_ligacao,fabrica,descricao) VALUES (%s,%s,'%s')";
		$sql = sprintf($sql,$id,pg_escape_string($login_fabrica),$descricao);
		$res = pg_exec($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) > 0 ) {
			pg_exec($con,'COMMIT');
			echo "{'id':{$id},'message':'ok'}";
		} else {
			pg_exec($con,'ROLLBACK');
			echo 'error';
		}
		break;
	default: // -----------------------------------------------------------------------------
		echo 'error';
		break;
}