<?php

namespace util;

class RequestHelper {

	public static function prepareGetParameters(){
		return NameHelper::prepareArray($_GET);
	}

	public static function preparePostParameters(){
		return NameHelper::prepareArray($_POST);
	}

	public static function prepareRequestParameters(){
		return NameHelper::prepareArray($_REQUEST);	
	}
}
