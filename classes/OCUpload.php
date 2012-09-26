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
        $info = array();
        
        $info[] = $this->handle_file_upload(
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
        
        header('Vary: Accept');
        $json = json_encode($info);
        $redirect = isset($_REQUEST['redirect']) ?
            stripslashes($_REQUEST['redirect']) : null;
        if ($redirect) {
            header('Location: '.sprintf($redirect, rawurlencode($json)));
            return;
        }
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo $json;
    }
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null) {
        $filename = $this->trim_file_name($name, $type, $index);
        
        if(isset($_SESSION['opencast']['files'][$filename]) 
                && (time() - 5) < $_SESSION['opencast']['files'][$filename]->time)
        {
            $file = $_SESSION['opencast']['files'][$filename];
            $file->chunk += 1;
            $file->time = time();
        } else {
            $file = new stdClass();
            $file->name = $this->trim_file_name($name, $type, $index);
            $file->size = intval($size);
            $file->type = $type;
            $file->chunk = 0;
            $file->time = time();
        }
        
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            // multipart/formdata uploads (POST method uploads)
            $file->data = $uploaded_file; //TODO: testen wann das vorliegt und schaun obs funktioniert
        } else {
            $file->data = fopen('php://input', 'r');
        }
        $_SESSION['opencast']['files'][$file->name] = $file;
        
        var_dump($file, $uploaded_file);
        die;
        
        
        
        
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            $file_path = $this->options['upload_dir'].$file->name;
            $append_file = !$this->options['discard_aborted_uploads'] &&
                is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = filesize($file_path);
            if ($file_size === $file->size) {
            	if ($this->options['orient_image']) {
            		$this->orient_image($file_path);
            	}
                $file->url = $this->options['upload_url'].rawurlencode($file->name);
                foreach($this->options['image_versions'] as $version => $options) {
                    if ($this->create_scaled_image($file->name, $options)) {
                        if ($this->options['upload_dir'] !== $options['upload_dir']) {
                            $file->{$version.'_url'} = $options['upload_url']
                                .rawurlencode($file->name);
                        } else {
                            clearstatcache();
                            $file_size = filesize($file_path);
                        }
                    }
                }
            } else if ($this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $this->set_file_delete_url($file);
        }
        return $file;
    }
}

