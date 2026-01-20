<?php

    $params=array(
        'token' => 'y44cc2gpjixxbdda',
        'to' => '+52'.$telefono,
        'image' => ''.$header,
        'caption' => ''.$body,
        'priority' => '10',
        'referenceId' => '',
        'nocache' => '1',
        'msgId' => '',
        'mentions' => ''
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ultramsg.com/instance78622/messages/image",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => array(
            "content-type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    // if ($err) {
    //     echo "cURL Error #:" . $err;
    // } else {
    //     echo $response;
    // }









    $params=array(
        'token' => 'y44cc2gpjixxbdda',
        'to' => '+52'.$telefono,
        'image' => 'https://universal.clase.digital/data/qr/'.$codigo.'.png',
        'caption' => ''.$codigo,
        'priority' => '10',
        'referenceId' => '',
        'nocache' => '1',
        'msgId' => '',
        'mentions' => ''
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ultramsg.com/instance78622/messages/image",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => array(
            "content-type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    // if ($err) {
    //     echo "cURL Error #:" . $err;
    // } else {
    //     echo $response;
    // }


    $params=array(
    'token' => 'y44cc2gpjixxbdda',
    'to' => '+52'.$telefono,
    'filename' => 'Acceso.pdf',
    'document' => 'https://universal.clase.digital/registro/imprimir/'.$codigo.'/'.$id,
    'caption' => ''.$codigo,
    'priority' => '10',
    'referenceId' => '',
    'nocache' => '1',
    'msgId' => '',
    'mentions' => ''
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.ultramsg.com/instance78622/messages/document",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_HTTPHEADER => array(
        "content-type: application/x-www-form-urlencoded"
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    // if ($err) {
    // echo "cURL Error #:" . $err;
    // } else {
    // echo $response;
    // }

?>