<?php

namespace Opencast;

Trait RelationshipTrait
{
    private function getRelLink($slug)
    {
        return \PluginEngine::getLink('opencastv3/api/' . $slug);
    }
}
