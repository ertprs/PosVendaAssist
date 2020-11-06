<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_admin.php';

switch($_GET["tipo"]) {
	case "atualizar_imagem":
		$campo = $_GET["campo"];
		
		$sql = "
		UPDATE tbl_reportagem_foto SET
		{$_GET['campo']} = '{$_GET['valor']}'
		
		WHERE
		tbl_reportagem_foto.reportagem_foto={$_GET['reportagem_foto']}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) {
			echo "falha|" . pg_last_error($con);
			die;
		}
		else {
			$image = new SimpleImage();
			$image->load($_GET["valor"]);
			$image->resizeToHeight(60);
			$miniatura = explode(".", $_GET["valor"]);
			$miniatura[count($miniatura) -2] .= "_min";
			$miniatura = implode(".", $miniatura);
			$image->save($miniatura);
			echo "ok";
			die;
		}
	break;
	
	case "limpar_imagem":
		try {
			$campo = $_GET["campo"];
			
			$sql = "
			SELECT
			{$campo}
			
			FROM
			tbl_reportagem_foto
			
			WHERE
			reportagem_foto={$_GET['reportagem_foto']}
			";
			@$res = pg_query($con, $sql);
			
			if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao ler dados no banco de dados <erro msg=" + pg_last_error($con) + ">");
			
			$imagem = pg_fetch_result($res, 0, 0);
			
			$sql = "
			UPDATE tbl_reportagem_foto SET
			{$_GET['campo']} = '{$_GET['valor']}'
			
			WHERE
			tbl_reportagem_foto.reportagem_foto={$_GET['reportagem_foto']}
			";
			@$res = pg_query($con, $sql);
			
			if (strlen(pg_last_error($con)) > 0) throw new Exception("Falha ao atualizar banco de dados <erro msg=" + pg_last_error($con) + ">");
			
			if (file_exists($imagem)) unlink($imagem);
			echo "ok";
			die;
		}
		catch(Exception $e) {
			echo "falha|" . $e->getMessage();
			die;
		}
	break;
	
	case "adicionar_foto":
		$sql = "
		INSERT INTO tbl_reportagem_foto(
		reportagem
		)
		
		VALUES(
		{$reportagem}
		)
		
		RETURNING
		reportagem_foto
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) {
			echo "falha|" . pg_last_error($con);
			die;
		}
		else {
			$reportagem_foto = pg_fetch_result($res, 0, 0);
			echo "ok|{$reportagem_foto}";
			die;
		}
	break;
	
	case "ajax_upload":
		require_once("../js/valums_upload/server/php.php");
		// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array();
		// max file size in bytes
		$sizeLimit = 4 * 1024 * 1024;
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		$result = $uploader->handleUpload($_GET['file_path'], true, "{$_GET['id']}_{$_GET['file_suffix']}");
		// to pass data through iframe you will need to encode all html tags
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	break;
}
 
/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/
 
class SimpleImage {
 
   var $image;
   var $image_type;
 
   function load($filename) {
 
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
 
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
 
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
 
         $this->image = imagecreatefrompng($filename);
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image,$filename);
      }
      if( $permissions != null) {
 
         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image);
      }
   }
   function getWidth() {
 
      return imagesx($this->image);
   }
   function getHeight() {
 
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
 
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
 
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
 
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }
 
   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }      
 
}
?>