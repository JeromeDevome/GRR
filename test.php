<?php

$url = "https://api.github.com/repos/JeromeDevome/GRR/releases/latest";
$opts = [
        'http' => [
                'method' => 'GET',
                'header' => [
                        'User-Agent: PHP'
                ]
        ]
];

$ctx = stream_context_create($opts);

$json = @file_get_contents( $url, 0, $ctx );

if($json === FALSE) {
 echo (" Jolie ");
} else{

$myObj = json_decode($json);

echo 'The tag name is ' . $myObj->tag_name .' '. $myObj->published_at;
echo'<br><br>';
echo $json;
}