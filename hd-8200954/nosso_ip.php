<?php

define(PATH_IP,'http://posvenda.telecontrol.com.br/assist/');

if (file_exists(PATH_IP . 'nosso_ip.txt')) {
	return file_get_contents(PATH_IP.'nosso_ip.txt');
}

