<?php
/*
 * OpencastEmbedButton.class.php 
 * Copyright (c) 2013  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


class OpencastEmbedButton extends StudipPlugin implements SystemPlugin
{
    function __construct()
    {
            parent::__construct();

            PageLayout::addScript($this->getPluginUrl() . '/javascripts/embed.js');

            //$this->addStudipMarkup();

           
    }
        
}