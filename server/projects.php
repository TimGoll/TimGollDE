<?php

// GLOBAL VARIABLES
$projects_path = '../data/projects/*';

// HANDLING
$data = json_decode($_POST['data']);
$action = $data->action;

if ($action == 'request_project_list') {
    if (isset($data->type))
        $type = $data->type;
    else
        $type = '__GENERAL__';

    // read all files
    $file_list = glob($projects_path);
    
    //process files
    $answer = [];
    foreach($file_list as $file) {
        $file_split = explode('/', $file);
        $file_id = explode('.', end($file_split))[0];

        $file_contents = json_decode(file_get_contents($file));

        $answer[$file_id] = [];
        $answer[$file_id]['path'] = substr($file, 3);
        $answer[$file_id]['topics'] = isset($file_contents->topics) ? $file_contents->topics : NULL;
        $answer[$file_id]['date'] = isset($file_contents->date) ? $file_contents->date : NULL;
        $answer[$file_id]['title'] = isset($file_contents->title) ? $file_contents->title : NULL;
        $answer[$file_id]['intro'] = isset($file_contents->intro) ? $file_contents->intro : NULL;
    }
    echo json_encode($answer);
}

?>