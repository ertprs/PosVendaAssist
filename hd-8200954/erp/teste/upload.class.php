<?php
// Programmed by Reza Salehi - zaalion@yahoo.com, free for non-commercial use. Nov. 2005
class uploader
{
	var $maxFileSize;
	var $uploadDir;
	//
	function uploader($max, $dir)
	{
		$this->maxFileSize=$max;
		$this->uploadDir=$dir; 
	}
	function upload($object)
	{
		$file_size=$_FILES[$object]['size'];
		$file_name=$_FILES[$object]['name'];
		
		if($file_size <= $this->maxFileSize && strlen($file_name)>0)
		{		
			move_uploaded_file($_FILES[$object]['tmp_name'], $this->uploadDir.$file_name) or $file_name='NOFILE.jpg';
			return($file_name);			
		}
		else
		{
			return('NOFILE.jpg');
		}			
	}
}

?>