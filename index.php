<?php
$accessToken = 'Yr92ww5KguLEYnhF+4tYegWq4yweGCxzL5MXGvGc5nfu5w7HMeHC1jCXVbWxYNLEvp6Mcswp79rEm3oJRN9m/pwWYuOhAdqmG/cpnb9/GC2jlrsKzZVD3Esx/6ziqcLj7Vv7MjqWJSjZmuP4mMJf8wdB04t89/1O/w1cDnyilFU=';
//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$json_object = json_decode($json_string);

//取得データ
$replyToken = $json_object->{"events"}[0]->{"replyToken"};        //返信用トークン
$message_type = $json_object->{"events"}[0]->{"message"}->{"type"};    //メッセージタイプ
$message_latitude = $json_object->{"events"}[0]->{"message"}->{"latitude"};    //メッセージ内容
$message_longitude = $json_object->{"events"}[0]->{"message"}->{"longitude"};    //メッセージ内容
$total = $message_latitude + $message_longitude;

//メッセージタイプが「text」以外のときは何も返さず終了
if ($message_type != "location") exit;
	try {
		$dbh = new PDO('mysql:host=localhost;dbname=procir_ichikawa358', 'ichikawa358', 'etkb79titp');
	} catch (PDOException $e) {
		echo 'DB接続エラー：' . $e->getMessage();
		exit();
	}

$stmt = $dbh->query("select * from yamanote_lines order by abs(latitude + longitude - $total) ASC limit 1");
$nearly_station = $stmt->fetch();
$nearly_station_id = $nearly_station['id'];

$stmt_2 = $dbh->query("select * from yamanote_lines inner join stores on yamanote_lines.id = stores.yamanote_line_id where yamanote_line_id = $nearly_station_id");
$stmt_2->setFetchMode(PDO::FETCH_ASSOC);
$shops = $stmt_2->fetchAll();
shuffle($shops);


//返信実行
sending_messages($accessToken, $replyToken, $shops);

//星の画像設定
function getStar($rating, $int) {
	if ($rating >= $int) {
		return 'https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png';
	}
	return 'https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gray_star_28.png';
}

function getLatLng($get_address) {
	$query = $get_address;
	$query = urlencode($query);
	$url = "http://www.geocoding.jp/api/";
	$url.= "?v=1.1&q=". $query;

	$fp = fopen($url, "r");
	while (!feof($fp)) {
		  $line.= fgets($fp);
	}
	fclose($fp);

	$xml = simplexml_load_string($line);
	$lat = $xml->coordinate->lat;
	$lng = $xml->coordinate->lng;
	return 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;

}

//メッセージの送信
function sending_messages($accessToken, $replyToken, $shops) {
	$review_0 = $shops[0]['review'];
	$review_1 = $shops[1]['review'];
	$address_0 = $shops[0]['address'];
	$address_1 = $shops[1]['address'];

	$response_format_text = [
		'type' => 'flex',
		'altText' => 'This is a flex message',
		'contents' => [
			'type' => 'carousel',
			'contents' => [
				[
					'type' => 'bubble',
					'size' => 'micro',
					'hero' => [
						'type' => 'image',
						'url' => $shops[0]['image'],
						'size' => 'full',
						'aspectMode' => 'cover',
						'aspectRatio' => '320:213',
					],
					'body' => [
						'type' => 'box',
						'layout' => 'vertical',
						'contents' => [
							[
								'type' => 'text',
								'text' => $shops[0]['shop_name'],
								'weight' => 'bold',
								'size' => 'sm',
								'wrap' => true,
							],
							[
								'type' => 'box',
								'layout' => 'baseline',
								'contents' => [
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_0, 1)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_0, 2)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_0, 3)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_0, 4)
									],
									[
										'type' => 'text',
										'text' => $shops[0]['review'] . '.0',
										'size' => 'xs',
										'color' => '#8c8c8c',
										'margin' => 'md',
										'flex' => 0,
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => $shops[0]['category'],
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => '営業日:' . $shops[0]['workday'],
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => '最寄駅: ' . $shops[0]['name'] . '駅',
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
						],
						'spacing' => 'sm',
						'paddingAll' => '13px',
					],
					'footer' => [
						'type' => 'box',
						'layout' => 'vertical',
						'contents' => [
							[
								'type' => 'button',
								'style' => 'primary',
								'action' => [
									'type' => 'uri',
									'label' => 'Google Map',
									'uri' => getLatLng($address_0),
								]
							]
						]
					],
				],
				[
					'type' => 'bubble',
					'size' => 'micro',
					'hero' => [
						'type' => 'image',
						'url' => $shops[1]['image'],
						'size' => 'full',
						'aspectMode' => 'cover',
						'aspectRatio' => '320:213',
					],
					'body' => [
						'type' => 'box',
						'layout' => 'vertical',
						'contents' => [
							[
								'type' => 'text',
								'text' => $shops[1]['shop_name'],
								'weight' => 'bold',
								'size' => 'sm',
							],
							[
								'type' => 'box',
								'layout' => 'baseline',
								'contents' => [
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_1, 1)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_1, 2)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_1, 3)
									],
									[
										'type' => 'icon',
										'size' => 'xs',
										'url' => getStar($review_1, 4)
									],
									[
										'type' => 'text',
										'text' => $shops[1]['review']. '.0',
										'size' => 'sm',
										'color' => '#8c8c8c',
										'margin' => 'md',
										'flex' => 0,
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => $shops[1]['category'],
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => '営業日:' . $shops[1]['workday'],
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
							[
								'type' => 'box',
								'layout' => 'vertical',
								'contents' => [
									[
										'type' => 'box',
										'layout' => 'baseline',
										'spacing' => 'sm',
										'contents' => [
											[
												'type' => 'text',
												'text' => '最寄駅: ' . $shops[1]['name'] . '駅',
												'wrap' => true,
												'color' => '#8c8c8c',
												'size' => 'xs',
												'flex' => 5,
											]
										]
									]
								]
							],
						],
						'spacing' => 'sm',
						'paddingAll' => '13px',
					],
					'footer' => [
						'type' => 'box',
						'layout' => 'vertical',
						'contents' => [
							[
								'type' => 'button',
								'style' => 'primary',
								'action' => [
									'type' => 'uri',
									'label' => 'Google Map',
									'uri' => getLatLng($address_1),
								]
							]
						]
					],
				]
			]
		]
	];

	//ポストデータ
	$post_data = [
		"replyToken" => $replyToken,
		"messages" => [$response_format_text]
	];

	//curl実行
	$ch = curl_init("https://api.line.me/v2/bot/message/reply");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charser=UTF-8',
		'Authorization: Bearer ' . $accessToken
	));
	$result = curl_exec($ch);
	curl_close($ch);
}
?>

