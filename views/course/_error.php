<?php 
if (isset($this->flash['error'])) {
    if (is_array($this->flash['error'])) {
        foreach($this->flash['error'] as $msg) {
            echo MessageBox::error($msg);
        }
    } else {
        echo MessageBox::error($this->flash['error']);
    }
} else {
    echo MessageBox::error($_('Es wurde keine Fehlermeldung gesetzt.'));
}
?>
