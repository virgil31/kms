<?php


header('Content-Type: application/json');

$ini_array = parse_ini_file("../config.ini");

$pdo=new PDO("pgsql:host=".$ini_array['pdo_host'].";port=".$ini_array['pdo_port']."; dbname=".$ini_array['pdo_db'].";",$ini_array['pdo_user'],$ini_array['pdo_psw']);



$keywords = $_GET["query"];

if(strlen(trim($keywords)) == 0) exit();


$limit = $_GET['limit'];
$start = $_GET['start'];

$ts_query_keywords = str_replace(" ",":* &",$keywords);


$query_user = "
    SELECT id,CONCAT('user.',CAST(A.id as TEXT)) as composed_id,CONCAT(last_name,' ',first_name) as to_display, 'user' as type, CONCAT(last_name,' ',first_name) as description, '' as sitar_code, '' as name, NULL as officer_id ,
        '' as officer_name, '' as zone_name, '' as street_name,0 as oi_id, 0 as oi_sitar_code,
                
		CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
		0 as license_id, CAST('' as text) as license_name		
		
        
	FROM sf_guard_user A

	WHERE first_name ilike '%$keywords%'
	  OR last_name ilike '%$keywords%'
	  OR CONCAT(first_name,' ',last_name) ilike '%$keywords%'
	  OR CONCAT(last_name,' ',first_name) ilike '%$keywords%'
	  OR CAST(id as TEXT) like '%$keywords%'
	";


$query_oi = "
    SELECT *
    FROM (
      SELECT A.id, CONCAT('information_source.',CAST(A.id as TEXT)) as composed_id, CONCAT('Monumento-',A.sitar_code) as to_display,CAST('information_source' as TEXT) as type, 
        ts_headline(COALESCE( NULLIF(A.description,'') , '-' ),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as description, 

        ts_headline(CAST(A.sitar_code as TEXT),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as sitar_code,     

        ts_headline(COALESCE( NULLIF(A.name,'') , '-' ),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as name,     
           
        B.id as officer_id,ts_headline(CONCAT(B.last_name,' ',B.first_name),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as officer_name,         

        D.name as zone_name, 
        ts_headline(INITCAP(LOWER(string_agg(F.name,', '))),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as street_name,
                 
        0 as oi_id, 0 as oi_sitar_code,
                     
		CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
		0 as license_id, CAST('' as text) as license_name
    
      FROM st_information_source A
        LEFT JOIN sf_guard_user B ON B.id = A.liable_officier
    
        LEFT JOIN st_information_source_st_circoscrizione C ON C.st_information_source_id = A.id
        LEFT JOIN st_circoscrizione D ON D.id = C.st_circoscrizione_id
    
        LEFT JOIN st_italian_address_info_source E ON E.information_source_id = A.id
        LEFT JOIN st_italian_street F ON F.id = E.italian_street_id
        
      GROUP BY A.id,B.id,B.first_name,B.last_name,D.name
    ) tmp
    
    WHERE to_tsvector('italian',description) ||
          to_tsvector('italian',CAST(sitar_code as TEXT))||
          to_tsvector('italian',name) ||
          to_tsvector('italian',officer_name)  ||
          to_tsvector('italian',street_name) @@ to_tsquery('italian','$ts_query_keywords')
     
";

$query_pa = "
    SELECT *
    FROM (
    
           SELECT A.id,CONCAT('archaeo_part.',CAST(A.id as TEXT)) as composed_id, CONCAT('Partizione-',A.id) as to_display, 'archaeo_part' as type, 
                ts_headline(COALESCE( NULLIF(A.description,'') , '-' ),to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as description,     
                CAST(A.id as TEXT) as sitar_code, '' as name,
                B.liable_officier as officer_id,
                ts_headline(CONCAT(C.last_name,' ',C.first_name),to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as officer_name,                         
                ts_headline(E.name,to_tsquery('italian','$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as zone_name, 
                '' as street_name,
                B.id as oi_id, B.sitar_code as oi_sitar_code,
                
                
                CAST('' as text) as title, 0 as created_by, CAST('' as text) as created_by_name, now() as created_at,
                0 as license_id, CAST('' as text) as license_name
    
           FROM st_archaeo_part A
             LEFT JOIN st_information_source B ON B.id = A.information_source_id
             LEFT JOIN sf_guard_user C ON C.id = B.liable_officier
    
    
             LEFT JOIN st_information_source_st_circoscrizione D ON D.st_information_source_id = B.id
             LEFT JOIN st_circoscrizione E ON E.id = D.st_circoscrizione_id
    )tmp
    WHERE to_tsvector('italian',description) ||
          to_tsvector('italian',CAST(sitar_code as TEXT))||
          to_tsvector('italian',officer_name)  ||
          to_tsvector('italian',zone_name) @@ to_tsquery('italian','$ts_query_keywords')
";



$query_collection = "
    SELECT *
    FROM(
          SELECT A.id, CONCAT('collection.',CAST(A.id as TEXT)) as composed_id, CONCAT('Collezione-',CAST(A.id as TEXT)) as to_display,
            'collection' as type,
            ts_headline(A.description,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as description,
            ts_headline(CAST(A.id as TEXT),to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as sitar_code,
            '' as name, 0 as officer_id, '' as officer_name, '' as zone_name, '' as street_name, 0 as oi_id, 0 as oi_sitar_code,
            ts_headline(A.title,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as title,
    
                 A.created_by, 
            ts_headline(CONCAT(B.last_name,' ',B.first_name),to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as created_by_name,
    
            A.created_at,A.license_id, 
            ts_headline(C.name,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as license_name
        
    
          FROM kms_collection A
            LEFT JOIN sf_guard_user B ON B.id = A.created_by
            LEFT JOIN kms_license C ON C.id = A.license_id
    ) tmp
    
    WHERE to_tsvector('italian',description) ||
          to_tsvector('italian',sitar_code)||
          to_tsvector('italian',title)||
          to_tsvector('italian',created_by_name)||
          to_tsvector('italian',license_name) @@ to_tsquery('italian','$ts_query_keywords')
";

$query_event = "
    SELECT *
    FROM(
          SELECT A.id, CONCAT('event.',CAST(A.id as TEXT)) as composed_id, CONCAT('Collezione-',CAST(A.id as TEXT)) as to_display,
            'event' as type,
            ts_headline(A.description,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as description,
            ts_headline(CAST(A.id as TEXT),to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as sitar_code,
            '' as name, 0 as officer_id, '' as officer_name, '' as zone_name, '' as street_name, 0 as oi_id, 0 as oi_sitar_code,
            ts_headline(A.title,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as title,
    
                 A.created_by, 
            ts_headline(CONCAT(B.last_name,' ',B.first_name),to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as created_by_name,
    
            A.created_at,A.license_id, 
            ts_headline(C.name,to_tsquery('$ts_query_keywords'),'StartSel=<mark>,StopSel=</mark>,HighlightAll=TRUE') as license_name
        
    
          FROM kms_event A
            LEFT JOIN sf_guard_user B ON B.id = A.created_by
            LEFT JOIN kms_license C ON C.id = A.license_id
    ) tmp
    
    WHERE to_tsvector('italian',description) ||
          to_tsvector('italian',sitar_code)||
          to_tsvector('italian',title)||
          to_tsvector('italian',created_by_name)||
          to_tsvector('italian',license_name) @@ to_tsquery('italian','$ts_query_keywords')
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
    ". ((isset($_GET["search_type"]) && $_GET["search_type"] != "all") ? " WHERE type like '".$_GET["search_type"]."'" : "") . "
    ORDER BY type DESC,sitar_code ASC LIMIT $limit OFFSET $start
");


$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_OBJ);

$arrayResult = array();
$total_count = 0;

foreach($result as $row){
    $total_count = $row->total_count;

    //$row->street_name = ucwords(strtolower($row->street_name)); // riga sostituita dall'uso di LOWER + INITCAP (PGSQL)

    //$row->description = strip_tags($row->description);
    //$row->description = str_ireplace($keywords,"<mark>".$keywords."</mark>",$row->description);//sottolineo le parole cercate con un semplice <mark>

    $row->tooltip = getTooltipInformation($pdo,$row);
    //$row->tooltip = str_ireplace($keywords,"<mark>".$keywords."</mark>",$row->tooltip);//sottolineo le parole cercate con un semplice <mark>

    array_push($arrayResult,$row);
}

//se non ci sono stati risultati ritorno un record
if(count($arrayResult) == 0){
    array_push($arrayResult,array(
        "to_display" => '-NO RESULT-',
        "tooltip" => '<img src="http://www.japanwanted.com/images/noresult.jpg" alt="Mountain View" style="width:100%;height:228px;">'
    ));
}



echo json_encode(array(
	"result" => $arrayResult,
    "total" => $total_count,
    "eventual_error" => $pdo->errorInfo()
));

////////////////////////////////////////////////////////////////////////////////////////////////////////////
function getTooltipInformation($pdo,$record){
    $info_tooltip = "";

    // OI
    if($record->type=="information_source"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><th align='left' width='150' style='color:#2c2c2c;' >Codice Monumento</th><td style='padding: 2px;'>".$record->sitar_code."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Nome</th><td style='padding: 2px;color:#2c2c2c;'>".$record->name."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Funz.Responsabile</th><td style='padding: 2px;'><a href='#user/".$record->officer_id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->officer_name." (#".$record->officer_id.")</u></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Zona</th><td style='padding: 2px;color:#2c2c2c;'>".strip_tags($record->zone_name)."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Indirizzo</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->street_name), 70, "<br>")."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td align='center' style='color:#2c2c2c; text-align:left;'>".getCountOICollections($pdo,$record->id)." Collezioni</td>
                                    <td align='center' style='color:#2c2c2c; text-align:left;'>".getCountOIEvents($pdo,$record->id)." Eventi</td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_file.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>".getCountOIDocs($pdo,$record->id)."</u></a> Documenti</td>      
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_lens.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Anteprima</u></a></td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_map.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Mappa</u></a></td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_tag.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' onclick='CL.app.getController(\"C_tag\").onTag(this,\"$record->type\",$record->id);return false;' style='color: #963232 !important; font-weight: bold;'><u>Tag!</u></a> </td>
                                </tr>
                            </table>
                        </div>";
    }

    // PA
    if($record->type=="archaeo_part"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Codice Partizione</th><td style='padding: 2px;'>".$record->sitar_code."</td></tr>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Monumento di Rif.</th><td style='padding: 2px;'>".$record->oi_sitar_code."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Funz.Responsabile</th><td style='padding: 2px;'><a href='#user/".$record->officer_id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->officer_name." (#".$record->officer_id.")</u></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Zona</th><td style='padding: 2px;color:#2c2c2c;'>".strip_tags($record->zone_name)."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>                                
                                    <td align='center' style='color:#2c2c2c;'>".getCountPACollections($pdo,$record->id)." Collezioni</td>
                                    <td align='center' style='color:#2c2c2c;'>".getCountPAEvents($pdo,$record->id)." Eventi</td>
                                    <td align='center'><img src='images/icons/icon_file.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>".getCountPADocs($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center'><img src='images/icons/icon_lens.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Anteprima</u></a></td>
                                    <td align='center'><img src='images/icons/icon_map.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' style='color: #963232 !important; font-weight: bold;'><u>Mappa</u></a></td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_tag.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' onclick='CL.app.getController(\"C_tag\").onTag(this,\"$record->type\",$record->id);return false;' style='color: #963232 !important; font-weight: bold;'><u>Tag!</u></a> </td>
                                </tr>
                            </table>
                        </div>";
    }

    // USER
    else if($record->type=="user"){
        $info_tooltip = "<table style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <tr>
                                <th align='left' width='150' style='color:#2c2c2c;' >Utente</th>
                                <td width='350'>
                                    <a href='#user/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".strip_tags($record->to_display)." (#".$record->id.")</u></a>                                
                                </td>
                                <td>
                                    <img src='images/icons/icon_tag.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'>                                    
                                    <a href='#' onclick='CL.app.getController(\"C_tag\").onTag(this,\"$record->type\",$record->id);return false;' style='color: #963232 !important; font-weight: bold;'><u>Tag!</u></a>
                                </td>
                            </tr>
                        </table>";
    }

    // COLLECTION
    else if($record->type=="collection"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Codice Collezione</th><td style='padding: 2px;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->sitar_code."</u></a></td></tr>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Titolo</th><td style='padding: 2px;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".wordwrap(str_replace('"',"'",$record->title), 70, "<br>")."</u></a></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Creata Da</th><td style='padding: 2px;'><a href='#user/".$record->created_by."' style='color: #963232 !important; font-weight: bold;'><u>".$record->created_by_name." (#".$record->created_by.")</u></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Creata Il</th><td style='padding: 2px;color:#2c2c2c;'>".date_format(date_create($record->created_at), 'd/m/Y')."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Licenza</th><td style='padding: 2px;color:#2c2c2c;'>".$record->license_name."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/coworkers' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionCoworkers($pdo,$record->id)."</u></a> Collaboratori</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionThreads($pdo,$record->id)."</u></a> Discussioni</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionMessages($pdo,$record->id)."</u></a> Messaggi</td>
                                </tr>
                               <tr>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionFiles($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/external_resources' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionExternalResources($pdo,$record->id)."</u></a> Risorse Esterne</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#collection/".$record->id."/tags' style='color: #963232 !important; font-weight: bold;'><u>".getCountCollectionTags($pdo,$record->id)."</u></a> TAGS</td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_tag.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' onclick='CL.app.getController(\"C_tag\").onTag(this,\"$record->type\",$record->id);return false;' style='color: #963232 !important; font-weight: bold;'><u>Tag!</u></a> </td>
                                </tr>
                            </table>
                        </div>";
    }


    // EVENT
    else if($record->type=="event"){
        $info_tooltip = "<div style='background: white; border-radius: 3px; padding: 10px; width: 100%; border-bottom: 2px inset #afafaf;'>
                            <table>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Codice Evento</th><td style='padding: 2px;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".$record->sitar_code."</u></a></td></tr>
                                <tr><th align='left' width='150'  style='color:#2c2c2c;'>Titolo</th><td style='padding: 2px;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".wordwrap(str_replace('"',"'",$record->title), 70, "<br>")."</u></a></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Descrizione</th><td style='padding: 2px;color:#2c2c2c;'>".wordwrap(str_replace('"',"'",$record->description), 70, "<br>")."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Creato Da</th><td style='padding: 2px;'><a href='#user/".$record->created_by."' style='color: #963232 !important; font-weight: bold;'><u>".$record->created_by_name." (#".$record->created_by.")</u></td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Creato Il</th><td style='padding: 2px;color:#2c2c2c;'>".date_format(date_create($record->created_at), 'd/m/Y')."</td></tr>
                                <tr><th align='left' style='color:#2c2c2c;'>Licenza</th><td style='padding: 2px;color:#2c2c2c;'>".$record->license_name."</td></tr>
                            </table>
                            <br/>
                            <table style='background: #ececec; padding: 10px; width: 100%; border-radius: 2px; border: 1px inset #afafaf;'>
                                <tr>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/coworkers' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventCoworkers($pdo,$record->id)."</u></a> Collaboratori</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventThreads($pdo,$record->id)."</u></a> Discussioni</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/threads' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventMessages($pdo,$record->id)."</u></a> Messaggi</td>
                                </tr>
                               <tr>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventFiles($pdo,$record->id)."</u></a> Documenti</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/external_resources' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventExternalResources($pdo,$record->id)."</u></a> Risorse Esterne</td>
                                    <td align='center' style='color:#2c2c2c;text-align:left;'><a href='#event/".$record->id."/tags' style='color: #963232 !important; font-weight: bold;'><u>".getCountEventTags($pdo,$record->id)."</u></a> TAGS</td>
                                    <td align='center' style='text-align:left;'><img src='images/icons/icon_tag.png' alt=' ' style='width:16px;height:16px;margin-right: 3px;'><a href='#' onclick='CL.app.getController(\"C_tag\").onTag(this,\"$record->type\",$record->id);return false;' style='color: #963232 !important; font-weight: bold;'><u>Tag!</u></a> </td>
                                </tr>
                            </table>
                        </div>";
    }

    return $info_tooltip;
}


////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////




/*
 * COLLECTION
 *
 */


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

/*
 * OI
 *
 */

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


/*
 * PA
 *
 */

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