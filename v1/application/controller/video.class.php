<?php

class Video {

    /**
     * Array of objects
     */
    private $objects;
    private $token;
    /**
     * Array of settings
     */

    /**
     * Reference to the registry object
     */
    private $registry;
    private $URI;
    private $Token;
    private $user;
    private $role;
    private $association = array(
        'mp4' => 'video/mp4'
    );

    /**
     * Construct our index page
     */
    public function __construct(Registry $registry) {
        $this->registry = $registry;
        $this->URI = URI::getInstance();
        $this->load();
    }

    /*
      Load the index page
     */

    public function load() {

        $args = $this->URI->getArguments();

        $extension = $this->URI->getExtension();
        $extensions = $this->URI->getExtensions();
        $audio = '';
        $len = count($args);
        $video = $args[0];
        $extensions = $this->URI->getExtensions();
        //

        /* header('Content-Type:audio/' . $this->association[$extension]);
          header('Content-Disposition: inline; filename="' . $audio . '.' . $extensions . '"');
          header('Content-Length: ' . filesize(BASE . '/application/audio-x777/' . $audio . '.' . $extensions));
          header('Cache-Control: no-cache');
          //header('Content-Transfer-Encoding: chunked');
          readfile(BASE . '/application/audio-x777/' . $audio . '.' . $extensions);
          exit(); */
        $path = BASE . '/application/media-x212/' . $video . '.' . $extensions;
        // $path = "";
        // $song = $this->user->getSongs("", ["link" => "/v1/audio/" . $audio . '.' . $extensions], "1990-09-27 00:00:00", 0, 1)[1];
        // if ($this->role <= 0) {
        //     if (intval($song['ispublic'][0]) == 1) {
        //         $path = BASE . '/application/audio-x777/' . $audio . '.' . $extensions;
        //     } else {
        //         $path = BASE . '/application/audio-x777/sample/' . $audio . '.' . $extensions;
        //     }
        // } else if ($this->role == 4) {
        //     if (intval($song['ispublic'][0]) == 1 || intval($song['isowned'][0]) == 1) {
        //         $path = BASE . '/application/audio-x777/' . $audio . '.' . $extensions;
        //     } else {
        //         $path = BASE . '/application/audio-x777/sample/' . $audio . '.' . $extensions;
        //     }
        // } else {
        //     $path = BASE . '/application/audio-x777/' . $audio . '.' . $extensions;
        // }
        try {
            $publicKey = file_get_contents(BASE . '/application/keys-y348/public.pem');
            $decoded = \Firebase\JWT\JWT::decode(isset($_GET['TOKEN']) ? $_GET['TOKEN'] :null, $publicKey, array('RS256'));
            $decoded_array = (array) $decoded;
        } catch (Exception $e) { // Also tried JwtException
            header('Location: ' . $GLOBALS['config_base'] . '/404.html');
            exit();
        } catch (Error $e) { // Also tried JwtException
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit();
        }
        if (intval($decoded_array['role_id']) < 5) {
            $link =  $args[0] . '.' . $extensions;
            if (
                    $link != $decoded_array['link']) {
                header('Location: ' . $GLOBALS['config_base'] . '/404.html');
                exit();
            }
        }
        if (!file_exists($path)) {
            $path = BASE . '/application/intro/video.mp4';
        }
        $this->stream($path, $this->association[$extension]);
    }

    public function stream($file, $content_type = 'application/octet-stream') {
        //@error_reporting(0);
        // Make sure the files exists, otherwise we are wasting our time
        if (!file_exists($file)) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }

        // Get file size
        $filesize = sprintf("%u", filesize($file));
        // Handle 'Range' header
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
        } elseif ($apache = apache_request_headers()) {
            $headers = array();
            foreach ($apache as $header => $val) {
                $headers[strtolower($header)] = $val;
            }
            if (isset($headers['range'])) {
                $range = $headers['range'];
            } else
                $range = FALSE;
        } else
            $range = FALSE;

        //Is range
        if ($range) {
            $partial = true;
            list($param, $range) = explode('=', $range);
            // Bad request - range unit is not 'bytes'
            if (strtolower(trim($param)) != 'bytes') {
                header("HTTP/1.1 400 Invalid Request");
                exit;
            }
            // Get range values
            $range = explode(',', $range);
            $range = explode('-', $range[0]);
            // Deal with range values
            if ($range[0] === '') {
                $end = $filesize - 1;
                $start = $end - intval($range[0]);
            } else if ($range[1] === '') {
                $start = intval($range[0]);
                $end = $filesize - 1;
            } else {
                // Both numbers present, return specific range
                $start = intval($range[0]);
                $end = intval($range[1]);
                if ($end >= $filesize || (!$start && (!$end || $end == ($filesize - 1)))) {
                    $partial = false; // Invalid range/whole file specified, return whole file
                }
            }


            $length = $end - $start + 1;
        }
        // No range requested
        else {
            $partial = false;
        }

        // Send standard headers
        header("Content-Type: $content_type");
        header("Content-Length: " . ($partial ? $length : $filesize));
        header('Accept-Ranges: bytes');
        // send extra headers for range handling...
        if ($partial) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$filesize");
            if (!$fp = fopen($file, 'rb')) {
                header("HTTP/1.1 500 Internal Server Error");
                exit;
            }
            if ($start)
                fseek($fp, $start);
            while ($length) {
                set_time_limit(0);
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                print(fread($fp, $read));
            }
            fclose($fp);
        }
        //just send the whole file
        else {
            readfile($file);
        }
        exit;
    }

}

?>
