<?php
ignore_user_abort(true);
set_time_limit(0);
$app->post('/media/convert/audio', function ($request, $response, $args) use ($app) {
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
    //The only field required is id and it must be an integer
    try {
        if ($rateLimit->check($decoded_array['profile']->user_id) <= 0) {
            throw new RateExceededException;
        }

        $name = $request->getParam('name');
        $type = empty($request->getParam('type')) ? 0 : intval($request->getParam('type'));
        $container['violin']->validate([
            'name' => [$name, 'required|min(2)|max(255)']
        ]);
        if ($container['violin']->passes()) {
            //copy file
            if (file_exists(BASE . '/application/temp-x212/' . $decoded_array['profile']->user_id . '/' . $name)) {
                copy(BASE . '/application/temp-x212/' . $decoded_array['profile']->user_id . '/' . $name, BASE . '/application/media-x212/' . 'temp-' . $name);
                unlink(BASE . '/application/temp-x212/' . $decoded_array['profile']->user_id . '/' . $name);

                $pieces = explode('.', strtolower($name));
                $ffmpeg = FFMpeg\FFMpeg::create(array(
                            'timeout' => 0
                ));
                $ffprobe = FFMpeg\FFProbe::create(array(
                            'timeout' => 0
                ));
                $extract = $ffprobe
                        ->format(BASE . '/application/media-x212/' . 'temp-' . $name);
                $duration = $extract->get('duration');
                $bitrate = $extract->get('bit_rate');
                if ($bitrate > 1200000) {
                    unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    $api_response['messages'][] = ["bitrate" => "bitrate too high (".$bitrate.")"];
                    $api_response['errorcode'] = 911;
                    $api_response['success'] = 0;
                    echo json_encode(array($api_response
                    ));
                    exit();
                }
                //first check if video or audio
                //change bitrate,convert then rename
                if (
                        $bitrate != 128000 || $pieces[count($pieces) - 1] != 'mp3'
                ) {
                    $audio = $ffmpeg->open(BASE . '/application/media-x212/' . 'temp-' . $name);

                    $format = new FFMpeg\Format\Audio\Mp3();
                    $format->setAudioKiloBitrate(128)->setAudioChannels(2);
                    $audio->save($format, BASE . '/application/media-x212/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    //save sample
                    $audio = $ffmpeg->open(BASE . '/application/media-x212/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    $clip = $audio->filters()->clip(FFMpeg\Coordinate\TimeCode::fromSeconds(0), FFMpeg\Coordinate\TimeCode::fromSeconds(min($duration, 30)));
                    $audio->save(new FFMpeg\Format\Audio\Mp3(), BASE . '/application/media-x212/sample/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    //delete temp files
                    if (file_exists(BASE . '/application/media-x212/' . 'temp-' . $name)) {
                        unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    }
                }
                //rename file
                else {
                    //save sample
                    copy(BASE . '/application/media-x212/' . 'temp-' . $name, BASE . '/application/media-x212/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    $audio = $ffmpeg->open(BASE . '/application/media-x212/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    $clip = $audio->filters()->clip(FFMpeg\Coordinate\TimeCode::fromSeconds(0), FFMpeg\Coordinate\TimeCode::fromSeconds(min($duration, 30)));
                    $audio->save(new FFMpeg\Format\Audio\Mp3(), BASE . '/application/media-x212/sample/' . str_replace('.' . $pieces[count($pieces) - 1], '.mp3', $name));
                    //delete temp files
                    if (file_exists(BASE . '/application/media-x212/' . 'temp-' . $name)) {
                        unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    }
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
    } catch (ServiceException $e) {
        $arr = json_decode($e->getMessage(), true);
        $arr['errorcode'] = $e->getCode();
        echo json_encode(array(
            $arr
        ));
    } catch (Exception $e) {
        $api_response['messages'][] = ["all" => $e->getMessage()];
        $api_response['errorcode'] = 911;
        $api_response['success'] = 0;
        echo json_encode(array($api_response
        ));
    } catch (Error $e) {
        $api_response['messages'][] = ["all" => $e->getMessage()];
        $api_response['errorcode'] = 911;
        $api_response['success'] = 0;
        echo json_encode(array($api_response
        ));
    }
    exit();
});
