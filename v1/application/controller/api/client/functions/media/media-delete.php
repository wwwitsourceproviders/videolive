<?php

$app->post('/media/delete', function ($request, $response, $args) use ($app) {
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

        $name = $request->getParam('name');
        $name = preg_replace('/^temp\-/', '', $name);
        $container['violin']->validate([
            'name' => [$name, 'required|min(2)|max(255)']
        ]);
        if ($container['violin']->passes()) {
            //copy file
            if (file_exists(BASE . '/application/media-x212/' . $name)) {
                ignore_user_abort(true);
                if (file_exists(BASE . '/application/temp-x212/' . $name)) {
                    unlink(BASE . '/application/media-x212/' . $name);
                }
                if (file_exists(BASE . '/application/media-x212/' . $name)) {
                    unlink(BASE . '/application/media-x212/' . $name);
                }
                if (file_exists(BASE . '/application/media-x212/sample/' . $name)) {
                    unlink(BASE . '/application/media-x212/sample/' . $name);
                }
                echo json_encode(
                        array(
                            $api_response
                        )
                );
                exit();
            } else {
                $api_response['messages'][] = ["file" => 'file not found'];
                $api_response['success'] = 0;
                echo json_encode(
                        array(
                            $api_response
                        )
                );
                exit();
            }
        } else {
            //show the user what he did wrong
            $api_response['success'] = 0;
            $keys = $container['violin']->errors()->keys();
            $key_length = count($keys);
            for ($i = 0; $i < $key_length; $i++) {
                $key = $keys[$i];
                $api_response['messages'][] = array($key => $container['violin']->errors()->get($key)[0]);
            }
            echo json_encode(array(
                $api_response,
            ));
        }
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
