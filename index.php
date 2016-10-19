<?php

require_once __DIR__.'/vendor/autoload.php';

// start a silex app
$app = new Silex\Application();
$app['debug'] = true;

// my instagram consume key
$instagram_client_id = 'CONSUMER_KEY';

// this function performs a reverse geocoding using google maps API
function do_reverse_geo($latitude, $longitude)
{
    // open a new http client
    $maps_client = new GuzzleHttp\Client();

    // join with latitude and longitude parameters
    $maps_url = 'https://maps.googleapis.com/maps/api/geocode/json';
    $param = $latitude .',' . $longitude;

    $maps_response = $maps_client->get(
        $maps_url,
        ['query' => ['latlng' => $param]]
    );
    
    // return consult as json data
    return json_decode($maps_response->getBody());
}

// root directory route
$app->get('/', function () {
    return 'Please point to /media/{media_id}';
});

// media directory route
$app->get('/media/{media_id}', function ($media_id) use ($instagram_client_id) {
    // open a new http client
    $client = new GuzzleHttp\Client();

    // join with media id and instagram api token
    $api_url = 'https://api.instagram.com/v1/media/' . $media_id;
    
    try {
        $api_response = $client->get(
            $api_url,
            ['query' => ['client_id' => $instagram_client_id]]
        );
    } catch (Exception $e) {
        return "Error, you should not push that button :(";
    }
    

    //first, check if the response was succesful
    if ($api_response->getStatusCode() != "200") {
        echo "Error in response";
        return;
    }

    // obtain response (only the body)
    $result = json_decode($api_response->getBody());

    // see response status, and exit if not OK
    $data_pack['STATUS'] = $result->meta->code;
    if ($data_pack['STATUS'] != 200) {
        $http_response_code = array(
            200 => 'OK',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found'
        );
        return 'STATUS '.
            $data_pack['STATUS'].
            ': '.
            $http_response_code[$data_pack['STATUS']];
    }

    // media id is part of data pack
    $data_pack['id'] = $media_id;

    // see media location info
    if ((!is_null($result->data->location))) {
        if ((!is_null($result->data->location->latitude)) and
            (!is_null($result->data->location->longitude))) {
            $data_pack['location']['geopoint']['latitude'] = $result->data->location->latitude;
            $data_pack['location']['geopoint']['longitude'] = $result->data->location->longitude;
        }

        if ((!is_null($result->data->location->name))) {
            $data_pack['location']['place'] = $result->data->location->name;
        }

        // perform a reverse geocoding from geopoints data
        $reverse_location = do_reverse_geo(
            $result->data->location->latitude,
            $result->data->location->longitude
            );
        if ($reverse_location !== -1) {
            if ($reverse_location->status === 'OK') {
                $data_pack['location']['address'] = $reverse_location->results[0]->formatted_address;
            }
        }
    } else {
        $data_pack['location'] = 0;
    }

    // return data assembled
    return json_encode($data_pack);
});

$app->run();
