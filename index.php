<?php
$actions['login-with-google-play'] = function($params, $postBody) {
    // Google Play login type is 11
    $loginType = 11;
    $idToken = $postBody['idToken'];
    $url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=".$idToken;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($content, true);
    $email = $data["email"];

    $output = array('error' => '');
    if (empty($email)) {
        $output['error'] = 'ERROR_EMPTY_USERNAME_OR_PASSWORD';
    } else {
        if (!IsPlayerWithUsernameFound($loginType, $email)) {
            // Make new player if not existed
            InsertNewPlayer($loginType, $email, '');
        }
        $playerAuthDb = new PlayerAuth();
        $playerAuth = $playerAuthDb->findone(array(
            'username = ? AND type = ?',
            $email,
            $loginType
        ));
        $playerDb = new Player();
        $player = $playerDb->load(array(
            'id = ?',
            $playerAuth->playerId
        ));
        $player = UpdatePlayerLoginToken($player);
        UpdateAllPlayerStamina($player->id);
        $output['player'] = CursorToArray($player);
    }
    echo json_encode($output);
};

if (!\Base::instance()->get('enable_action_request_query')) {
    $f3->route('POST /login-with-google-play', function($f3, $params) {
        DoPostAction('login-with-google-play', $f3, $params);
    });
}

?>
