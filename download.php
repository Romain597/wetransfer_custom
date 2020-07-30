<?php
session_start();
header('Content-type: text/html; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', true);

ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('max_input_time', 500);
ini_set('max_execution_time', 500);

$func = require_once("assets/php/functions.php");
if(!empty($func)) {
    check_if_send();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Télécharment - WeTransfer Romain</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <script defer src="assets/js/script.js"></script>
</head>
<body class="container">
<?php

    $name = empty($_GET["archive"]) ? "" : $_GET["archive"] ;
    $suppr = empty($_GET["delete"]) ? "" : $_GET["delete"] ;
    $token = empty($_GET["access"]) ? "" : $_GET["access"] ;
    $download = empty($_POST["download"]) ? "" : $_POST["download"] ;

    $btn=false;
    $msg='Nous nous exusons mais vous n\'avez pas accès à cette archive !';
    if(!empty($func)) {
        if(!empty($name) && !empty($token)) {
            $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
            $select = mysql_get('SELECT a.id as id_archive, u.id as id_user, name, dir, nb_files, send_date, ("'.$date->format("Y-m-d").'" BETWEEN DATE_FORMAT(send_date,"%Y-%m-%d") AND DATE_ADD(DATE_FORMAT(send_date,"%Y-%m-%d"), INTERVAL 10 DAY)) as test_date FROM archive a INNER JOIN user u ON a.user_id=u.id WHERE token="'.$token.'" ;'); //AND send=1 AND "'.$date->format("Y-m-d H:i:s").'" BETWEEN send_date AND DATE_ADD(send_date, INTERVAL 10 DAY)
            if(!empty($select)) {
                $archive_id = $select[0]["id_archive"];
                $user_id = $select[0]["id_user"];
                if(!empty($select[0]["test_date"]) && strtoupper(trim($select[0]["test_date"]))!="NULL") {
                    if(!empty($suppr)) {
                        clean_bdd($token);
                        $msg='Suppression effectuée !';
                    }
                    else if(!empty($download)) {
                        $msg='L\'archive est en cous de téléchargement...<br><br><b>Nous vous remercions d\'avoir utilisé WeTranfersCustom !</b>';
                        header('Content-Type: application/zip');
                        header('Content-disposition: attachment; filename='.$zipname);
                        header('Content-Length: ' . filesize($zipname));
                        readfile($zipname);
                    }
                    else {
                        $btn=true;
                        $send_date_text = $select[0]["send_date"];
                        if(!empty($send_date)) {
                            $send_date = new DateTime($send_date_text,new DateTimeZone("Europe/Paris"));
                            $send_date = $send_date->add(new DateInterval('P10D'));
                            $interval = $send_date->diff($date);
                            $msg='Vous pouvez télécharger votre archive jusqu\'au '.$send_date->format("Y-m-d").' à minuits !';
                        }
                        else {
                            $msg='Vous pouvez télécharger votre archive !';
                        }
                    }
                }
                else {
                    if(empty($select[0]["send"])) {
                        $btn=true;
                        $msg='Vous pouvez télécharger votre archive !<br><br>Remarque : Aucun email n\'a été envoyé.';
                    }
                    else {
                        $send_date_text = $select[0]["send_date"];
                        if(!empty($send_date)) {
                            $send_date = new DateTime($send_date_text,new DateTimeZone("Europe/Paris"));
                            $send_date = $send_date->add(new DateInterval('P10D'));
                            $interval = $send_date->diff($date);
                            $msg='Nous nous excusons mais vous n\'avez plus accès à cette archive depuis '.$interval->format("%a jour(s)").' !';
                        }
                        else {
                            $msg='Nous nous excusons mais vous n\'avez plus accès à cette archive !';
                        }
                        clean_bdd($token);
                    }
                }
            }
            else {
                $msg='Votre lien n\'est pas valide !';
            }
        }
        else {
            $msg='Votre lien n\'est pas valide !';
        }
    }
    if($btn) {
        echo('<form class="row" method="post" enctype="multipart/form-data"><input name="download" type="submit" value="Télécharger" class="btn btn-primary"></form>');
    }
    if(!empty($msg)) {
        echo('<p class="row text-center">'.$msg.'</p>');
    }
?>
</body>
</html>