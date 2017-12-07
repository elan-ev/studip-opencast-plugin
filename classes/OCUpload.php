<?php


require_once $this->trails_root.'/vendor/UploadHandler.php';

class OCUpload
{
    public function __construct($options = NULL)
    {
    }

    function get_server_var($id) {
        return @$_SERVER[$id];
    }

    public function post() {
        if (!$uuid = Request::option('uuid')) return;

        // Manage chunks on our own
        /*
        $content_disposition_header = $this->get_server_var('HTTP_CONTENT_DISPOSITION');
        $file_name = $content_disposition_header ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $content_disposition_header
            )) : null;

        tglog('Filename: '. $file_name);
        */

        mkdir($GLOBALS['TMP_PATH'] . '/opencast/');
        $tmp_file_name = $GLOBALS['TMP_PATH'] . '/opencast/' . $uuid;
        tglog('TMPFILE: '. $tmp_file_name);
        // tglog('$')

        tglog('SERVER - Content-Length: '. $this->get_server_var('CONTENT_LENGTH'));
        #tglog('SERVER:'. print_r($_SERVER, 1));
        tglog('_REQUEST:'. print_r($_REQUEST, 1));

        #tglog('HEADERS: '. print_r(getallheaders(), 1));


        /*
        $append_file = $content_range && is_file($file_path) &&
            $file->size > $this->get_file_size($file_path);
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            // multipart/formdata uploads (POST method uploads)
            if ($append_file) {

            } else {
                move_uploaded_file($uploaded_file, $file_path);
            }
        }*/

        if (isset($_FILES['video'])) {
            $upload = $_FILES['video'];
            // last chunk received
            tglog('Last ChunkSize: ' . filesize($upload['tmp_name']));

            file_put_contents(
                $tmp_file_name,
                file_get_contents($upload['tmp_name']),
                FILE_APPEND
            );

            // upload finished!
            tglog('Upload fertig: ' . filesize($tmp_file_name));

            return new OCUploadFile($tmp_file_name, filesize($tmp_file_name), $upload['type'], $upload['error']);
        } else {
            // intermediate chunks
            $chunk = file_get_contents('php://input');
            tglog('ChunkSize: ' . strlen($chunk));

            file_put_contents(
                $tmp_file_name,
                $chunk,
                FILE_APPEND
            );
        }

        return false;

        /*
        $content_disposition_header = $this->get_server_var('HTTP_CONTENT_DISPOSITION');
        $file_name = $content_disposition_header ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $content_disposition_header
            )) : null;
        tglog('Filename: '. $filename);
        */

        /*
        if (isset($_FILES['video'])) {
            // get chunk-number
        $content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
        $content_range = $content_range_header ?
            preg_split('/[^0-9]+/', $content_range_header) : null;
        $size =  $content_range ? $content_range[3] : null;
        */


        /*
        //TODO: Falls nicht möglich Fehler ausgeben wegen zu altem browser
        $upload = isset($_FILES['video']) ?
            $_FILES['video'] : null;

        $totalFileSize = Request::int('total_file_size');
        $file = $this->handle_file_upload($upload['tmp_name'],
                Request::get('file_name'),
                $totalFileSize,
                $upload['type'],
                $upload['error']
        );

        tglog('File-Object: '. print_r($file, 1));
        return $file;
        */
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null) {
        $filename = $this->trim_file_name($name, $type, $index);
        $file = new OCUploadFile($filename, intval($size), $type, $error, $index);

        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            // multipart/formdata uploads (POST method uploads)
            $file->setChunkPath($uploaded_file); //TODO: ist das absoluter pfad?
        } else {// wenn älterer Firefox
            die;
            $file->createChunkFile();
        }
        return $file;
    }
}
