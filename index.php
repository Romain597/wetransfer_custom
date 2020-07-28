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

$date = new DateTime("now",new DateTimeZone("Europe/Paris"));
//var_dump($date->format("Y-m-d H:i:s"));
$func = require_once("assets/php/functions.php");
//unset($_SESSION["user"]);
if(!isset($_SESSION["user"])) {
    $_SESSION["user"] = mysql_set('INSERT INTO user (id,token,last_visit) VALUES (NULL,"'.session_id().'","'.$date->format("Y-m-d H:i:s").'");');
}
//var_dump($_SESSION["user"]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeTransfer Romain</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <script defer src="assets/js/script.js"></script>
</head>
<body class="container">
    <div class="row">
        <form id="archive-form" action="assets/php/upload.php" class="col" method="post" enctype="multipart/form-data">
            <div class="form-group row">
                <input id="input-file" name="input-files" class="form-control-file" type="file" multiple files>
                <input id="add-file-input" name="add-files" class="btn btn-secondary" type="submit" value="Ajouter fichier(s)">
            </div>
            <div class="form-group row">
                <label class="" for="files-list-select">Liste fichiers</label>
                <select id="files-list-select" name="files-list[]" class="form-control" size="5" multiple>
                <?php
                //var_dump(send_mail("test","salut","",["coucou@char.fr"])); // test
                    $list = mysql_get('SELECT * FROM data WHERE user_id='.$_SESSION["user"].' ORDER BY id;');
                    //var_dump($list);
                    if(!empty($list)) {
                        //echo('<option name="none" value=""></option');
                        foreach($list as $value) {
                            if(file_exists("cache/".$value["name"])) {
                                echo('<option value="'.$value["id"].'/'.$value["name"].'">'.$value["name"].'</option>');
                            }
                        }
                    }
                ?>
                </select>
                <input id="delete-file-input" name="delete-files" class="btn btn-secondary" type="submit" value="Supprimer de la liste">
            </div>
            <div class="form-group row">
                <label class="" for="email-to-input">Email destinataire</label>
                <input id="email-to-input" name="email-to" class="form-control" type="email">
            </div>
            <div class="form-group row">
                <label class="" for="text-from-input">Prénom et Nom de l'expéditeur</label>
                <input id="text-from-input" name="text-from" class="form-control" type="text">
            </div>
            <div class="form-group row">
                <label class="" for="subject-input">Sujet</label>
                <input id="subject-input" name="subject" class="form-control" type="text">
            </div>
            <div class="form-group row">
                <label class="" for="description-input">Description</label>
                <textarea id="description-input" name="description" class="form-control" rows="5"></textarea>
            </div>
            <input id="validate" name="validate-files" type="submit" class="btn btn-primary" value="Envoyer">
        </form>
    </div>
</body>
</html>