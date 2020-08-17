<?php
session_start();
$func = require_once(__DIR__.'/'."functions.php");
if(empty($func)) {
    $included_files = get_included_files();
    $find = false;
    foreach ($included_files as $filename) {
        if(preg_match('',$filename)===1) {
            $find = true;
        }
    }
    if($find==true) { $func = 1; }
}

$btn_validate_files = empty($_POST["validate-files"]) ? "" : $_POST["validate-files"] ;
$btn_delete_files = empty($_POST["delete-files"]) ? "" : $_POST["delete-files"] ;
$btn_add_files = empty($_POST["add-files"]) ? "" : $_POST["add-files"] ;
$error = [];
$_SESSION['msg_array'] = [];

if($func) {
    if(!isset($_SESSION["user"])) {
        $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
        $token = preg_replace('#\.#','',uniqid("",true));
        mysql_set('INSERT INTO user (id,token,last_visit) VALUES (NULL,"'.$token.'","'.$date->format("Y-m-d H:i:s").'");');
        $_SESSION["user"] = $token;
    }
    if(!empty($_SESSION["user"])) {
        if(!empty($btn_add_files)) {
            $files = empty($_FILES["input-files"]) ? [] : $_FILES["input-files"] ;
            if(!empty($files)) {
                $files_bis = convert_array_files($files);
                $user = mysql_get('SELECT id FROM user WHERE token="'.$_SESSION["user"].'";');
                if(!empty($user)) {
                    $user_id = $user[0]["id"];
                    $archive_size = 0;
                    $dir = preg_replace('#\.#','',uniqid('wtc-data',true));
                    $data = mysql_get('SELECT SUM(size) as sum_total, dir FROM data WHERE user_id='.$user_id.';');
                    if(!empty($data)) {
                        if(!empty($data[0]["sum_total"])) { $archive_size = $data[0]["sum_total"]; }
                        if(!empty($data[0]["dir"])) { $dir = $data[0]["dir"]; }
                    }
                    if(file_exists(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$dir)==false) { mkdir('../../'.FOLDER_DATA.'/'.$dir); }
                    $test = false;
                    foreach($files_bis as $file) {
                        $test = false;
                        if($file['error']==UPLOAD_ERR_OK) {
                            $size = $file['size'];
                            $archive_size += $size;
                            if($archive_size<=MAX_ARCHIVE_SIZE) {
                                if($size<=MAX_FILE_SIZE) {
                                    if(move_uploaded_file($file["tmp_name"],'../../'.FOLDER_DATA.'/'.$dir.'/'.basename($file["name"]))) {
                                        $test = true;
                                    }
                                    if($test) {
                                        mysql_set('INSERT INTO data (id,type,name,dir,size,user_id) VALUES (NULL,"'.$file["type"].'","'.basename($file["name"]).'","'.$dir.'",'.$size.','.$user_id.');');
                                    }
                                }
                                else {
                                    $error[] = $file;
                                    $error[count($error)-1]["msg"] = 'Taille maximun du fichier atteinte.';
                                }
                            }
                            else {
                                $error[] = $file;
                                $error[count($error)-1]["msg"] = 'Taille maximun de l\'archive atteinte.';
                            }
                        }
                        else {
                            $error[] = $file;
                            $error[count($error)-1]["msg"] = get_file_error($file['error']);
                        }
                    }
                }
            }
        }
        else if(!empty($btn_delete_files)) {
            $files = empty($_POST["files-list"]) ? [] : $_POST["files-list"] ;
            if(!empty($files)) {
                $test = false;
                foreach($files as $file) {
                    $test = false;
                    $file_bis = preg_split('#\}#',$file,0,PREG_SPLIT_NO_EMPTY);
                    $file_name = $file_bis[1];
                    $file_id = $file_bis[0];
                    if(file_exists(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_name)) {
                        $test = true;
                        unlink(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_name);
                    }
                    if($test) {
                        mysql_set('DELETE FROM data WHERE id='.$file_id.';');
                    }
                }
            }
        }
        else if(!empty($btn_validate_files)) {
            $text_from = empty($_POST["text-from"]) ? "" : trim($_POST["text-from"]) ;
            $email_to = empty($_POST["email-to"]) ? "" : trim($_POST["email-to"]) ;
            $email_from = empty($_POST["email-from"]) ? "" : trim($_POST["email-from"]) ;
            $subject = empty($_POST["subject"]) ? "" : trim($_POST["subject"]) ;
            $description = empty($_POST["description"]) ? "" : trim($_POST["description"]) ;
            $valid = valid_input_data($text_from,$email_to,$email_from,$subject,$description);
            $error_msg = "";
            if($valid["val"]) {
                $files = mysql_get('SELECT d.* FROM data d INNER JOIN user u ON d.user_id=u.id WHERE token="'.$_SESSION["user"].'" ORDER BY id;');
                if(!empty($files)) {
                    $user_id = $files[0]["user_id"];
                    $archives = mysql_get('SELECT * FROM archive WHERE user_id='.$user_id.' ORDER BY id;');
                    try {
                        $files_count = count($files);
                        $archive_dir = preg_replace('#\.#','',uniqid('wtc-archive',true));
                        if(!empty($archives)) {
                            if(!empty($archives[0]["dir"])) { $archive_dir = $archives[0]["dir"]; }
                        }
                        if(file_exists(__DIR__.'/'.'../../'.FOLDER_ARCHIVE.'/'.$archive_dir)==false) { mkdir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir); }
                        $zip = new ZipArchive();
                        $zipname = 'WeTransferCustom'.preg_replace('#\.#','',uniqid("",true)).'.zip';
                        $archive_open = true;
                        if ($zip->open('../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$zipname, ZipArchive::CREATE)!==TRUE) {
                            $archive_open = false;
                        }
                        if($archive_open) {
                            $file_dir = $files[0]["dir"];
                            foreach($files as $file) {
                                $file_name = $file["name"];
                                if(file_exists(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name)) {
                                    $zip->addFromString(basename($file_name), file_get_contents('../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name));
                                }
                                else {
                                    $files_count--;
                                }
                            }
                            $zip->close();
                            $test = false;
                            if(file_exists(__DIR__.'/'.'../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$zipname)) {
                                $test = true;
                                foreach($files as $file) {
                                    $file_name = $file["name"];
                                    if(file_exists(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name)) {
                                        unlink(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name);
                                    }
                                }
                                $sup_f = rmdir(__DIR__.'/'.'../../'.FOLDER_DATA.'/'.$file_dir);
                                mysql_set('DELETE FROM data WHERE user_id='.$user_id.';');
                            }
                            if($test) {
                                $insert = mysql_set('INSERT INTO archive (id,name,dir,user_id,send_to,send_from,name_from,email_from,email_to,subject,description,nb_files,send_to_date,send_from_date) VALUES (NULL,"'.$zipname.'","'.$archive_dir.'",'.$user_id.',0,0,"'.$text_from.'","'.$email_from.'","'.$email_to.'","'.$subject.'","'.$description.'",'.$files_count.',NULL,NULL);');
                                $token = $_SESSION["user"];
                                // destinataires
                                $email_to_array = preg_split('#\s*[\,\;]\s*#',$email_to,0,PREG_SPLIT_NO_EMPTY);
                                $contenu_html=get_email_to_body($zipname,$token,$files_count,$text_from,$subject,$description);
                                $result_send_to_mail = send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html,$email_to_array);
                                if(!empty($result_send_to_mail["val"])) {
                                    $dateA = new DateTime("now",new DateTimeZone("Europe/Paris"));
                                    $updateA = mysql_set('UPDATE archive SET send_to=1, send_to_date = "'.$dateA->format("Y-m-d H:i:s").'"  WHERE id = '.$insert.' ;');
                                    if(count($email_to_array)>1) {
                                        $_SESSION['msg_array']["succes"][] = 'Emails destinataires envoyés.';
                                    }
                                    else {
                                        $_SESSION['msg_array']["succes"][] = 'Email destinataire envoyé.';
                                    }
                                }
                                else {
                                    if(count($email_to_array)>1) {
                                        $error_msg = 'Emails destinataires non envoyés.<br>Veuillez vérifier la saisie des adresses emails !';
                                    }
                                    else {
                                        $error_msg = 'Email destinataire non envoyé.<br>Veuillez vérifier la saisie de l\'adresse email !';
                                    }
                                }
                                // expéditeur
                                $email_from_array = preg_split('#\s*[\,\;]\s*#',$email_from,0,PREG_SPLIT_NO_EMPTY);
                                $contenu_html=get_email_from_body($zipname,$token,$files_count,$text_from,$email_from,$subject,$description);
                                $result_send_from_mail = send_mail("WeTransferCustom - Information",$contenu_html,$email_from_array);
                                if(!empty($result_send_from_mail["val"])) {
                                    $dateB = new DateTime("now",new DateTimeZone("Europe/Paris"));
                                    $updateB = mysql_set('UPDATE archive SET send_from=1, send_from_date = "'.$dateB->format("Y-m-d H:i:s").'"  WHERE id = '.$insert.' ;');
                                    if(count($email_from_array)>1) {
                                        $_SESSION['msg_array']["succes"][] = 'Emails expéditeurs envoyés.';
                                    }
                                    else {
                                        $_SESSION['msg_array']["succes"][] = 'Email expéditeur envoyé.';
                                    }
                                }
                                else {
                                    if(count($email_from_array)>1) {
                                        $error_msg = 'Emails expéditeurs non envoyés.<br>Veuillez vérifier la saisie des adresses emails !';
                                    }
                                    else {
                                        $error_msg = 'Email expéditeur non envoyé.<br>Veuillez vérifier la saisie de l\'adresse email !';
                                    }
                                }
                            }
                        }
                        else {
                            $error_msg = 'Archive non créer.';
                        }
                    } catch (Exception $e) {
                        $error_msg = 'Nous nous exusons mais nous avons rencontré une erreur de traitement.';
                    }
                }
                else {
                    $error_msg = 'Aucun fichier(s) à envoyer !';
                }
            }
            else {
                $error_msg = '';
                foreach($valid as $key => $value) {
                    if($key!="val") {
                        if($value==false) {
                            switch($key) {
                                case 'email_to_test':
                                    $error_msg .= 'Adresse(s) email(s) destinataire(s) non valide(s) !';
                                break;
                                case 'email_from_test':
                                    $error_msg .= 'Adresse email expéditeur non valide !';
                                break;
                            }
                        }   
                    }
                }
                if(empty($error_msg)) { $error_msg = 'Adresse(s) email(s) non valide(s) !'; }
            }
            $error[] = ['msg'=>$error_msg];
        }
    }
    else {
        $error[] = ['msg'=>'Nous nous exusons mais vous n\'avez pas été détecté. Actualisez la page pour corriger cela.'];
    }
}
else {
    $error[] = ['msg'=>'Nous nous exusons mais notre service est momentanément indisponible.'];
}
$_SESSION['msg_array']["error"] = $error;
header("Location: ../../index.php");