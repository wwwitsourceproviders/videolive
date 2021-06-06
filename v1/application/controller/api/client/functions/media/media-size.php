<?php

$app->post('/media/size', function ($request, $response, $args) use ($app) {
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
    //The only field required is id and it must be an integer
    try {
        if ($rateLimit->check($decoded_array['profile']->user_id) <= 0) {
            throw new RateExceededException;
        }
        //calculate file size
        $files = scandir(BASE . '/application/media-x212/');
        $size = 0;
        for ($i = 0; $i < count($files); $i++) {
            $file = $files[$i];
            if ($file != "." && $file != "..") {
                $size += filesize(BASE . '/application/media-x212/'.$file);
            }
        }
        echo json_encode(
                array(
                    $api_response,
                    ['size' => $size]
                )
        );
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
