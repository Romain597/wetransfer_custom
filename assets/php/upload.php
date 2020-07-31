<?php
session_start();
$func = require_once("functions.php");
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
        //var_dump($date->format("Y-m-d H:i:s"));
        $token = uniqid("",true);
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
                    if(file_exists('../../'.FOLDER_DATA.'/'.$dir)==false) { mkdir('../../'.FOLDER_DATA.'/'.$dir); }
                    //$error = array();
                    //var_dump($dir,preg_replace('#\.#','',uniqid('wtc-data',true)),$data);
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
                    //$file_bis = explode('/',$file);
                    $file_bis = preg_split('#\}#',$file,0,PREG_SPLIT_NO_EMPTY);
                    $file_name = $file_bis[1];
                    $file_id = $file_bis[0];
                    //var_dump($file_bis);
                    if(file_exists('../../'.FOLDER_DATA.'/'.$file_name)) {
                        $test = true;
                        unlink('../../'.FOLDER_DATA.'/'.$file_name);
                    }
                    if($test) {
                        //var_dump($test,'DELETE FROM data WHERE id='.$file_id.';');
                        mysql_set('DELETE FROM data WHERE id='.$file_id.';');
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
            $error_msg = "";
            if($valid["val"]) {
                $files = mysql_get('SELECT d.* FROM data d INNER JOIN user u ON d.user_id=u.id WHERE token="'.$_SESSION["user"].'" ORDER BY id;');
                if(!empty($files)) {
                    $user_id = $files[0]["user_id"];
                    $archives = mysql_get('SELECT * FROM archive WHERE user_id='.$user_id.' ORDER BY id;');
                    try {
                        $files_count = count($files);
                        //$date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                        //$timestamp = $date->format("U");
                        $archive_dir = preg_replace('#\.#','',uniqid('wtc-archive',true));
                        if(!empty($archives)) {
                            if(!empty($archives[0]["dir"])) { $archive_dir = $archives[0]["dir"]; }
                        }
                        if(file_exists('../../'.FOLDER_ARCHIVE.'/'.$archive_dir)==false) { mkdir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir); }
                        $zip = new ZipArchive();
                        $zipname = 'WeTransferCustom'.uniqid("",true).'.zip';
                        //$zipname = 'WeTransferCustom_'.$timestamp.'.zip';
                        $archive_open = true;
                        if ($zip->open('../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$zipname, ZipArchive::CREATE)!==TRUE) {
                            //exit("Impossible d'ouvrir l'archive <$zipname>\n");
                            $archive_open = false;
                        }
                        if($archive_open) {
                            $file_dir = $files[0]["dir"];
                            foreach($files as $file) {
                                $file_name = $file["name"];
                                //$file_dir = $file["dir"];
                                if(file_exists('../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name)) {
                                    //$zip->addFile('../../'.FOLDER_DATA.'/'.$file_name);
                                    $zip->addFromString(basename($file_name), file_get_contents('../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name));
                                }
                                else {
                                    $files_count--;
                                }
                            }
                            $zip->close();
                            $test = false;
                            if(file_exists('../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$zipname)) {
                                $test = true;
                                //delete_data_files($file_dir,'all',1);
                                foreach($files as $file) {
                                    $file_name = $file["name"];
                                    //$file_dir = $file["dir"];
                                    if(file_exists('../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name)) {
                                        unlink('../../'.FOLDER_DATA.'/'.$file_dir.'/'.$file_name);
                                    }
                                }
                                $sup_f = rmdir('../../'.FOLDER_DATA.'/'.$file_dir);
                                //var_dump($sup_f);
                                mysql_set('DELETE FROM data WHERE user_id='.$user_id.';');
                            }
                            if($test) {
                                $insert = mysql_set('INSERT INTO archive (id,name,dir,user_id,send,email_from,email_to,subject,description,nb_files,send_date) VALUES (NULL,"'.$zipname.'","'.$archive_dir.'",'.$user_id.',0,"'.$text_from.'","'.$email_to.'","'.$subject.'","'.$description.'",'.$files_count.',NULL);');
                                //$select = mysql_get('SELECT token FROM user WHERE id='.$_SESSION["user"].';');
                                //$token = $select['token'];
                                $token = $_SESSION["user"];
                                $email_to_array = preg_split('#\s*[\,\;]\s*#',$email_to,0,PREG_SPLIT_NO_EMPTY);
                                //if(empty($text_from)) { $text_from='Quelqu\'un'; }
                                /*$contenu_html='<p>Bonjour,</p><br><br><p><b>'.$text_from.'</b> vous a préparer une archive ZIP contenant '.$files_count.' fichier(s) à téléchargé sur <a href="http://127.0.0.1/wetransfer_custom/">WeTransferCustom</a>';
                                $contenu_html.=' ayant pour objet <b>'.$subject.'</b>.</p><br><p><span style="color:red;">Votre archive sera accesible pendant 10 jours à partir de la date de cet email.</span></p><br><br>';
                                if(!empty($description)) { $contenu_html.='<p>Message de <b>'.$text_from.'</b> :</p><br><p><b>'.$description.'</b></p><br><br>'; }
                                $contenu_html.='<p style="text-align:center;">Pour télécharger l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'">cliquez ici</a>.</p><br><br>';
                                $contenu_html.='<p style="text-align:center;">Pour supprimer l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'&delete=1">cliquez ici</a>.</p>';*/
                                $contenu_html=get_email_body($zipname,$token,$files_count,$text_from,$subject,$description);
                                //$contenu_text=''; //'Bonjour,\n\n'.$text_from.' vous a préparer une archive ZIP à téléchargé sur WeTransferCustom.';
                                $result_send_mail = send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html,$email_to_array); //,$contenu_text
                                if(!empty($result_send_mail["val"])) {
                                    $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                                    $update = mysql_set('UPDATE archive SET send=1, send_date = "'.$date->format("Y-m-d H:i:s").'"  WHERE id = '.$insert.' ;');
                                    if(count($email_to_array)>1) {
                                        $_SESSION['msg_array']["succes"] = 'Emails envoyer.<br>Il reste 10 jours à vos destinataires pour récupérer l\'archive.';
                                    }
                                    else {
                                        $_SESSION['msg_array']["succes"] = 'Email envoyer.<br>Il reste 10 jours à votre destinataire pour récupérer l\'archive.';
                                    }
                                }
                                else {
                                    if(count($email_to_array)>1) {
                                        $error_msg = 'Emails non envoyer. Veuillez vérifier la saisie des adresses emails !';
                                    }
                                    else {
                                        $error_msg = 'Email non envoyer. Veuillez vérifier la saisie de l\'adresse email !';
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
                $error_msg = 'Adresse(s) email(s) non valide(s) !';
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
//var_dump($files,$user_id);//var_dump($dir,$user,$data,$files,$files_bis);//var_dump($dir,$files,$_FILES,$files_bis,preg_replace('#\.#','','truc.555'),preg_replace('#\.#','',uniqid('wtc-data',true))); //var_dump($result_send_mail,$insert,$valid,$zipname,$_POST,$files,is_dir('../../'.FOLDER_DATA.'/'));
header("Location: ../../index.php");