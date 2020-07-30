<?php

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
    $result = true;
    if(empty($email_to)) {
        $result = false;
    }
    /*if() {
        $result = false;
    }
    if() {
        $result = false;
    }*/
    return $result;
}

function send_mail($objet="",$contenu_html="",$contenu_text="",$destinataire=[]) {

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
        Swift_Preferences::getInstance()->setCharset('iso-8859-2');

        // Create a message
        $message = (new Swift_Message($objet))
            ->setFrom(['swiftmailer@wetransfercustom.fr'])
            ->setTo($destinataire)
            ->setBody($contenu_html, 'text/html')
            ->addPart($contenu_text, 'text/plain');

        // Send the message
        $result = $mailer->send($message);

        if(!empty($result)) {
            $return_value = array("val" => $result,"msg" =>'Email envoyer.');
        }

    }
    return $return_value;
}

function clean_bdd($token="") {
    if(!empty($token)) {
        $result=mysql_get('SELECT id FROM user WHERE token="'.trim($token).'";');
        if(!empty($result)) {
            $user_id = $result[0]["id"];
            if(!empty($user_id_inline)) {
                mysql_set('DELETE FROM user WHERE id = '.$user_id.' ;');
                mysql_set('DELETE FROM archive WHERE user_id = '.$user_id.' ;');
                mysql_set('DELETE FROM data WHERE user_id = '.$user_id.' ;');
            }
        }
    }
    else {
        $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
        $result=mysql_get('SELECT user_id FROM archive WHERE "'.$date->format("Y-m-d").'" NOT BETWEEN DATE_FORMAT(send_date,"%Y-%m-%d") AND DATE_ADD(DATE_FORMAT(send_date,"%Y-%m-%d"), INTERVAL 10 DAY);');
        if(!empty($result)) {
            $user_id_inline = "";
            foreach($result as $line) {
                $user_id_inline .= $line["user_id"].",";
            }
            $user_id_inline = preg_replace('\,$','',$user_id_inline);
            if(!empty($user_id_inline)) {
                mysql_set('DELETE FROM user WHERE id IN ('.$user_id_inline.');');
                mysql_set('DELETE FROM archive WHERE user_id IN ('.$user_id_inline.');');
                mysql_set('DELETE FROM data WHERE user_id IN ('.$user_id_inline.');');
            }
        }
    }
}

function check_if_send() {
    $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
    $result=mysql_get('SELECT d.*, u.token FROM archive a INNER JOIN user u WHERE send=0 OR send_date IS NULL;');
    if(!empty($result)) {
        foreach($result as $archive) {
            $test = false;
            $zipname = $archive["name"];
            if(file_exists("../../data/".$zipname)) {
                $test = true;
            }
            if($test) {
                $token = $archive["token"];
                $email_to_array = preg_split(',',$archive["email_to"],0,PREG_SPLIT_NO_EMPTY);
                $text_from = $archive["email_from"];
                $subject = $archive["subject"];
                $description = $archive["description"];
                $nb_files = $archive["nb_files"];
                if(empty($text_from)) { $text_from='Quelqu\'un'; }
                $contenu_html='<p>Bonjour,<br><br><b>'.$text_from.'</b> vous a préparer une archive ZIP contenant '.$nb_files.' fichier(s) à téléchargé sur <a href="http://127.0.0.1/wetransfer_custom/">WeTransferCustom</a>';
                $contenu_html.=' ayant pour objet <b>'.$subject.'</b>.</p><br><p><span style="color:red;">Votre archive sera accesible pendant 10 jours à partir de la date de cet email.</span></p><br><br>';
                if(!empty($description)) { $contenu_html.='<p>Message de <b>'.$text_from.'</b> :</p><br><p><b>'.$description.'</b></p><br><br>'; }
                $contenu_html.='<p style="text-align:center;">Pour télécharger l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'">cliquez ici</a>.</p><br><br>';
                $contenu_html.='<p style="text-align:center;">Pour supprimer l\'archive, <a href="http://127.0.0.1/wetransfer_custom/download.php?archive='.$zipname.'&access='.$token.'&delete=1">cliquez ici</a>.</p>';
                $contenu_text=''; //'Bonjour,\n\n'.$text_from.' vous a préparer une archive ZIP à téléchargé sur WeTransferCustom.';
                $result_send_mail = send_mail("Transfert de fichier(s) - WeTransferCustom",$contenu_html,$contenu_text,$email_to_array);
                if(!empty($result_send_mail["val"])) {
                    $date = new DateTime("now",new DateTimeZone("Europe/Paris"));
                    $update = mysql_set('UPDATE archive SET send=1, send_date = "'.$date->format("Y-m-d H:i:s").'"  WHERE id = '.$archive["id"].' ;');
                }
            }
        }
    }
}