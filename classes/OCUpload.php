<?php


require_once $this->trails_root.'/vendor/upload.class.php';

class OCUpload extends UploadHandler 
{
    public function __construct($options = NULL) 
    {
        parent::__construct($options);
    }
    
    public function post() {
        //TODO: Falls nicht möglich Fehler ausgeben wegen zu altem browser
        $upload = isset($_FILES['video']) ?
            $_FILES['video'] : null;
        $totalFileSize = Request::int('total_file_size');
        $file = $this->handle_file_upload($upload['tmp_name'],
                $upload['name'],
                $totalFileSize,
                $upload['type'],
                $upload['error']
        );
        return $file; 
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

