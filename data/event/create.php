<?php


header('Content-Type: application/json');


$ini_array = parse_ini_file("../config.ini");

$pdo=new PDO("pgsql:host=".$ini_array['pdo_host'].";port=".$ini_array['pdo_port']."; dbname=".$ini_array['pdo_db'].";",$ini_array['pdo_user'],$ini_array['pdo_psw']);

$data = json_decode($_POST['data'],true);


$s = $pdo->prepare("
	INSERT INTO kms_event(title, description, created_by,license_id,created_at)
	VALUES(:title,:description,:created_by,:license_id,now())
");

$params = array(
    'title' => $data['title'],
    'description' => $data['description'],
    'created_by' => $data['created_by'],
    'license_id' => $data['license_id']
);

$success = $s->execute($params);

$last_id = $pdo->lastInsertId("kms_event_id_seq");


foreach (explode(",",$data["tags"]) as $tag){
    $tag = explode(".",$tag);

    $tag_type = $tag[0];
    $target_id = $tag[1];

    creaTag($pdo,$last_id,$tag_type,$target_id);
}

sleep(1.5);


if ($success) {

    require_once('../user_activity/create.php');
    createUserActivity($pdo,$data["created_by"],'creato l\'evento <b>"'.$data["title"].'"</b>','event/'.$last_id,"icon_calendar.png",$last_id,null);

    echo json_encode(array(
        "success" => true,
        "result" => array(
            "id" => $last_id
        )
    ));
}
else{
    echo json_encode(array(
        "success" => false,
        "error_message" =>  $pdo->errorInfo()
    ));
}


////////////////////////////////////////////////////////

function creaTag($pdo, $event_id, $tag_type, $target_id){
    $s = $pdo->prepare("
        INSERT INTO kms_event_tag(event_id, type, target_id)
        VALUES(:event_id, :tag_type, :target_id)
    ");

    $params = array(
        'event_id' => $event_id,
        'tag_type' => $tag_type,
        'target_id' => $target_id
    );

    $success = $s->execute($params);

    if(!$success){
        $s = $pdo->prepare("
            DELETE FROM kms_event
            WHERE id = $event_id
        ");
        $s->execute();

        echo json_encode(array(
            "success" => false,
            "error_message" =>  "Errore nella creazione dei tag, effettuato anche il Revert della event creata",
            "error_message" =>  $pdo->errorInfo()
        ));
        exit(0);

    }
}

