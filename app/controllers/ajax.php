<?php

class AjaxController extends OpencastController
{
    public function index_action()
    {
        $this->render_text($this->_('Ups..'));
    }

    public function getseries_action()
    {
        $this->render_json(array_values([]));
    }

    public function getepisodes_action($series_id)
    {
        $this->render_json(array_values([]));
    }
}
