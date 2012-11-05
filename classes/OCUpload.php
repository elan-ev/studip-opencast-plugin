<?php


require_once $this->trails_root.'/vendor/upload/server/php/upload.class.php';

class OCUpload extends UploadHandler 
{
    public function __construct($options = NULL) 
    {
        parent::__construct($options);
    }
    
    public function post() {
        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : null;
        
        $file = $this->handle_file_upload(
            isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
            isset($_SERVER['HTTP_X_FILE_NAME']) ?
                $_SERVER['HTTP_X_FILE_NAME'] : (isset($upload['name']) ?
                    $upload['name'] : null),
            isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                $_SERVER['HTTP_X_FILE_SIZE'] : (isset($upload['size']) ?
                    $upload['size'] : null),
            isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                $_SERVER['HTTP_X_FILE_TYPE'] : (isset($upload['type']) ?
                    $upload['type'] : null),
            isset($upload['error']) ? $upload['error'] : null
        );
        return $file; 
    }
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null) {
        $filename = $this->trim_file_name($name, $type, $index);
        $file = new OCUploadFile($filename, intval($size), $type, $error, $index);
        
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            // multipart/formdata uploads (POST method uploads)
            $file->setChunkPath($uploaded_file); //TODO: ist das absoluter pfad?
        } else {
            $file->createChunkFile();
        }
        return $file;
    }
}

