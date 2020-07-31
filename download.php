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

$_SESSION['msg_array'] = [];

$func = require_once("assets/php/functions.php");
if(empty($func)) {
    $included_files = get_included_files();
    $find = false;
    foreach ($included_files as $filename) {
        if(preg_match('#assets\/php\/functions\.php#',$filename)===1) {
            $find = true;
        }
    }
    if($find==true) { $func = 1; }
}

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
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.js"></script>
    <script defer src="assets/js/script.js"></script>
</head>
<body class="container">
<?php

    $name = empty($_GET["archive"]) ? "" : $_GET["archive"] ;
    $delete = empty($_GET["delete"]) ? 0 : $_GET["delete"] ;
    $token = empty($_GET["access"]) ? "" : $_GET["access"] ;
    $download = empty($_POST["download"]) ? "" : $_POST["download"] ;

    $btn=false;
    $msg='vous n\'avez pas accès à cette archive !';
    if(!empty($func)) {
        if(!empty($name) && !empty($token)) {
            $date = new DateTime("now",new DateTimeZone("Europe/Paris")); // H:i:s
            $select = mysql_get('SELECT a.id as id_archive, u.id as id_user, name, dir, nb_files, last_visit, send_date, ("'.$date->format("Y-m-d").'" BETWEEN DATE_FORMAT(send_date,"%Y-%m-%d") AND DATE_ADD(DATE_FORMAT(send_date,"%Y-%m-%d"), INTERVAL 10 DAY)) as test_date FROM archive a INNER JOIN user u ON a.user_id=u.id WHERE u.token="'.$token.'" AND a.name="'.$name.'" ;'); //AND send=1 AND "'.$date->format("Y-m-d H:i:s").'" BETWEEN send_date AND DATE_ADD(send_date, INTERVAL 10 DAY)
            if(!empty($select)) {
                $name = $select[0]['name'];
                $archive_dir = $select[0]['dir'];
                $archive_path = FOLDER_ARCHIVE.'/'.$archive_dir.'/'.$name;
                //var_dump($archive_path,file_exists($archive_path));
                if(file_exists($archive_path)) {
                    $archive_id = $select[0]["id_archive"];
                    $user_id = $select[0]["id_user"];
                    if(!empty($select[0]["test_date"]) && strtoupper(trim($select[0]["test_date"]))!="NULL") {
                        if(!empty($delete)) {
                            //delete_archive_files($token,$name,$archive_dir,$archive_path);
                            //if(file_exists($archive_path)) {
                                unlink($archive_path);
                                if(sizeof(scandir(FOLDER_ARCHIVE.'/'.$archive_dir))==2) {
                                    $sup_f = rmdir(FOLDER_ARCHIVE.'/'.$archive_dir);
                                    //var_dump($sup_f);
                                    clean_bdd($token);
                                }
                                else {
                                    clean_bdd($token,$name);
                                }
                            //}
                            $_SESSION = [];
                            $msg='<span class="text-success">Suppression effectuée !</span><br><br><span class="text-primary">Nous vous remercions d\'avoir utilisé WeTranfersCustom !</span><br><br>Redirection sur l\'accueil dans <span id="counter">10</span> secondes.';
                            $msg.='<script defer>let intervalB = setInterval(()=>{ document.getElementById("counter").innerText = parseInt(document.getElementById("counter").innerText)-1; },1000); let timeoutB = setTimeout(()=>{ clearInterval(intervalB);document.location.href="index.php"; },10000); </script>';
                        }
                        else {
                            $btn=true;
                            $_SESSION = [];
                            $send_date_text = $select[0]["send_date"];
                            $last_date_text = $select[0]["last_visit"];
                            if(!empty($send_date_text)) {
                                $send_date = new DateTime($send_date_text,new DateTimeZone("Europe/Paris"));
                                $send_date = $send_date->add(new DateInterval('P10D'));
                                $msg='Vous pouvez télécharger votre archive jusqu\'au '.$send_date->format("d/m/Y").' à minuits !';
                            }
                            else if(!empty($last_date_text)) {
                                $last_date = new DateTime($last_date_text,new DateTimeZone("Europe/Paris"));
                                $last_date = $last_date->add(new DateInterval('P10D'));
                                $msg='Vous pouvez télécharger votre archive jusqu\'au '.$last_date->format("d/m/Y").' à minuits !';
                            }
                            else {
                                $msg='Vous pouvez télécharger votre archive !';
                            }
                        }
                        /*else if(!empty($download)) {
                            var_dump($archive_path); // ne passe pas
                            $msg='L\'archive est en cous de téléchargement...<br><br><b>Nous vous remercions d\'avoir utilisé WeTranfersCustom !</b>';
                            header('Content-Type: application/zip');
                            header('Content-disposition: attachment; filename='.$name);
                            header('Content-Length: ' . filesize($archive_path));
                            readfile($archive_path);
                        }*/
                    }
                    else {
                        if(empty($select[0]["send"])) {
                            $btn=true;
                            $_SESSION = [];
                            $msg='Vous pouvez télécharger votre archive !<br><br>Remarque : Aucun email n\'a été envoyé.';
                        }
                        else {
                            $send_date_text = $select[0]["send_date"];
                            $last_date_text = $select[0]["last_visit"];
                            if(!empty($send_date_text)) {
                                $send_date = new DateTime($send_date_text,new DateTimeZone("Europe/Paris"));
                                $send_date = $send_date->add(new DateInterval('P10D'));
                                $interval = $send_date->diff($date);
                                $msg='Vous n\'avez plus accès à cette archive depuis '.$interval->format("%a jour(s)").' !';
                            }
                            else if(!empty($last_date_text)) {
                                $last_date = new DateTime($last_date_text,new DateTimeZone("Europe/Paris"));
                                $last_date = $last_date->add(new DateInterval('P10D'));
                                $interval = $last_date->diff($date);
                                $msg='Vous n\'avez plus accès à cette archive depuis '.$interval->format("%a jour(s)").' !';
                            }
                            else {
                                $msg='Vous n\'avez plus accès à cette archive !';
                            }
                            //delete_archive_files($token,$name,$archive_dir,$archive_path);
                            //if(file_exists($archive_path)) {
                                unlink($archive_path);
                                if(sizeof(scandir(FOLDER_ARCHIVE.'/'.$archive_dir))==2) {
                                    $sup_f = rmdir(FOLDER_ARCHIVE.'/'.$archive_dir);
                                    //var_dump($sup_f);
                                    clean_bdd($token);
                                }
                                else {
                                    clean_bdd($token,$name);
                                }
                            //}
                        }
                    }
                }
                else {
                    $msg='L\'archive indiquée n\'existe pas !';
                }
            }
            else {
                $msg='L\'archive indiquée n\'existe pas !';
            }
        }
        else {
            $msg='Votre lien n\'est pas valide !';
        }
    }
    else {
        $msg='Nous nous exusons mais notre service est momentanément indisponible.';
    }
    echo('<div class="row vh-100 align-items-center justify-content-center my-3"><div class="col-12 w-100 align-self-center">');
    if($btn) {
        //echo('<form class="row align-items-center justify-content-center my-3" method="post" enctype="multipart/form-data"><input name="download" type="submit" value="Télécharger" class="btn btn-primary"></form>');
        echo('<div class="row align-items-center justify-content-center mb-3"><a id="download" href="'.$archive_path.'" target="_blank" class="btn btn-primary">Télécharger</a></div>');
    }
    if(!empty($msg)) {
        echo('<p id="info-wtr" class="text-muted text-center user-select-none">'.$msg.'</p>');
    }
    echo('</div></div>');
?>
</body>
</html>