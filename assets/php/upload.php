<?php
session_start();
$func = require_once("functions.php");

$btn_validate_files = empty($_POST["validate-files"]) ? "" : $_POST["validate-files"] ;
$btn_delete_files = empty($_POST["delete-files"]) ? "" : $_POST["delete-files"] ;
$btn_add_files = empty($_POST["add-files"]) ? "" : $_POST["add-files"] ;

if($func) {
    if(!empty($btn_add_files)) {
        $files = empty($_FILES["input-files"]) ? [] : $_FILES["input-files"] ;
        if(!empty($files)) {
            $files_bis = convert_array_files($files);
            foreach($files_bis as $file) {
                $test = false;
                if(move_uploaded_file($file["tmp_name"],"../../cache/".basename($file["name"]))) {
                    $test = true;
                }
                if($test) {
                    mysql_set('INSERT INTO data (id,type,name,dir,archive_id,user_id) VALUES (NULL,"'.$file["type"].'","'.htmlentities(basename($file["name"])).'","cache/'.htmlentities(basename($file["name"])).'",NULL,'.$_SESSION["user"].');');
                }
            }
        }
    }
    else if(!empty($btn_delete_files)) {
        $files = empty($_POST["files-list"]) ? [] : $_POST["files-list"] ;
        if(!empty($files)) {
            foreach($files as $file) {
                $test = false;
                $file_bis = explode($file,'/');
                $file_name = $file_bis[1];
                $file_id = $file_bis[0];
                if(file_exists("../../cache/".$file_name)) {
                    $test = true;
                    unlink("../../cache/".$file_name);
                }
                if($test) {
                    mysql_set('DELETE FROM data WHERE id='.$file_id.');');
                }
            }
        }
    }
    else if(!empty($btn_validate_files)) {
        
    }
}
//var_dump($_POST,$files,is_dir("../../cache/"));
header("Location: ../../index.php");