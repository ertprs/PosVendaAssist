<?php
if (!function_exists('sql_where')) {
	require_once(__DIR__ . DIRECTORY_SEPARATOR . '../helpdesk/mlg_funciones.php');
}

function sql_cmd($conn, $table, $values=null, $where=null) {

	/**
	 * Configuração do comportamento da função
	 * delete_keypass  se FALSE, $value DEVE ser null ou a palavra 'delete' minúsculo
	 *                 para qualquer outro valor, só esse valor será aceito para fazer
	 *                 DELETE. É uma medida de segurança para evitar efeitos permanentes
	 *                 caso aconteça um erro de validação quando for fazer algum outro
	 *                 tipo de ação.
	 * allow_exec      Se TRUE, espera que o primeiro parâmetro seja um recurso de conexão
	 *                 e irá permitir que a instrução SQL gerada seja executada e o recurso
	 *                 de resultado seja retornado.
	 *                 Se FALSE, irá ignorar o primeiro parâmetro (se for um resource) e
	 *                 não será executada nenhuma instrução SQL, retonando apenas a STRING
	 *                 com a instrução resultado da interpretação dos outros parâmetros.
	 * force_utf8      TRUE:  always return/use UTF8 strings, convert if necessary
	 *                 FALSE: NEVER use UTF8, convert to Latin1 if necessary
	 *                 NULL:  ignore: won't check nor convert strings
	 *                 default FALSE
	 */
	$delete_keypass = false;
	$allow_exec     = false;
	$force_utf8     = false;

	if (!$allow_exec or !is_resource($conn)) {
		global $con;
		$execute = false;

		// shift parameters
		$where  = $values;
		$values = $table;
		$table  = $conn;
		$conn   = $GLOBALS['con'];
	} else {
		$db_exec_command = array(
			'pgsql'  => 'pg_query',     'mysql' => 'mysql_query',
			'sqlite' => 'sqlite_query', 'mssql' => 'mssql_query'
		);

		$resource_type = preg_replace("/(\w+).*/", '$0', get_resource_type($conn));
		$execcmd = $db_exec_command[$resource_type];
		$execute = true;
	}

	$parse_joins = function(array $tables) {
		// valida de certa forma o JOIN: se é INNER, não pode ser LEFT|RIGHT,
		// se é outer, DEVE ter left ou right.
		$modRegEx = '/^\s*(:?NATURAL\s)?(:?(:?CROSS|INNER)\s?|(:?(:?RIGHT|LEFT|FULL)\s)(:?OUTER\s)?)?JOIN/';

		$ret = array_shift($tables);

		foreach($tables as $mod=>$table) {
			if (preg_match($modRegEx, mb_strtoupper($table))):
				$ret .= "\n  $table";
			elseif (preg_match($modRegEx, mb_strtoupper($mod))):
				$ret .= "\n  $mod $table";
			else:
				continue;
			endif;
		}
		return $ret;
	};

	// "PosVenda extension": nome do índice da tabela é o nome da tabela sem o 'tbl_'
	// Para caso do $where ser um único valor numérico, assume que é o ID do registro
	// usando esse nome de coluna.
	$indexname = preg_replace('/(?:view|vw|tbl|tmp|temp)_(\w+)/', '$1', $table);

	if (!is_null($where) and is_numeric($where) and $indexname)
		$where = array($indexname => $where);

	// para fazer mais simples a escrita, irá aceitar o *
	// ou um CSV
	if (is_string($values) and (($delete_keypass and $delete_keypass === $values) or $values !== 'delete')) {
		if  (strlen($values))
			$values = array_filter(preg_split('/,\s*/', $values), 'strlen');
		else
			return false; // não queremos que faça um DELETE sem querer...
	}

	if (($delete_keypass and $delete_keypass === $values) or
			(($values === 'delete' or is_null($values)) and
			is_array($where) and !empty($table))) {
		$action = 'delete';
		$values = null;
	} else if (count($values) == count(array_filter(array_keys($values), 'is_numeric'))) {
		$action = !is_array($values[0]) ? 'select' : 'insert';
		$multiInsert = is_array($values[0]);
	} else {
		$multiInsert = isset($values[0]) and is_array($values[0]);
		$action = is_array($where) ? 'update' : 'insert';
	}

	$campos  = $multiInsert ? array_keys($values[0]) : ($action == 'select' ? $values : array_keys($values));
	$camposQuery = implode(', ', $campos);

	// CRUD
	switch ($action) {
		case 'insert':
			if ($multiInsert) { // inserir vários registros de uma só vez...
				foreach($values as $idx=>$rowData) {
					foreach($rowData as $fieldName=>$fieldValue) {
						// nome de campo no INSERT só pode ser nome de campo, nem função nem expressão
						if (!preg_match('/^\w+$/', $fieldName))
							return false;

						if ($fieldValue[0] === substr($fieldValue, -1) and $fieldValue[0] === "'") {
							continue;
						}
						$isStr = preg_match('/'.INT_FIELDS.'/', $fieldName);
						$rowData[$fieldName] = pg_quote($fieldValue, $isStr);
					}
					$dadosInsert[] = implode(', ', $rowData);
				}
				$valoresInsert = implode("\n      ), (\n        ", $dadosInsert);
			} else {
				foreach($values as $fieldName=>$fieldValue) {
					// nome de campo no INSERT só pode ser nome de campo, nem função nem expressão
					if (!preg_match('/^\w+$/', $fieldName))
						throw new Exception ("'$fieldName' is not a valid postgreSQL field name!");

					if ($fieldValue[0] === substr($fieldValue, -1) and $fieldValue[0] === "'") {
						$valores[$fieldName] = $fieldValue;
						continue;
					}
					$isStr = preg_match('/'.INT_FIELDS.'/', $fieldName);
					$valores[$fieldName] = pg_quote($fieldValue, $isStr);
				}
				$valoresInsert = implode(', ', $valores);
			}
			$sql = "INSERT INTO $table (\n        $camposQuery\n      ) VALUES (\n        $valoresInsert\n)";
		break;

		case 'select':
			if (is_array($table)) {
				$table = $parse_joins($table);
			}
			$sql  = "SELECT $camposQuery\n  FROM $table";
			$sql .= (is_array($where) and count($where)) ?
				"\n WHERE ".sql_where($where) :(
				is_string($where) and strlen($where) > 4 ? "\n WHERE $where" : ''
			);
		break;

		case 'update':
			$valores = array_values($values);

			foreach($campos as $idx=>$fieldName) {

				// nome de campo no INSERT só pode ser nome de campo, nem função nem expressão
				if (!preg_match('/^\w+$/', $fieldName))
					return false;

				$value = pg_quote($valores[$idx], preg_match('/'.INT_FIELDS.'/', $fieldName));
				$dadosQuery[] = "$fieldName = $value";
				$valores[$idx] = $value;
			}
			$camposUpdate = implode(",\n       ", $dadosQuery);

			$sql = "UPDATE $table\n   SET $camposUpdate\n WHERE " . sql_where($where);
		break;

		case 'delete':
			$sql = "DELETE FROM $table\n WHERE ".sql_where($where);
		break;
	}

	if ($sql) {
		if ($force_utf8 === true and !mb_check_encoding($sql, 'UTF8')) {
			$sql = utf8_encode($sql);
		}
		if ($force_utf8 === false and mb_check_encoding($sql, 'UTF8')) {
			$sql = utf8_decode($sql);
		}
		return $sql;
	}
	return false;
}
// vim: set noet ts=2 sts=2 fdm=syntax fdl=1 :

