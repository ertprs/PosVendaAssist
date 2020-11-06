<?php

file_put_contents("/tmp/log.log", "RECEIVER POST"."\n",FILE_APPEND);
file_put_contents("/tmp/log.log", print_r($_POST,1)."\n",FILE_APPEND);
file_put_contents("/tmp/log.log", "RECEIVER FILE"."\n",FILE_APPEND);
file_put_contents("/tmp/log.log", print_r($_FILES,1)."\n",FILE_APPEND);

echo "FINISH";