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

function send_mail($objet="",$contenu_html="",$contenu_text="",$destinataire=[]) {

    $return_value = array("val" => 0,"msg" =>'The file "vendor/autoload.php" is missing or corrupt.');
    
    if(file_exists('././vendor/autoload.php')) {
        $return_value = array("val" => 0,"msg" =>'Error in function "send_mail".');
        
        require_once('vendor/autoload.php');

        // Create the Transport (mailtrap)
        $transport = (new Swift_SmtpTransport('smtp.mailtrap.io',2525))
            ->setUsername('16d23dc5057427')
            ->setPassword('c3904e2ddcdd93');
        
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);

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