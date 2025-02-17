<?php


header('Content-Type: application/json');

$ini_array = parse_ini_file("../config.ini");

$pdo=new PDO("pgsql:host=".$ini_array['pdo_host'].";port=".$ini_array['pdo_port']."; dbname=".$ini_array['pdo_db'].";",$ini_array['pdo_user'],$ini_array['pdo_psw']);


$collection_id = $_GET["collection_id"];


$query_user = "
    SELECT id,CONCAT('user,',CAST(A.id as TEXT)) as composed_id,CONCAT(last_name,' ',first_name) as to_display, CAST('user' as TEXT) as type, CONCAT(last_name,' ',first_name) as description, CAST('' as TEXT) as sitar_code, '' as name, NULL as officer_id ,
          '' as officer_name, '' as zone_name, '' as street_name,0 as oi_id, 0 as oi_sitar_code,
          
          CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
		  0 as license_id, CAST('' as text) as license_name	
		
    FROM sf_guard_user A
      LEFT JOIN kms_collection_tag B ON (A.id = B.target_id AND B.type like 'user')
    
    WHERE B.collection_id = $collection_id

";


$query_oi = "
    SELECT A.id, CONCAT('information_source,',CAST(A.id as TEXT)) as composed_id, CONCAT('Monumento-',A.sitar_code) as to_display,CAST('information_source' as TEXT) as type, 
        COALESCE( NULLIF(A.description,'') , '-' ) as description, 
        CAST(A.sitar_code as TEXT) as sitar_code,
        COALESCE( NULLIF(A.name,'') , '-' ) as name,                
        B.id as officer_id,CONCAT(B.last_name,' ',B.first_name) as officer_name,         
        D.name as zone_name, 
        string_agg(F.name,', ') as street_name,
             
        0 as oi_id, 0 as oi_sitar_code,
        
        CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
		0 as license_id, CAST('' as text) as license_name	
    
    FROM st_information_source A
        LEFT JOIN sf_guard_user B ON B.id = A.liable_officier
    
        LEFT JOIN st_information_source_st_circoscrizione C ON C.st_information_source_id = A.id
        LEFT JOIN st_circoscrizione D ON D.id = C.st_circoscrizione_id
    
        LEFT JOIN st_italian_address_info_source E ON E.information_source_id = A.id
        LEFT JOIN st_italian_street F ON F.id = E.italian_street_id
    
        LEFT JOIN kms_collection_tag G ON (A.id = G.target_id AND G.type like 'information_source')
    
    WHERE G.collection_id = $collection_id
    
    
    GROUP BY A.id,B.id,B.first_name,B.last_name,D.name
     
";



$query_pa = "
    SELECT A.id,CONCAT('archaeo_part,',CAST(A.id as TEXT)) as composed_id, CONCAT('Partizione-',A.id) as to_display, 'archaeo_part' as type,
            COALESCE( NULLIF(A.description,'') , '-' ) as description,
            CAST(A.id as TEXT) as sitar_code, '' as name,
            B.liable_officier as officer_id,
            CONCAT(C.last_name,' ',C.first_name) as officer_name,
            E.name as zone_name,
            '' as street_name,
            B.id as oi_id, B.sitar_code as oi_sitar_code,
            
            CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
		    0 as license_id, CAST('' as text) as license_name	
    
    FROM st_archaeo_part A
         LEFT JOIN st_information_source B ON B.id = A.information_source_id
         LEFT JOIN sf_guard_user C ON C.id = B.liable_officier
    
         LEFT JOIN st_information_source_st_circoscrizione D ON D.st_information_source_id = B.id
         LEFT JOIN st_circoscrizione E ON E.id = D.st_circoscrizione_id
    
         LEFT JOIN kms_collection_tag F ON (A.id = F.target_id AND F.type like 'archaeo_part')
    
    WHERE collection_id = $collection_id
";



$query_collection = "
    SELECT A.id, CONCAT('collection.',CAST(A.id as TEXT)) as composed_id, CONCAT('Collezione-',CAST(A.id as TEXT)) as to_display,
        'collection' as type, A.description,CAST(A.id as TEXT) as sitar_code, '' as name, 0 as officer_id, '' as officer_name, '' as zone_name, '' as street_name, 0 as oi_id, 0 as oi_sitar_code,
        A.title, A.created_by, CONCAT(B.last_name,' ',B.first_name) as created_by_name, A.created_at,A.license_id, C.name as license_name
    
    FROM kms_collection A
        LEFT JOIN sf_guard_user B ON B.id = A.created_by
        LEFT JOIN kms_license C ON C.id = A.license_id
    
        LEFT JOIN kms_collection_tag D ON (A.id = D.target_id AND D.type like 'collection')
    
    
    WHERE collection_id = $collection_id
";

$query_event = "
    SELECT A.id, CONCAT('event.',CAST(A.id as TEXT)) as composed_id, CONCAT('Collezione-',CAST(A.id as TEXT)) as to_display,
        'event' as type, A.description,CAST(A.id as TEXT) as sitar_code, '' as name, 0 as officer_id, '' as officer_name, '' as zone_name, '' as street_name, 0 as oi_id, 0 as oi_sitar_code,
        A.title, A.created_by, CONCAT(B.last_name,' ',B.first_name) as created_by_name, A.created_at,A.license_id, C.name as license_name
    
    FROM kms_event A
        LEFT JOIN sf_guard_user B ON B.id = A.created_by
        LEFT JOIN kms_license C ON C.id = A.license_id
    
        LEFT JOIN kms_collection_tag D ON (A.id = D.target_id AND D.type like 'event')
    
    
    WHERE collection_id = $collection_id
";

$statement = $pdo->prepare("
	SELECT *,count(*) OVER() AS total_count
	FROM
	(
    	($query_user)
    	UNION
    	($query_oi)
    	UNION
    	($query_pa)
    	UNION
    	($query_collection)
    	UNION
    	($query_event)
	)tmp
    ORDER BY type DESC,sitar_code
");


$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_OBJ);

$arrayResult = array();
$total_count = 0;

foreach($result as $row){
    $total_count = $row->total_count;

    $row->collection_id = $collection_id;

    $row->street_name = ucwords(strtolower($row->street_name));    
    $row->tooltip = getTooltipInformation($pdo,$row);

    array_push($arrayResult,$row);
}

sleep(1);   //per evitare il run layout failed


echo json_encode(array(
    "result" => $arrayResult,
    "total" => $total_count,
    "eventual_error" => $pdo->errorInfo()
));




////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////


function getTooltipInformation($pdo,$record){
    $info_tooltip = "";
  
    // OI
    if($record->type=="information_source"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><td width='50'><img src='images/icons/icon_information_source.png' style='border: 1px solid grey;' alt=' ' height='32' width='32'></td><th align='left' width='150' style='color:#2c2c2c;' >Codice Monumento</th><td style='padding: 2px;'>".strip_tags($record->sitar_code)."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Nome</th><td style='padding: 2px;color:#2c2c2c;'>".$record->name."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Funz.Responsabile</th><td style='padding: 2px;'><a href='#user/".$record->officer_id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->officer_name." (#".$record->officer_id.")</u></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Zona</th><td style='padding: 2px;color:#2c2c2c;'>".strip_tags($record->zone_name)."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Indirizzo</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->street_name), 70, "<br>")."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td align='center' style='color:#2c2c2c;'>".getCountOICollections($pdo,$record->id)." Collezioni</td>
                                    <td align='center' style='color:#2c2c2c;'>".getCountOIEvents($pdo,$record->id)." Eventi</td>
                                    <td align='center'><img src='images/icons/icon_file.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>".getCountOIDocs($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center'><img src='images/icons/icon_lens.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Anteprima</u></a></td>
                                    <td align='center'><img src='images/icons/icon_map.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Mappa</u></a></td>
                                </tr>
                            </table>
                        </div>";
    }

    // PA
    if($record->type=="archaeo_part"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><td width='50'><img src='images/icons/icon_archaeo_part.png' style='border: 1px solid grey;' alt=' ' height='32' width='32'></td><th align='left' width='150'  style='color:#2c2c2c;'>Codice Partizione</th><td style='padding: 2px;'>".strip_tags($record->sitar_code)."</td></tr>
                                <tr><td width='50'></td><th align='left' width='150'  style='color:#2c2c2c;'>Monumento di Rif.</th><td style='padding: 2px;'>".strip_tags($record->oi_sitar_code)."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Funz.Responsabile</th><td style='padding: 2px;'><a href='#user/".$record->officer_id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->officer_name." (#".$record->officer_id.")</u></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Zona</th><td style='padding: 2px;color:#2c2c2c;'>".strip_tags($record->zone_name)."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td align='center' style='color:#2c2c2c;'>".getCountPACollections($pdo,$record->id)." Collezioni</td>
                                    <td align='center' style='color:#2c2c2c;'>".getCountPAEvents($pdo,$record->id)." Eventi</td>
                                    <td align='center'><img src='images/icons/icon_file.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>".getCountPADocs($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center'><img src='images/icons/icon_lens.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Anteprima</u></a></td>
                                    <td align='center'><img src='images/icons/icon_map.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Mappa</u></a></td>
                                </tr>
                            </table>
                        </div>";
    }

    // USER
    else if($record->type=="user"){
        $info_tooltip = "<table style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <tr><td width='50'><img src='images/icons/icon_user.png' style='border: 1px solid grey;' alt=' ' height='32' width='32'></td><th align='left' width='150' style='color:#2c2c2c;' >Utente</th><td><a href='#user/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".strip_tags($record->to_display)." (#".$record->id.")</u></td></tr>
                        </table>";
    }

    // COLLECTION
    else if($record->type=="collection"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><td width='50'><img src='images/icons/icon_collection_black.png' style='border: 1px solid grey;' alt=' ' height='32' width='32'></td><th align='left' width='150'  style='color:#2c2c2c;'>Codice Collezione</th><td style='padding: 2px;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->sitar_code."</u></a></td></tr>
                                <tr><td width='50'></td><th align='left' width='150'  style='color:#2c2c2c;'>Titolo</th><td style='padding: 2px;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".wordwrap(str_replace('"',"'",$record->title), 70, "<br>")."</u></a></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Creata Da</th><td style='padding: 2px;'><a href='#user/".$record->created_by."' style='color: #963232 !important; font-weight: bold;'><u>".$record->created_by_name." (#".$record->created_by.")</u></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Creata Il</th><td style='padding: 2px;color:#2c2c2c;'>".date_format(date_create($record->created_at), 'd/m/Y')."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Licenza</th><td style='padding: 2px;color:#2c2c2c;'>".$record->license_name."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td width='60'></td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/coworkers' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionCoworkers($pdo,$record->id)."</u></a> Collaboratori</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionThreads($pdo,$record->id)."</u></a> Discussioni</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionMessages($pdo,$record->id)."</u></a> Messaggi</td>
                                </tr>
                                <tr>
                                    <td width='60'></td>                          
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionFiles($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/external_resources' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionExternalResources($pdo,$record->id)."</u></a> Risorse Esterne</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/tags' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionTags($pdo,$record->id)."</u></a> TAGS</td>
                                </tr>
                            </table>
                        </div>";
    }

    // EVENT
    else if($record->type=="event"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><td width='50'><img src='images/icons/icon_calendar_black.png' style='border: 1px solid grey;' alt=' ' height='32' width='32'></td><th align='left' width='150'  style='color:#2c2c2c;'>Codice Evento</th><td style='padding: 2px;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->sitar_code."</u></a></td></tr>
                                <tr><td width='50'></td><th align='left' width='150'  style='color:#2c2c2c;'>Titolo</th><td style='padding: 2px;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".wordwrap(str_replace('"',"'",$record->title), 70, "<br>")."</u></a></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Creato Da</th><td style='padding: 2px;'><a href='#user/".$record->created_by."' style='color: #963232 !important; font-weight: bold;'><u>".$record->created_by_name." (#".$record->created_by.")</u></td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Creato Il</th><td style='padding: 2px;color:#2c2c2c;'>".date_format(date_create($record->created_at), 'd/m/Y')."</td></tr>
                                <tr><td width='50'></td><th align='left' style='color:#2c2c2c;'>Licenza</th><td style='padding: 2px;color:#2c2c2c;'>".$record->license_name."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td width='60'></td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/coworkers' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventCoworkers($pdo,$record->id)."</u></a> Collaboratori</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventThreads($pdo,$record->id)."</u></a> Discussioni</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventMessages($pdo,$record->id)."</u></a> Messaggi</td>
                                </tr>
                                <tr>
                                    <td width='60'></td>                          
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventFiles($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/external_resources' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventExternalResources($pdo,$record->id)."</u></a> Risorse Esterne</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/tags' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventTags($pdo,$record->id)."</u></a> TAGS</td>
                                </tr>
                            </table>
                        </div>";
    }


    return $info_tooltip;
}




function getCountOIDocs($pdo,$oi_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM st_information_source_document
        WHERE information_source_id = :oi_id
    ");

    $statement->execute(array(
        "oi_id" => $oi_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}

function getCountPADocs($pdo,$pa_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM st_archaeo_part_document
        WHERE archaeo_part_id = :pa_id
    ");

    $statement->execute(array(
        "pa_id" => $pa_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountCollectionCoworkers($pdo,$collection_id){
    $statement = $pdo->prepare("
        SELECT (count(*)+1) as count
        FROM kms_collection_user
        WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountCollectionThreads($pdo,$collection_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_thread
        WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountCollectionMessages($pdo,$collection_id){
    $statement = $pdo->prepare("
    SELECT count(*)
        FROM kms_collection_thread_message A
		  LEFT JOIN kms_collection_thread B ON B.id = A.thread_id 
	WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}

function getCountCollectionFiles($pdo,$collection_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_file
        WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountCollectionExternalResources($pdo,$collection_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_external_resource
        WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}



function getCountCollectionTags($pdo,$collection_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_tag
        WHERE collection_id = :collection_id
    ");

    $statement->execute(array(
        "collection_id" => $collection_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountOICollections($pdo, $oi_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_tag
        WHERE target_id = :oi_id
          AND type like 'information_source'
    ");

    $statement->execute(array(
        "oi_id" => $oi_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}

function getCountOIEvents($pdo, $oi_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_tag
        WHERE target_id = :oi_id
          AND type like 'information_source'
    ");

    $statement->execute(array(
        "oi_id" => $oi_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountPACollections($pdo, $pa_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_collection_tag
        WHERE target_id = :pa_id
          AND type like 'archaeo_part'
    ");

    $statement->execute(array(
        "pa_id" => $pa_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}

function getCountPAEvents($pdo, $pa_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_tag
        WHERE target_id = :pa_id
          AND type like 'archaeo_part'
    ");

    $statement->execute(array(
        "pa_id" => $pa_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}





/*
 * EVENT
 *
 */

function getCountEventCoworkers($pdo,$event_id){
    $statement = $pdo->prepare("
        SELECT (count(*)+1) as count
        FROM kms_event_user
        WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountEventThreads($pdo,$event_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_thread
        WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountEventMessages($pdo,$event_id){
    $statement = $pdo->prepare("
    SELECT count(*)
        FROM kms_event_thread_message A
		  LEFT JOIN kms_event_thread B ON B.id = A.thread_id 
	WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}

function getCountEventFiles($pdo,$event_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_file
        WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}


function getCountEventExternalResources($pdo,$event_id){
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_external_resource
        WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}



function getCountEventTags($pdo,$event_id)
{
    $statement = $pdo->prepare("
        SELECT count(*)
        FROM kms_event_tag
        WHERE event_id = :event_id
    ");

    $statement->execute(array(
        "event_id" => $event_id
    ));

    $result = $statement->fetchAll(PDO::FETCH_OBJ);

    return $result[0]->count;
}