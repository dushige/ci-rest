<?php

class Result {

    public $success = FALSE;
    public $message = '';

    public function set_error($message = '', $data = '') {
        $this->success = FALSE;
        $this->message = $message;
        if (!empty($data)) {
            $this->data = $data;
        }
        return $this;
    }

    public function set_success($message = '', $data = '') {
        $this->success = TRUE;
        $this->message = $message;
        if (!empty($data)) {
            $this->data = $data;
        }
        return $this;
    }
}
