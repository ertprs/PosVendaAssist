<?php
if (!function_exists("gera_token")) {
	function gera_token($fabrica ,$admin="", $posto="", $id_admin = "") {
		global $con;
		$cookie_json  = json_encode(array());
		$token_cookie = hash('sha512',rand(1000,10000).date("now").$fabrica.$admin.$posto);
		if(!empty($id_admin)) {
			$sql          = "INSERT INTO tbl_login_cookie(fabrica,token,cookie,admin) VALUES ($fabrica,'$token_cookie','$cookie_json', $id_admin)";
		}else{
			$sql          = "INSERT INTO tbl_login_cookie(fabrica,token,cookie) VALUES ($fabrica,'$token_cookie','$cookie_json')";
		}
        $res          = pg_query($con, $sql);
        if (pg_affected_rows($res) !== 1)
            return null;

		return $token_cookie;
	}
}

if (!function_exists("add_cookie")) {
	function add_cookie(&$cookie_json, $nome, $valor) {
		$cookie_json[$nome] = $valor;
		return $cookie_json;
	}
}

if (!function_exists("remove_cookie")) {
	function remove_cookie(&$cookie_json, $nome) {
		unset($cookie_json[$nome]);
		return $cookie_json;
	}
}

if (!function_exists("set_cookie_login")) {
	function set_cookie_login($token, $cookie_json) {
		global $con;

		if (!strlen($token))
			return null;

        $sql = "SELECT token,cookie FROM tbl_login_cookie WHERE token = '$token'";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
		$cookie_json = json_encode($cookie_json);
		$sql = "UPDATE tbl_login_cookie SET cookie = E'$cookie_json' WHERE token = '$token'";
		$res = pg_query($con, $sql);

		if (!pg_affected_rows($res)) {
			return null;
		}
		return $token;
		}
		return null;
	}
}

if (!function_exists("update_fabrica")) {
	function update_fabrica($token,$fabrica) {
		global $con;
		if (!strlen($token))
			return null;

		$sql = "SELECT token,cookie FROM tbl_login_cookie WHERE token = '$token'";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
			$cookie_json = json_encode($cookie_json);
			$sql = "UPDATE tbl_login_cookie SET fabrica = $fabrica WHERE token = '$token'";
            $res = pg_query($con, $sql);

            if (!is_resource($res) or pg_affected_rows($res) !== 1) {
				return null;
			}
			return $token;
		}
		return null;
	}
}

if (!function_exists("get_cookie_login")) {
	function get_cookie_login($token){
		global $con;

		$sql = "SELECT token,cookie FROM tbl_login_cookie WHERE token = '$token'";
        $res = pg_query($con, $sql);
        $cook_json = null;

        if (!pg_num_rows($res))
            return null;

        $rec = pg_fetch_assoc($res);

        if (array_key_exists('cookie', $rec))
            return json_decode($rec['cookie'], true);

        return null;
	}
}

if (!function_exists("remove_login_cookie")) {
	function remove_login_cookie($sess) {
		if (empty($sess)) {
			return false;
		}

		global $con;

		pg_query($con, 'BEGIN TRANSACTION');

		$res = pg_query($con, "DELETE FROM tbl_login_cookie WHERE token = '$sess'");

        if (pg_affected_rows($res) == 1) {
			pg_query($con, 'COMMIT TRANSACTION');
			return true;
		}

		pg_query($con, 'ROLLBACK TRANSACTION');
		return false;
	}
}

if (!function_exists("check_session_duration")) {
    function check_session_duration($sess, $duration) {
        if (empty($sess) or empty($duration)) {
            return false;
        }

        global $con;

        $sql = "SELECT * FROM tbl_login_cookie
            WHERE token = '$sess' AND data_input >= (CURRENT_TIMESTAMP - INTERVAL '$duration')";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 1) {
            return true;
        }

        return false;
    }
}

if (!function_exists("update_session_timestamp")) {
    function update_session_timestamp($sess) {
        if (empty($sess)) {
            return false;
        }

        global $con;

		pg_query($con, 'BEGIN TRANSACTION');

        $sql = "UPDATE tbl_login_cookie SET data_input = CURRENT_TIMESTAMP WHERE token = '$sess'";
        $res = pg_query($con, $sql);

        if (pg_affected_rows($res) == 1) {
			pg_query($con, 'COMMIT TRANSACTION');
			return true;
		}

		pg_query($con, 'ROLLBACK TRANSACTION');
		return false;
    }
}

if(!function_exists("get_ip")) {
	function get_ip() {
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
						return $ip;
					}
				}
			}
		}
	}
}
