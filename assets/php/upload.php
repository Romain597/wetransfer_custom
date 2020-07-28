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
                    mysql_set('INSERT INTO data (id,type,name,dir,archive_id,user_id) VALUES (NULL,"'.$file["type"].'","'.basename($file["name"]).'","cache/'.basename($file["name"]).'",NULL,'.$_SESSION["user"].');');
                }
            }
        }
    }
    else if(!empty($btn_delete_files)) {
        $files = empty($_POST["files-list"]) ? [] : $_POST["files-list"] ;
        if(!empty($files)) {
            foreach($files as $file) {
                $test = false;
                $file_bis = explode('/',$file);
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
        $files = empty($_POST["files-list"]) ? [] : $_POST["files-list"] ;
        if(!empty($files)) {
            try {
                $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                $timestamp = $date->format("U");
                $zip = new ZipArchive();
                $zipname = 'WeTransferCustom_'.uniqid().'.zip';
                //$zipname = 'WeTransferCustom_'.$timestamp.'.zip';
                if ($zip->open($zipname, ZipArchive::CREATE)!==TRUE) {
                    exit("Impossible d'ouvrir l'archive <$zipname>\n");
                }
                foreach($files as $file) {
                    $file_bis = explode('/',$file);
                    $file_name = $file_bis[1];
                    $file_id = $file_bis[0];
                    $zip->addFile("../../cache/".$file_name);
                }
                $zip->close();
                $test = false;
                $text_from = empty($_POST["text-from"]) ? "" : $_POST["text-from"] ;
                $email_to = empty($_POST["email-to"]) ? "" : $_POST["email-to"] ;
                $subject = empty($_POST["subject"]) ? "" : $_POST["subject"] ;
                $description = empty($_POST["description"]) ? "" : $_POST["description"] ;
                if(file_exists("../../data/".$zipname)) {
                    $test = true;
                }
                if($test) {
                    mysql_set('INSERT INTO archive (id,name,dir,user_id,send,email_from,email_to,subject,description) VALUES (NULL,"'.$zipname.'","data/'.$zipname.'",'.$_SESSION["user"].',0,"'.$text_from.'","'.$email_to.'","'.$subject.'","'.$description.'");');
                    $result = mysql_set('SELECT token FROM user WHERE id='.$_SESSION["user"].';');
                    $token = $result['token'];
                    $contenu_html='Bonjour,<br><br><b>'.$text_from.'</b> vous a préparer une archive ZIP à téléchargé sur <a href="https://127.0.0.1/wetransfer_custom/">WeTransferCustom</a>.';
                    $contenu_html.='<br>Pour la télécharger, <a href="https://127.0.0.1/wetransfer_custom/download.php?name='.$zipname.'&user='.$token.'">cliquer ici</a>.<br>';
                    $contenu_html.='Pour la supprimer, <a href="https://127.0.0.1/wetransfer_custom/download.php?name='.$zipname.'&user='.$token.'&delete=1">cliquer ici</a>.';
                    $contenu_text='Bonjour,\n\n'.$text_from.' vous a préparer une archive ZIP à téléchargé sur WeTransferCustom.';
                    send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html="",$contenu_text="",[$email_to])
                }
            } catch (Exception $e) {
                // les erreurs sont traitées ici
            }
        }
    }
}
//var_dump($file_bis,$_POST,$files,is_dir("../../cache/"));
header("Location: ../../index.php");