<?php
	include_once("../../../wp-config.php");
	include_once("../../../wp-load.php");
	include_once("../../../wp-includes/wp-db.php");
	global $wpdb;
	$refer = $_SERVER['HTTP_REFERER'];
	if(!isset($_POST['allowPHPNonce'])){
		if ( !wp_verify_nonce( $_POST['allowPHPNonce'], plugin_basename(__FILE__) )) {header("location:".$refer);}
	}
	else{
		if(!isset($_POST['action']) || !defined ('ABSPATH')){header("location:".$refer);}
		if(isset($_POST['id'])){$id = $_POST['id'];}else{$id='0';}
		if(isset($_POST['function'])){$function = $_POST['function'];}else{$function="";}
		if(isset($_POST['name'])){$name = $_POST['name'];}else{$name="";}
		$action = $_POST['action'];
		
		#delete
		if($action == "delete"){
			$sql = "delete from ".$wpdb->prefix."allowPHP_functions WHERE id='".$id."'";
			$wpdb->query($wpdb->prepare($sql));		
			header("location:".$refer);
		}
		#add
		elseif($action == "add" && $function != ""){
			$sql = "insert into ".$wpdb->prefix."allowPHP_functions (function,name) values('".$function."','".$name."')";
			$results = $wpdb->get_results($wpdb->prepare($sql));
			header("location:".$refer);
		}
		#modify
		elseif($action == "modify" && $function != ""){
			$sql = "update ".$wpdb->prefix."allowPHP_functions set function='".$function."', name='".$name."' where id = ".$id;
			$results = $wpdb->get_results($wpdb->prepare($sql));
			header("location:".$refer);
		}
		elseif($action == "options" && isset($_POST['option_404msg'])){
			if(isset($_POST["option_show404"])){$show404 = $_POST['option_show404'];}else{$show404 = 0;}
			if(isset($_POST["option_404msg"])){$fourohfourmsg = $_POST['option_404msg'];}else{$show404 = 0;}
			$options = get_option("allowPHP_options");
			$options = unserialize($options);
			$options['show404'] = $show404;
			$options['fourohfourmsg'] = $fourohfourmsg;
			update_option("allowPHP_options", $options);
		}
	}
	header("location:".$refer."&noaction");
?>