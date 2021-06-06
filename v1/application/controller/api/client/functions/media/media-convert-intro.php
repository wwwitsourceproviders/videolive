<?php
ignore_user_abort(true);
set_time_limit(0);
$app->post('/media/convert/intro', function ($request, $response, $args) use ($app) {
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
    if (intval($decoded_array['role_id']) < 4) {
        header('HTTP/1.1 400 Bad Request', true, 400);
        exit();
    }

    /*
      NOTE: This will now be an object instead of an associative array. To get
      an associative array, you will need to cast it as such:
     */


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
                
                if ($bitrate > 2500000) {
                    unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    $api_response['messages'][] = ["bitrate" => "bitrate too high (".$bitrate.")"];
                    $api_response['errorcode'] = 911;
                    $api_response['success'] = 0;
                    echo json_encode(array($api_response
                    ));
                    exit();
                }
                $expectedbitrate = $bitrate > 2100000 ? 2100000 : $bitrate;
                //first check if video or audio
                //audio
                if ($type == 0) {
                    //change bitrate,convert then rename
                    if (
                            $bitrate != 128000
                    ) {
                        $audio = $ffmpeg->open(BASE . '/application/media-x212/' . 'temp-' . $name);

                        $format = new FFMpeg\Format\Audio\Mp3();
                        $format->setAudioKiloBitrate(128);
                        $audio->save($format, BASE . '/application/intro/audio.mp3');
                        unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    }
                    //rename file
                    else {
                        copy(BASE . '/application/media-x212/' . 'temp-' . $name, BASE . '/application/intro/audio.mp3');
                        unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                    }
                }
                //video
                else {
                    $dimension = $ffprobe
                            ->streams(BASE . '/application/media-x212/' . 'temp-' . $name)   // extracts streams informations
                            ->videos()                      // filters video streams
                            ->first()                       // returns the first video stream
                            ->getDimensions();
                    $width = $dimension->getWidth();
                    //only convert to mp4
                    if (
                            $bitrate <= $expectedbitrate &&
                            $width <= 720 &&
                            $pieces[count($pieces) - 1] != 'mp4'
                    ) {
                        $video = $ffmpeg->open(BASE . '/application/media-x212/' . 'temp-' . $name);
                        $format = new FFMpeg\Format\Video\X264();
                        $format->setAudioCodec("libmp3lame");
                        $video->save($format, BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)));

                        //speed up
                        exec('qt-faststart '
                                . BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name))
                                . ' '
                                . BASE . '/application/intro/video.mp4'
                        );
                        if (!file_exists(BASE . '/application/media-x212/' . (str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)))) {
                            copy(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)), BASE . '/application/intro/video.mp4');
                        }
                        if (file_exists(BASE . '/application/media-x212/' . 'temp-' . $name)) {
                            unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                        }
                        if (file_exists(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)))) {
                            unlink(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)));
                        }
                    } else if (
                            $bitrate > $expectedbitrate ||
                            $width > 720
                    ) {
                        $video = $ffmpeg->open(BASE . '/application/media-x212/' . 'temp-' . $name);
                        $format = new FFMpeg\Format\Video\X264();
                        $format
                                ->setKiloBitrate($expectedbitrate)
                                ->setAudioCodec("libmp3lame");
                        $video
                                ->filters()
                                ->resize(new FFMpeg\Coordinate\Dimension(720, 480));
                        $video->save($format, BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)));

                        //speed up
                        exec('qt-faststart '
                                . BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name))
                                . ' '
                                . BASE . '/application/intro/video.mp4'
                        );
                        if (!file_exists(BASE . '/application/media-x212/' . (str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)))) {
                            copy(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)), BASE . '/application/intro/video.mp4');
                        }
                        if (file_exists(BASE . '/application/media-x212/' . 'temp-' . $name)) {
                            unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                        }
                        if (file_exists(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)))) {
                            unlink(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)));
                        }
                    } else {
                        //speed up
                        exec('qt-faststart '
                                . BASE . '/application/media-x212/' . 'temp-' . $name
                                . ' '
                                . BASE . '/application/intro/video.mp4'
                        );
                        if (!file_exists(BASE . '/application/media-x212/' . (str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)))) {
                            copy(BASE . '/application/media-x212/' . ('atemp-' . str_replace('.' . $pieces[count($pieces) - 1], '.mp4', $name)), BASE . '/application/intro/video.mp4');
                        }
                        if (file_exists(BASE . '/application/media-x212/' . 'temp-' . $name)) {
                            unlink(BASE . '/application/media-x212/' . 'temp-' . $name);
                        }
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
