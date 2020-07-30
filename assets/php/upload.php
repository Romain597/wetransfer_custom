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
            $user = mysql_get('SELECT id FROM user WHERE token="'.$_SESSION["user"].'";');
            if(!empty($user)) {
                $user_id = $user[0]["id"];
                foreach($files_bis as $file) {
                    $test = false;
                    if(move_uploaded_file($file["tmp_name"],"../../cache/".basename($file["name"]))) {
                        $test = true;
                    }
                    if($test) {
                        mysql_set('INSERT INTO data (id,type,name,dir,archive_id,user_id) VALUES (NULL,"'.$file["type"].'","'.basename($file["name"]).'","cache/'.basename($file["name"]).'",NULL,'.$user_id.');');
                    }
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
        $text_from = empty($_POST["text-from"]) ? "" : $_POST["text-from"] ;
        $email_to = empty($_POST["email-to"]) ? "" : $_POST["email-to"] ;
        $subject = empty($_POST["subject"]) ? "" : $_POST["subject"] ;
        $description = empty($_POST["description"]) ? "" : $_POST["description"] ;
        $valid = valid_input_data($text_from,$email_to,$subject,$description);
        if($valid) {
            $files = mysql_get('SELECT d.* FROM data d INNER JOIN user u ON d.user_id=u.id WHERE token="'.$_SESSION["user"].'" ORDER BY id;');
            if(!empty($files)) {
                $user_id = $files[0]["user_id"];
                try {
                    $files_count = count($files);
                    $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                    $timestamp = $date->format("U");
                    $zip = new ZipArchive();
                    $zipname = 'WeTransferCustom'.uniqid("",true).'.zip';
                    //$zipname = 'WeTransferCustom_'.$timestamp.'.zip';
                    if ($zip->open('../../data/'.$zipname, ZipArchive::CREATE)!==TRUE) {
                        exit("Impossible d'ouvrir l'archive <$zipname>\n");
                    }
                    foreach($files as $file) {
                        $file_name = $file["name"];
                        if(file_exists("../../cache/".$file_name)) {
                            //$zip->addFile("../../cache/".$file_name);
                            $zip->addFromString(basename($file_name), file_get_contents("../../cache/".$file_name));
                        }
                        else {
                            $files_count--;
                        }
                    }
                    $zip->close();
                    $test = false;
                    if(file_exists("../../data/".$zipname)) {
                        $test = true;
                    }
                    if($test) {
                        $insert = mysql_set('INSERT INTO archive (id,name,dir,user_id,send,email_from,email_to,subject,description,send_date) VALUES (NULL,"'.$zipname.'","data/'.$zipname.'",'.$user_id.',0,"'.$text_from.'","'.$email_to.'","'.$subject.'","'.$description.'",'.$files_count.',NULL);');
                        //$select = mysql_get('SELECT token FROM user WHERE id='.$_SESSION["user"].';');
                        //$token = $select['token'];
                        $token = $_SESSION["user"];
                        $email_to_array = preg_split(',',$email_to,0,PREG_SPLIT_NO_EMPTY);
                        if(empty($text_from)) { $text_from='Quelqu\'un'; }
                        $contenu_html='<p>Bonjour,</p><br><br><p><b>'.$text_from.'</b> vous a préparer une archive ZIP contenant '.$files_count.' fichier(s) à téléchargé sur <a href="http://127.0.0.1/wetransfer_custom/">WeTransferCustom</a>';
                        $contenu_html.=' ayant pour objet <b>'.$subject.'</b>.</p><br><p><span style="color:red;">Votre archive sera accesible pendant 10 jours à partir de la date de cet email.</span></p><br><br>';
                        if(!empty($description)) { $contenu_html.='<p>Message de <b>'.$text_from.'</b> :</p><br><p><b>'.$description.'</b></p><br><br>'; }
                        $contenu_html.='<p style="text-align:center;">Pour télécharger l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'">cliquez ici</a>.</p><br><br>';
                        $contenu_html.='<p style="text-align:center;">Pour supprimer l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'&delete=1">cliquez ici</a>.</p>';
                        $contenu_text=''; //'Bonjour,\n\n'.$text_from.' vous a préparer une archive ZIP à téléchargé sur WeTransferCustom.';
                        $result_send_mail = send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html,$contenu_text,$email_to_array);
                        if(!empty($result_send_mail["val"])) {
                            $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                            $update = mysql_set('UPDATE archive SET send=1, send_date = "'.$date->format("Y-m-d H:i:s").'"  WHERE id = '.$insert.' ;');
                        }
                    }
                } catch (Exception $e) {
                    // les erreurs sont traitées ici
                }
            }
        }
    }
}
//var_dump($result_send_mail,$insert,$valid,$zipname,$_POST,$files,is_dir("../../cache/"));
header("Location: ../../index.php");