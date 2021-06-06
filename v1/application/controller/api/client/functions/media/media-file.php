<?php

$app->post('/media/file', function ($request, $response, $args) use ($app) {
    /*
      Rate limiter
     */
    $api_response = array(
        'errorcode' => 0,
        'messages' => array(),
        'success' => 1,
        'id' => 0,
    );

    $container = $app->getContainer();
    $decoded_array = [];
    try {
        $publicKey = file_get_contents(BASE . '/application/keys-y348/public.pem');
        $TOKEN = !empty($request->getHeader('TOKEN')[0]) ? $request->getHeader('TOKEN')[0] : '';

        $decoded = \Firebase\JWT\JWT::decode($TOKEN, $publicKey, array('RS256'));
        $decoded_array = (array) $decoded;
    } catch (Exception $e) { // Also tried JwtException
        header('HTTP/1.1 400 Bad Request', true, 400);
        exit();
    } catch (Error $e) { // Also tried JwtException
        header('HTTP/1.1 400 Bad Request', true, 400);
        exit();
    }
    if (intval($decoded_array['role_id']) < 3) {
        header('HTTP/1.1 400 Bad Request', true, 400);
        exit();
    }
    $adapter = new \Touhonoob\RateLimit\Adapter\APCu(); // Use APC as Storage
    $rateLimit = new \Touhonoob\RateLimit\RateLimit($decoded_array['profile']->user_id, 100000, 5, $adapter); // 100 Requests / Hour

    try {
        if ($rateLimit->check($decoded_array['profile']->user_id) <= 0) {
            throw new RateExceededException;
        }
        $upload_handler = new UploadHandler(
                array(
            'upload_dir' => BASE . '/application/temp-x212/' . $decoded_array['profile']->user_id . '/',
            'accept_file_types' => '/\.(mp4|webm|mp3|avi|ogg|mkv)$/i',
            'max_file_size' => 250 * 1024 * 1024,
            'param_name' => 'file',
            'print_response' => false,
            'access_control_allow_methods' => array(
                'POST',
            ),
                )
        );
        echo json_encode(array(
            $api_response
        ));
    } catch (RateExceededException $e) {
        header("HTTP/1.0 529 Too Many Requests");
    } catch (MysqldbException$e) {
        $api_response['messages'][] = ["all" => "You did something very bad."];
        $api_response['errorcode'] = 911;
        $api_response['success'] = 0;
        echo json_encode(array($api_response
        ));
    } catch (ServiceException $e) {
        $arr = json_decode($e->getMessage(), true);
        $arr['errorcode'] = $e->getCode();
        echo json_encode(array(
            $arr
        ));
    } catch (Exception $e) {
        $api_response['messages'][] = ["all" => "You did something very bad."];
        $api_response['errorcode'] = 911;
        $api_response['success'] = 0;
        echo json_encode(array($api_response
        ));
    } catch (Error $e) {
        $api_response['messages'][] = ["all" => "You did something very bad."];
        $api_response['errorcode'] = 911;
        $api_response['success'] = 0;
        echo json_encode(array($api_response
        ));
    }
    exit();
});
