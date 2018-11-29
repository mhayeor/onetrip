<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
	$allFlights = array();
	$fly_from = "";
	$latitude = Input::get('lat');
	$longitude = Input::get('long');
	$users = DB::select('SELECT * FROM flight WHERE code IS NOT NULL ORDER BY ((latit-'.$latitude.')*(latit-'.$latitude.')) + ((longit - '.$longitude.')*(longit - '.$longitude.')) ASC LIMIT 3');
	foreach ($users as $value) {
		if (strpos($fly_from, $value->code) === false) {
			$fly_from = $value->code.",".$fly_from;
		}
	}
	$fly_from = rtrim($fly_from, ',');
	$fly_from = 'LTN,STN,BHX';
	$users = DB::select('SELECT airport_id FROM bus_flight');
	$fly_to = "";
	foreach ($users as $value) {
		if (strpos($fly_to, $value->airport_id) === false) {
			$fly_to = $value->airport_id.",".$fly_to;
		}
		// echo $value->airport_id;
	}
	$departure_date = Input::get('departure_date');
	$return_date = Input::get('return_date');
	$fly_to = rtrim($fly_to, ',');
	$client = new GuzzleHttp\Client();
	$res = $client->get('https://api.skypicker.com/flights?fly_from='.$fly_from.'&fly_to='.$fly_to.'&date_from='.$departure_date.'&date_to='.$departure_date.'&return_from='.$return_date.'&return_to='.$return_date.'');
	// echo $res->getStatusCode(); // 200
	// echo $res->getBody();
	$response = json_decode($res->getBody(), true);
	foreach ($response['data'] as $value) {
		if ($value['route'][0]['flyTo'] == $value['route'][1]['flyFrom'] && $value['route'][1]['flyTo'] == $value['route'][0]['flyFrom']) {
			$a = $value['route'][0]['flyFrom'];
			$b = $value['route'][0]['flyTo'];
			$d = $value['route'][1]['flyFrom'];
			$e = $value['route'][1]['flyTo'];
			$arrival = date('Y-m-d H:i:s', $value['route'][0]['aTimeUTC']);
			$departure = date('Y-m-d H:i:s', $value['route'][1]['dTimeUTC']);
		      // $buses = DB::table('bus_flight')->groupBy('bus_id')->where('airport_id', $b)->get();
			$buses = DB::select("SELECT DISTINCT bus_id FROM bus_flight WHERE airport_id='$b'");
			foreach ($buses as $bus) {
				$b_c_trip = DB::select("SELECT * FROM bus_trips WHERE origin_id='$bus->bus_id' AND (TIMESTAMPDIFF(MINUTE, '$arrival', departure) >= 60) AND (TIMESTAMPDIFF(MINUTE, '$arrival', departure) <= 240) AND EXISTS (SELECT scenic_id FROM destinations WHERE destinations.scenic_id = bus_trips.destination_id) GROUP BY destination_id");
				foreach ($b_c_trip as $b_c) {
					# code...
					$c_b_trip = DB::select("SELECT *, TIMESTAMPDIFF(MINUTE, arrival, '$departure') AS diff FROM bus_trips WHERE origin_id='$b_c->destination_id' AND destination_id='$bus->bus_id' AND (TIMESTAMPDIFF(MINUTE, arrival, '$departure') >= 90) AND (TIMESTAMPDIFF(MINUTE, arrival, '$departure') <= 300) GROUP BY destination_id LIMIT 1");
					foreach ($c_b_trip as $c_b) {
						$flight_det = array(
							'o_flight_from' => $value['route'][0]['flyFrom'],
							'o_flight_code_from' => $value['route'][0]['flyFrom'],
							'o_flight_to' => $value['route'][0]['flyTo'],
							'o_flight_code_to' => $value['route'][0]['flyTo'],
							'o_flight_depart' => date('Y-m-d H:i:s', $value['route'][0]['dTimeUTC']),
							'o_flight_arrive' => date('Y-m-d H:i:s', $value['route'][0]['aTimeUTC']),
							'o_flight_price_cents' => $value['price'],
							'o_bus_from' => $b_c->origin_id,
							'o_bus_code_from' => $b_c->origin_id,
							'o_bus_to' => $b_c->destination_id,
							'o_bus_code_to' => $b_c->destination_id,
							'o_bus_depart' => $b_c->departure,
							'o_bus_arrive' => $b_c->arrival,
							'o_bus_price_cents' => $b_c->price_cents,
							'i_flight_from' => $value['route'][1]['flyFrom'],
							'i_flight_code_from' => $value['route'][1]['flyFrom'],
							'i_flight_to' => $value['route'][1]['flyTo'],
							'i_flight_code_to' => $value['route'][1]['flyTo'],
							'i_flight_depart' => date('Y-m-d H:i:s', $value['route'][1]['dTimeUTC']),
							'i_flight_arrive' => date('Y-m-d H:i:s', $value['route'][1]['aTimeUTC']),
							'i_flight_price_cents' => $value['price'],
							'i_bus_from' => $c_b->origin_id,
							'i_bus_code_from' => $c_b->origin_id,
							'i_bus_to' => $c_b->destination_id,
							'i_bus_code_to' => $c_b->destination_id,
							'i_bus_depart' => $c_b->departure,
							'i_bus_arrive' => $c_b->arrival,
							'i_bus_price_cents' => $c_b->price_cents,
						);
						array_push($allFlights, $flight_det);
						// echo json_encode($flight_det);
						$o_flight_from = $value['route'][0]['flyFrom'];
						$o_flight_code_from = $value['route'][0]['flyFrom'];
						$o_flight_to = $value['route'][0]['flyTo'];
						$o_flight_code_to = $value['route'][0]['flyTo'];
						$o_flight_depart = $value['route'][0]['dTimeUTC'];
						$o_flight_arrive = $value['route'][0]['aTimeUTC'];
						$o_flight_price_cents = $value['price'];
						$o_bus_from = $b_c->origin_id;
						$o_bus_code_from = $b_c->origin_id;
						$o_bus_to = $b_c->origin_id;
						$o_bus_code_to = $b_c->destination_id;
						$o_bus_depart = $b_c->departure;
						$o_bus_arrive = $b_c->arrival;
						$o_bus_price_cents = $b_c->arrival;

						$i_flight_from = $value['route'][1]['flyFrom'];
						$i_flight_code_from = $value['route'][1]['flyFrom'];
						$i_flight_to = $value['route'][1]['flyTo'];
						$i_flight_code_to = $value['route'][1]['flyTo'];
						$i_flight_depart = $value['route'][1]['dTimeUTC'];
						$i_flight_arrive = $value['route'][1]['aTimeUTC'];
						$i_flight_price_cents = $value['price'];
						$i_bus_from = $c_b->origin_id;
						$i_bus_code_from = $c_b->origin_id;
						$i_bus_to = $c_b->origin_id;
						$i_bus_code_to = $c_b->destination_id;
						$i_bus_depart = $c_b->departure;
						$i_bus_arrive = $c_b->arrival;
						$i_bus_price_cents = $c_b->arrival;

					}
				}
			}
		}
	}
	// print_r($allFlights);
					    return response($allFlights, 200)
					                  ->header('Content-Type', 'application/json');
});
Route::get('/bus_stops', function () {
	$client = new GuzzleHttp\Client();
	$res = $client->get('https://api.idbus.com/v1/stops', [
		'headers' => [
			'Authorization' => 'Token x-PhrUGIwjZNiD53ZJHcyw'
        // 'Accept'     => 'application/json'
		]
	]);
	// echo $res->getStatusCode(); // 200
	// echo $res->getBody();
	$response = json_decode($res->getBody(), true);
	DB::table('bus_locations')->truncate();

	foreach ($response as $key => $value) {
		foreach ($value as $ikey => $ivalue) {
			$id = $ivalue['id'];
			$carrier_id = $ivalue['_carrier_id'];
			$short_name = $ivalue['short_name'];
			$long_name = $ivalue['long_name'];
			$latitude = $ivalue['latitude'];
			$longitude = $ivalue['longitude'];
			$destinations = "";
			foreach ($ivalue['destinations_ids'] as $mkey => $mvalue) {
				$destinations = $destinations.$mvalue.", ";
			}
			DB::insert('insert into bus_locations (id, carrier_id, short_name, long_name, latitude, longitude, destinations) values (?, ?, ?, ?, ?, ?, ?)', [$id, $carrier_id, $short_name, $long_name, $latitude, $longitude, $destinations]);
			// echo $ivalue['id'];
		}
	}
	echo "done";
	// $users = DB::select('select * from bus_locations');
	// echo $users;

});
Route::get('/bus_trips', function () {
	$today = date("Y-m-d");
	$till = date("Y-m-d", strtotime("+1 month", strtotime($today)));
	$client = new GuzzleHttp\Client();
	$res = $client->get('https://api.idbus.com/v1/fares?start_date='.$today.'&end_date='.$till.'', [
		'headers' => [
			'Authorization' => 'Token x-PhrUGIwjZNiD53ZJHcyw'
        // 'Accept'     => 'application/json'
		]
	]);
	// echo $res->getStatusCode(); // 200
	// echo $res->getBody();
	$response = json_decode($res->getBody(), true);
	// DB::table('bus_locations')->truncate();
	DB::table('bus_trips')->truncate();

	// echo $res->getBody();
	foreach ($response as $key => $value) {
		foreach ($value as $ikey => $ivalue) {
			$arrival = date('Y-m-d H:i:s', strtotime($ivalue['arrival']));
			$departure = date('Y-m-d H:i:s', strtotime($ivalue['departure']));
			$origin_id = $ivalue['origin_id'];
			$destination_id = $ivalue['destination_id'];
			if ($ivalue['price_cents'] == "") {
				$price_cents = 0;
			} else {
				$price_cents = $ivalue['price_cents'];
			}
			$updated_at = date('Y-m-d H:i:s', strtotime($ivalue['updated_at']));
			foreach ($ivalue['legs'] as $lkey => $lvalue) {
				$legs = implode("|", $lvalue);
			}
			DB::insert('insert into bus_trips (origin_id, destination_id, departure, arrival, price_cents, updated_at, legs) values (?, ?, ?, ?, ?, ?, ?)', [$origin_id, $destination_id, $departure, $arrival, $price_cents, $updated_at, $legs]);
		}
	}
	// foreach ($response as $key => $value) {
	// 	foreach ($value as $ikey => $ivalue) {
	// 		$id = $ivalue['id'];
	// 		$carrier_id = $ivalue['_carrier_id'];
	// 		$short_name = $ivalue['short_name'];
	// 		$long_name = $ivalue['long_name'];
	// 		$latitude = $ivalue['latitude'];
	// 		$longitude = $ivalue['longitude'];
	// 		$destinations = "";
	// 		foreach ($ivalue['destinations_ids'] as $mkey => $mvalue) {
	// 			$destinations = $destinations.$mvalue.", ";
	// 		}
	// 		DB::insert('insert into bus_locations (id, carrier_id, short_name, long_name, latitude, longitude, destinations) values (?, ?, ?, ?, ?, ?, ?)', [$id, $carrier_id, $short_name, $long_name, $latitude, $longitude, $destinations]);
	// 		// echo $ivalue['id'];
	// 	}
	// }
	// echo "done";
	// $users = DB::select('select * from bus_locations');
	// echo $users;

});
