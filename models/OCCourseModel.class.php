<?php

/**
 * Created by PhpStorm.
 * User: aklassen
 * Date: 23.07.15
 * Time: 12:14
 */

require_once 'OCModel.php';
require_once dirname(__FILE__) .'/../classes/OCRestClient/SearchClient.php';

class OCCourseModel
{

    /**
     * @param $course_id
     */
    function __construct($course_id){

        $this->course_id = $course_id;
        // take care of connected series
        $cseries =OCSeriesModel::getConnectedSeries($this->course_id);
        if(!empty($cseries)){
            $current_seriesdata = array_pop($cseries);

            self::setSeriesMetadata($current_seriesdata);
            self::setSeriesID($current_seriesdata['identifier']);
            self::setEpisodePostions(OCModel::getCoursePositions($this->course_id));
        }

    }

    public function getCourseID(){
        return $this->course_id;
    }

    public function getSeriesID(){
        return $this->series_id;
    }

    public function getSeriesMetadata(){
        return $this->seriesMetadata;
    }

    public function getEpisodePositions(){
        return $this->episodePositions;
    }

    public function setSeriesID($series_id){
        $this->series_id = $series_id;
    }

    public function setCourseID($course_id){
        $this->course_id = $course_id;
    }

    public function setSeriesMetadata($seriesMetadata){
        $this->seriesMetadata = $seriesMetadata;
    }

    public function setEpisodePostions($positions){
        $this->episodePositions = $positions;
    }

    /*  */

    public function getEpisodes($force_reload = false){


       $cache = StudipCacheFactory::getCache();
       $cache_key = 'oc_episodedata/'.$this->course_id ;

       $series = unserialize($cache->read($cache_key));

       if(empty($series) && !$force_reload) {
           $search_client = SearchClient::getInstance();
           $series = $search_client->getEpisodes($this->getSeriesID());
           // cache ordered episodes for 30mins
           $cache->write($cache_key, serialize($series), 1800);
       }


        $stored_episodes = OCModel::getCoursePositions($this->course_id);
        $ordered_episodes = array();

        //check if series' episodes is already stored in studip
        if(!empty($stored_episodes)) {
            if(!empty($series)) {
                // add additional episode metadata from opencast
                $ordered_episodes = $this->episodeComparison($stored_episodes, $series);
            } else {
                // someone deleted the connected episodes in opencast
                //TODO
            }
        } else {
            // since we don't have any idea about the episodes...
            if(!empty($series)) {
                $ordered_episodes = $this->episodeComparison($stored_episodes, $series);
            } else {
                // that must be a brand new series without any episodes
            }
        }


        return $ordered_episodes;
    }

    private function episodeComparison($stored_episodes, $remote_episodes) {
        $episodes = array();
        $oc_episodes = $this->prepareEpisodes($remote_episodes);
        $lastpos;

        foreach($stored_episodes as $key => $stored_episode){

            if($tmp = $oc_episodes[$stored_episode['episode_id']]){
                $tmp['visibility'] = $stored_episode['visible'];
                $tmp['position'] = $stored_episode['position'];
                $lastpos = $stored_episode['position'];
                OCModel::setCoursePositionForEpisode($stored_episode['episode_id'], $lastpos, $this->course_id, $tmp['visibility']);
                $episodes[$stored_episode['position']] = $tmp;

                unset($oc_episodes[$stored_episode['episode_id']]);
                unset($stored_episodes[$key]);
            }

        }

        //add new episodes
        if(!empty($oc_episodes)){
            foreach($oc_episodes as $episode){
                $lastpos++;

                $episode['visibility'] = true;
                $episode['position'] = $lastpos;
                OCModel::setCoursePositionForEpisode($episode['id'], $lastpos, $this->course_id, 'true');
                $episodes[$episode['position']] = $episode;
            }

        }

        // removed orphaned episodes
        if(!empty($stored_episodes)){
            foreach($stored_episodes as $orphaned_episode) {
                // todo log event for this action
                OCModel::removeStoredEpisode($orphaned_episode['episode_id'],$this->course_id);
            }
        }


        return $episodes;

    }

    private function prepareEpisodes($oc_episodes){
        $episodes = array();
        foreach($oc_episodes as $episode) {

            if(is_object($episode->mediapackage)){


                foreach($episode->mediapackage->attachments->attachment as $attachment) {
                    if($attachment->type === 'presenter/search+preview') $preview = $attachment->url;
                }

                foreach($episode->mediapackage->media->track as $track) {
                    // TODO CHECK CONDITIONS FOR MEDIAPACKAGE AUDIO AND VIDEO DL
                    if(($track->type === 'presenter/delivery') && ($track->mimetype === 'video/mp4' || $track->mimetype === 'video/avi')){
                        $url = parse_url($track->url);
                        if(in_array('atom', $track->tags->tag) && $url['scheme'] != 'rtmp') {
                            $presenter_download = $track->url;
                        }
                    }
                    if(($track->type === 'presentation/delivery') && ($track->mimetype === 'video/mp4' || $track->mimetype === 'video/avi')){
                        $url = parse_url($track->url);
                        if(in_array('atom', $track->tags->tag) && $url['scheme'] != 'rtmp') {
                            $presentation_download = $track->url;
                        }
                    }
                    if(($track->type === 'presenter/delivery') && (($track->mimetype === 'audio/mp3') || ($track->mimetype === 'audio/mpeg') || ($track->mimetype === 'audio/m4a')))
                        $audio_download = $track->url;
                }
                $episodes[$episode->id] = array('id' => $episode->id,
                    'title' => OCModel::sanatizeContent($episode->dcTitle),
                    'start' => $episode->mediapackage->start,
                    'duration' => $episode->mediapackage->duration,
                    'description' => OCModel::sanatizeContent($episode->dcDescription),
                    'author' => OCModel::sanatizeContent($episode->dcCreator),
                    'preview' => $preview,
                    'presenter_download' => $presenter_download,
                    'presentation_download' => $presentation_download,
                    'audio_download' => $audio_download,
                );
            }
        }

        return $episodes;

    }


}