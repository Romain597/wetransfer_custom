<?php

// constantes
define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);
define('MAX_FILE_MULTIPLICATOR',20);
define('MAX_ARCHIVE_MULTIPLICATOR',200);
define('MAX_FILE_SIZE',MB*MAX_FILE_MULTIPLICATOR);
define('MAX_ARCHIVE_SIZE',MB*MAX_ARCHIVE_MULTIPLICATOR);
define('FOLDER_ARCHIVE','archive');
define('FOLDER_DATA','data');

// fonctions

function mysql_connec() {
    $host="localhost";
    $user="root";
    $pass="";
    $db="wetransfer_custom";

    $link = mysqli_connect($host,$user,$pass,$db);

    if(mysqli_connect_errno()) {
        printf("Échec de la connexion : %s\n", mysqli_connect_error());
        exit();
    }
    return $link;
}

function mysql_close($link) {
    mysqli_close($link);
}

function mysql_get($sql) {
    $link = mysql_connec();
    $return_data = array();
    if($link) {
        if($sql!="") {
            mysqli_query($link,'SET NAMES "utf8"');
            if( $result = mysqli_query($link,$sql) ) {
                while($row= mysqli_fetch_assoc($result)) {
                    $return_data[] = $row;
                } 
                mysqli_free_result($result);       
            }
        }
    }
    mysql_close($link);
    return $return_data;  
}

function mysql_set($sql) {
    $id;
    $link = mysql_connec();
    $return_data = array();
    if($link) {
        if($sql!="") {
            mysqli_query($link,'SET NAMES "utf8"');
            mysqli_query($link,$sql);
            $id= mysqli_insert_id($link);
        }
    }
    mysql_close($link);
    return $id;
}

function convert_array_files($file) {
    $result = array();
    $line= array("name"=>"","tmp_name"=>"","type"=>"","error"=>"","size"=>"");
    if(is_array($file["name"])) {
        $j=0;
        foreach($file as $key => $values) {
            $n = count($values);
            $i = 0;
            for($i=0;$i<$n;$i++) {
                if($j==0) { $result[]=$line; }
                $result[$i][$key] = $values[$i];
            }
            $j++;
        }
    } else {
        $result[] = $file;
    }
    return $result;
}

function valid_input_data($text_from,$email_to,$subject,$description) {
    $result = array("val"=>true,"email_test"=>true,"from_test"=>true,"subject_test"=>true,"description_test"=>true);
    if(preg_match('#^[^\s]+\@[^\s]+$#',trim($email_to))!==1) {
        $result["val"] = false;
        $result["email_test"] = false;
    }
    /*if(preg_match('//',$text_from)!==1) {
        $result["val"] = false;
        $result["from_test"] = false;
    }
    if(preg_match('//',$subject)!==1) {
        $result["val"] = false;
        $result["subject_test"] = false;
    }
    if(preg_match('//',$description)!==1) {
        $result["val"] = false;
        $result["description_test"] = false;
    }*/
    return $result;
}

function send_mail($objet="",$contenu_html="",$destinataire=[]) { //$contenu_text="",

    $return_value = array("val" => 0,"msg" =>'The file "vendor/autoload.php" is missing or corrupt.');
    
    if(file_exists('../../vendor/autoload.php')) {
        $return_value = array("val" => 0,"msg" =>'Error in function "send_mail".');
        
        require_once('../../vendor/autoload.php');

        // Create the Transport (mailtrap)
        $transport = (new Swift_SmtpTransport('smtp.mailtrap.io',2525))
            ->setUsername('16d23dc5057427')
            ->setPassword('c3904e2ddcdd93');
        
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);

        // charset
        //Swift_Preferences::getInstance()->setCharset('utf-8'); //iso-8859-2

        // Create a message
        $message = (new Swift_Message($objet))
            ->setFrom(['swiftmailer@wetransfercustom.fr'])
            ->setTo($destinataire)
            ->setBody($contenu_html, 'text/html');
            //->addPart($contenu_text, 'text/plain');

        // Send the message
        $result = $mailer->send($message);

        if(!empty($result)) {
            $return_value = array("val" => $result,"msg" =>'Email envoyer.');
        }

    }
    return $return_value;
}

function clean_bdd($token="",$archive="") {
    if(!empty($token)) {
        if(!empty($archive)) {
            $result=mysql_get('SELECT a.* FROM archive a INNER JOIN user u ON u.id=a.user_id WHERE u.token="'.$token.'" AND a.name = "'.$archive.'" LIMIT 1 ;');
            if(!empty($result)) {
                $user_id = $result[0]["user_id"];
                if(!empty($user_id)) {
                    mysql_set('DELETE FROM archive WHERE user_id = '.$user_id.' AND name = "'.$archive.'" ;');
                }
                delete_archive_files('no','no',$result[0]["dir"],FOLDER_ARCHIVE.'/'.$result[0]["dir"].'/'.$archive);
            }
        }
        else {
            $result=mysql_get('SELECT a.* FROM archive a INNER JOIN user u ON u.id=a.user_id WHERE u.token="'.$token.'" LIMIT 1 ;');
            if(!empty($result)) {
                $user_id = $result[0]["user_id"];
                if(!empty($user_id)) {
                    mysql_set('DELETE FROM user WHERE id = '.$user_id.' ;');
                    mysql_set('DELETE FROM archive WHERE user_id = '.$user_id.' ;');
                    mysql_set('DELETE FROM data WHERE user_id = '.$user_id.' ;');
                }
                delete_archive_files('no','no',$result[0]["dir"],'all');
                $files = mysql_get('SELECT d.* FROM data d WHERE user_id='.$user_id.' ;');
                if(!empty($files)) {
                    foreach($files as $data) {
                        delete_data_files($data["dir"],'all',1);
                    }
                }
            }
        }
    }
    else {
        $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
        $result=mysql_get('SELECT a.* FROM archive WHERE "'.$date->format("Y-m-d").'" NOT BETWEEN DATE_FORMAT(send_date,"%Y-%m-%d") AND DATE_ADD(DATE_FORMAT(send_date,"%Y-%m-%d"), INTERVAL 10 DAY);');
        if(!empty($result)) {
            $user_id_inline = "";
            foreach($result as $line) {
                $user_id_inline .= $line["user_id"].",";
                delete_archive_files('no','no',$line["dir"],FOLDER_ARCHIVE.'/'.$line["dir"].'/'.$line["name"]);
                $files = mysql_get('SELECT d.* FROM data d WHERE user_id='.$line["user_id"].' ;');
                if(!empty($files)) {
                    foreach($files as $data) {
                        delete_data_files($data["dir"],'all',1);
                    }
                }
            }
            $user_id_inline = preg_replace('#\,$#','',$user_id_inline);
            if(!empty($user_id_inline)) {
                mysql_set('DELETE FROM user WHERE id IN ('.$user_id_inline.');');
                mysql_set('DELETE FROM archive WHERE user_id IN ('.$user_id_inline.');');
                mysql_set('DELETE FROM data WHERE user_id IN ('.$user_id_inline.');');
            }
        }
    }
}

function get_email_body($zipname,$token,$nb_files,$text_from,$subject,$description) {
    if(empty($text_from)) { $text_from='Quelqu\'un'; }
    if(empty($subject)) { $text_subject='n\'ayant pas d\'objet'; } else $text_subject='ayant pour objet <b>'.$subject.'</b>';
    $contenu_html='<p style="text-align:center;cursor:default;margin-top:20px;">Bonjour,</p>';
    $contenu_html.='<p style="text-align:center;cursor:default;"><b>'.$text_from.'</b> vous a préparer une archive ZIP à téléchargé contenant '.$nb_files.' fichier(s) sur <a href="http://localhost/WeTransferCustom/">WeTransferCustom</a>';
    $contenu_html.=' '.$text_subject.'.</p><p style="text-align:center;cursor:default;color:red;">Votre archive sera accesible pendant 10 jours à partir de cette date.</p>';
    if(!empty($description)) { $contenu_html.='<p style="text-align:center;cursor:default;">Message de <b>'.$text_from.'</b> :</p><p style="text-align:center;cursor:default;"><b>'.$description.'</b></p>'; }
    $contenu_html.='<p style="text-align:center;cursor:default;;margin-top:50px;">Pour télécharger l\'archive, <a href="http://localhost/WeTransferCustom/download.php?archive='.$zipname.'&access='.$token.'">cliquez ici</a>.</p>';
    $contenu_html.='<p style="text-align:center;cursor:default;">Pour supprimer l\'archive, <a href="http://localhost/WeTransferCustom/download.php?archive='.$zipname.'&access='.$token.'&delete=1">cliquez ici</a>.</p>';
    $contenu_html.='<p style="text-align:center;cursor:default;margin-top:40px;">Copyright WeTransferCustom 2020</p>';
    return $contenu_html;
}

function check_if_send() {
    $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
    $result=mysql_get('SELECT d.*, u.token FROM archive a INNER JOIN user u WHERE send=0 OR send_date IS NULL;');
    if(!empty($result)) {
        $test = false;
        foreach($result as $archive) {
            $test = false;
            $archive_dir = $archive["dir"];
            $zipname = $archive["name"];
            if(file_exists('../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$zipname)) {
                $test = true;
            }
            if($test) {
                $token = $archive["token"];
                $email_to_array = preg_split('#\,#',$archive["email_to"],0,PREG_SPLIT_NO_EMPTY);
                $text_from = $archive["email_from"];
                $subject = $archive["subject"];
                $description = $archive["description"];
                $nb_files = $archive["nb_files"];
                //if(empty($text_from)) { $text_from='Quelqu\'un'; }
                /*$contenu_html='<p>Bonjour,<br><br><b>'.$text_from.'</b> vous a préparer une archive ZIP contenant '.$nb_files.' fichier(s) à téléchargé sur <a href="http://127.0.0.1/wetransfer_custom/">WeTransferCustom</a>';
                $contenu_html.=' ayant pour objet <b>'.$subject.'</b>.</p><br><p><span style="color:red;">Votre archive sera accesible pendant 10 jours à partir de la date de cet email.</span></p><br><br>';
                if(!empty($description)) { $contenu_html.='<p>Message de <b>'.$text_from.'</b> :</p><br><p><b>'.$description.'</b></p><br><br>'; }
                $contenu_html.='<p style="text-align:center;">Pour télécharger l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'">cliquez ici</a>.</p><br><br>';
                $contenu_html.='<p style="text-align:center;">Pour supprimer l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'&delete=1">cliquez ici</a>.</p>';*/
                $contenu_html=get_email_body($zipname,$token,$nb_files,$text_from,$subject,$description);
                //$contenu_text=''; //'Bonjour,\n\n'.$text_from.' vous a préparer une archive ZIP à téléchargé sur WeTransferCustom.';
                $result_send_mail = send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html,$email_to_array); //,$contenu_text
                if(!empty($result_send_mail["val"])) {
                    $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                    $update = mysql_set('UPDATE archive SET send=1, send_date = "'.$date->format("Y-m-d H:i:s").'"  WHERE id = '.$archive["id"].' ;');
                }
            }
        }
    }
}

function get_file_error($code) {
    $msg = "";
    switch($code) {
        case UPLOAD_ERR_INI_SIZE :
            $msg = 'La taille du fichier chargé excède la valeur autorisé par le serveur.';
        break;
        case UPLOAD_ERR_FORM_SIZE :
            $msg = 'La taille du fichier chargé excède la valeur qui a été spécifiée dans le formulaire HTML.';
        break;
        case UPLOAD_ERR_PARTIAL :
            $msg = 'Le fichier n\'a été que partiellement chargé.';
        break;
        case UPLOAD_ERR_NO_FILE :
            $msg = 'Aucun fichier n\'a été chargé.';
        break;
        case UPLOAD_ERR_NO_TMP_DIR :
            $msg = 'Un dossier temporaire est manquant.';
        break;
        case UPLOAD_ERR_CANT_WRITE :
            $msg = 'Échec de l\'écriture du fichier sur le disque.';
        break;
        case UPLOAD_ERR_EXTENSION :
            $msg = 'Une extension PHP a arrêté l\'envoi de fichier.';
        break;
    }
    return $msg;
}

function delete_data_files($data_dir,$data_path,$delete_folder=1) {
    if(empty($delete_folder)) { $delete_folder = 0; } else $delete_folder = 1;
    if(!empty($data_dir) && !empty($data_path)) {
        if(file_exists('../../'.FOLDER_DATA.'/'.$data_dir)) {
            if($data_path=="all") {
                if(sizeof(scandir('../../'.FOLDER_DATA.'/'.$data_dir))>2) {
                    foreach(scandir('../../'.FOLDER_DATA.'/'.$data_dir) as $file) {
                        if($file!="." && $file!="..") { unlink('../../'.FOLDER_DATA.'/'.$data_dir.'/'.$file); }
                    }
                }
                if($delete_folder==1) {
                    $sup_f = rmdir('../../'.FOLDER_DATA.'/'.$data_dir);
                    //var_dump($sup_f);
                }
            }
            else {
                if(file_exists($data_path)) { unlink($data_path); }
                if($delete_folder==1 && sizeof(scandir('../../'.FOLDER_DATA.'/'.$data_dir))==2) {
                    $sup_f = rmdir('../../'.FOLDER_DATA.'/'.$data_dir);
                    //var_dump($sup_f);
                }
            }
        }
    }
}

function delete_archive_files($token,$name,$archive_dir,$archive_path) {
    $bdd = true;
    if(empty($token) || empty($name) || $token=='no' || $name=='no') {
        $bdd = false;
    }
    if(!empty($archive_dir) && !empty($archive_path)) {
        if(file_exists('../../'.FOLDER_ARCHIVE.'/'.$archive_dir)) {
            if(sizeof(scandir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir))==2 && $archive_path!="all") {
                if(file_exists($archive_path)) { unlink($archive_path); }
                $sup_f = rmdir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir);
                //var_dump($sup_f);
                if($bdd) { clean_bdd($token); }
            }
            else if($archive_path=="all") {
                foreach(scandir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir) as $file) {
                    if($file!="." && $file!="..") { unlink('../../'.FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$file); }
                }
                $sup_f = rmdir('../../'.FOLDER_ARCHIVE.'/'.$archive_dir);
                //var_dump($sup_f);
                if($bdd) { clean_bdd($token); }
            }
            else {
                if(file_exists($archive_path)) { unlink($archive_path); }
                if($bdd) { clean_bdd($token,$name); }
            }
        }
    }
}