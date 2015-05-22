<?php

// load Zend classes
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Http_Client');

// This function formats the output and deliver it to the browser
function format_response($format, $api_response) {
 
    // Define HTTP responses
    $http_response_code = array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found'
    );
 
    // Set HTTP Response
    header('HTTP/1.1 '.$api_response['STATUS'].' '.$http_response_code[ $api_response['STATUS'] ]);

    if ($format === 'json') {
      header('Content-Type: application/json; charset=utf-8'); // HTTP json response content type
      $response = json_encode($api_response);
    } else {
      // xml response type ...
      header('Content-Type: application/xml; charset=utf-8');  // HTTP xml response content type
      $response = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<response>'."\n".
            "\t".'<id>'.$api_response['id'].'</id>'."\n";

      if ($api_response['location'] == 0)
        $response .= '</response>';
      else {
        $response .=  "\t".'<location>'."\n".
                      "\t"."\t".'<geopoint>'."\n".
                      "\t"."\t"."\t".'<latitude>'.$api_response['location']['geopoint']['latitude'].'</latitude>'."\n".
                      "\t"."\t"."\t".'<longitude>'.$api_response['location']['geopoint']['longitude'].'</longitude>'."\n".
                      "\t"."\t".'</geopoint>'."\n";
        if (isset($api_response['location']['place']))
          $response .= "\t".'<place>'.$api_response['location']['place'].'</place>'."\n";
        $response .= "\t".'</location>'."\n".
                      '</response>';
      }

    }

    echo $response;
}

// Look if a media id was specified
$image = isset($_GET['media']) ? $_GET['media'] : null;

// My Instagram API consumer key
$CLIENT_ID = '7e1d9eb5f97c4f89a170e43ce6fff285';
//$CLIENT_SECRET = '7ec92ff1b3eb4908bb3d61231ba52dbe';

if (is_null($image)) {
  exit('You must specify Instagram media as "media=MEDIA_ID"');
}

// Look if a response type was specified, if not apply default format
$format = isset($_GET['format']) ? $_GET['format'] : 'json';

// If the response is different from a specified, apply default ... 
if(($format !== 'json') and ($format !== 'xml')) {
  $format = 'json';
}

try {
  	// initialize client
  	$client = new Zend_Http_Client('https://api.instagram.com/v1/media/' . $image);
  	$client->setParameterGet('client_id', $CLIENT_ID);

  	// get image metadata
  	$req_response = $client->request();
  	$result = json_decode($req_response->getBody());
  
  	$data_pack['STATUS'] = $result->meta->code;
    $data_pack['id'] = $image;

    if ((!is_null($result->data->location))) {
      if((!is_null($result->data->location->latitude)) and (!is_null($result->data->location->longitude))) {
        $data_pack['location']['geopoint']['latitude'] = $result->data->location->latitude;
        $data_pack['location']['geopoint']['longitude'] = $result->data->location->longitude;
      }

      if((!is_null($result->data->location->name))) {
        $data_pack['location']['place'] = $result->data->location->name;
      }
    }
    else
      $data_pack['location'] = 0; 

    format_response($format, $data_pack);
    
} catch (Exception $e) {
      echo 'ERROR: ' . $e->getMessage() . print_r($client);
      exit;
}

?>