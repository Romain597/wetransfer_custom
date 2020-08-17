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

if(!isset($_SESSION['msg_array'])) {
    $_SESSION['msg_array'] = [];
}

$func = require_once(__DIR__.'/'."assets/php/functions.php");
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
    clean_bdd();
    check_if_send();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeTransfer Romain</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo("http://".$_SERVER["HTTP_HOST"].preg_replace('#index\.php$#','',$_SERVER["PHP_SELF"])); ?>assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.js"></script>
    <script defer src="<?php echo("http://".$_SERVER["HTTP_HOST"].preg_replace('#index\.php$#','',$_SERVER["PHP_SELF"])); ?>assets/js/script.js"></script>
</head>
<body class="container">
    <div class="row align-items-center justify-content-center">
        <form id="archive-form" action="assets/php/upload.php" class="col" method="post" enctype="multipart/form-data">
            <div class="row mt-3 mb-4 align-items-center justify-content-center">
                <p class="text-center user-select-none col-12 my-0 align-self-center text-primary">Les dossiers ne sont <u>pas autorisés</u>.</p>
                <p class="text-center user-select-none col-12 my-0 align-self-center text-info font-italic">Suppression automatique au bout de 10 jours après validation.</p>
                <p class="text-center user-select-none col-12 my-0 align-self-center text-info font-italic">Limite de taille par fichier fixé à <?php echo(MAX_FILE_MULTIPLICATOR); ?> MO.</p>
                <p class="text-center user-select-none col-12 my-0 align-self-center text-info font-italic">Limite de taille par archive fixé à <?php echo(MAX_ARCHIVE_MULTIPLICATOR); ?> MO.</p>
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label for="input-file">Ajout(s) de fichier(s)</label>
                <div class="align-self-center custom-file"> <!-- col-auto -->
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(MAX_FILE_SIZE); ?>" />
                    <input type="file" name="input-files[]" class="custom-file-input" id="customFileLangHTML" data-browse="Parcourir" multiple>
                    <label class="custom-file-label text-secondary" for="customFileLangHTML">Choisir un ou plusieurs fichier(s)</label>
                </div>
                <input id="add-file-input" name="add-files" class="btn btn-secondary mt-3" type="submit" value="Ajouter fichier(s)">
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label for="files-list-select">Liste de fichier(s)</label>
                <select id="files-list-select" name="files-list[]" class="form-control" size="5" multiple>
                    <?php
                        if(!empty($func)) {
                            $list = mysql_get('SELECT d.* FROM data d INNER JOIN user u ON d.user_id=u.id WHERE token="'.$_SESSION["user"].'" ORDER BY id;');
                            if(!empty($list)) {
                                foreach($list as $value) {
                                    if(file_exists(__DIR__.'/'.FOLDER_DATA.'/'.$value["dir"].'/'.$value["name"])) {
                                        echo('<option value="'.$value["id"].'}'.$value['dir'].'/'.$value["name"].'">'.$value["name"].'</option>');
                                    }
                                }
                            }
                        }
                    ?>
                </select>
                <input id="delete-file-input" name="delete-files" class="btn btn-secondary mt-3" type="submit" value="Supprimer(s) de la liste">
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label class="mb-0" for="email-to-input">Email(s) destinataire(s)</label>
                <small class="text-center form-text text-muted user-select-none w-100 mt-0 mb-2">( une virgule , ou un point-virgule ; pour mettre plusieurs adresses emails )</small>
                <input id="email-to-input" name="email-to" class="form-control" type="email" multiple>
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label class="mb-0" for="email-from-input">Email(s) expéditeur(s)</label>
                <small class="text-center form-text text-muted user-select-none w-100 mt-0 mb-2">( une seule adresse email )</small>
                <input id="email-from-input" name="email-from" class="form-control" type="email">
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label for="text-from-input">Dénomination de l'expéditeur</label>
                <input id="text-from-input" name="text-from" class="form-control" type="text">
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label for="subject-input">Objet</label>
                <input id="subject-input" name="subject" class="form-control" type="text">
            </div>
            <div class="form-group row align-items-center justify-content-center">
                <label for="description-input">Description</label>
                <textarea id="description-input" name="description" class="form-control" rows="5"></textarea>
            </div>
            <div class="row align-items-center justify-content-center">
                <input id="validate" name="validate-files" type="submit" class="btn btn-primary my-3" value="Envoyer">
            </div>
            <?php
            if(!empty($_SESSION['msg_array'])) {
                echo('<div class="row my-3 align-items-center justify-content-center">');
                if(!empty($_SESSION['msg_array']["succes"])) {
                    foreach($_SESSION['msg_array']["succes"] as $key => $msg) {
                        echo('<p class="text-success text-center user-select-none col-12 my-0">'.$msg.'</p>');
                    }
                    echo('<p class="text-success text-center user-select-none col-12 my-0">Il reste 10 jours à votre destinataire pour récupérer l\'archive.</p>');
                    $_SESSION =[];
                }
                else if(!empty($_SESSION['msg_array']["error"])) {
                    foreach($_SESSION['msg_array']["error"] as $key => $error) {
                        if(!empty($error["msg"])) {
                            if(!empty($error["name"])) {
                                echo('<p class="text-danger text-center user-select-none col-12 my-0">Fichier <b>'.basename($error["name"]).'</b> : '.$error["msg"].'</p>');
                            }
                            else {
                                echo('<p class="text-danger text-center user-select-none col-12 my-0">'.$error["msg"].'</p>');
                            }
                        }
                    }
                }
                echo('</div>');
            }
        ?>
        </form>
    </div>
</body>
</html>