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
     * This is the maximum number of seconds that unread entries are
     * marked as new.
     */
    const LAST_VISIT_MAX = 7776000; // 90 days

    /**
     * @param $course_id
     */
    function __construct($course_id){

        $this->setCourseID($course_id);
        // take care of connected series
        $cseries = OCSeriesModel::getConnectedSeries($this->getCourseID(),true);

        if(!empty($cseries)){
            $current_seriesdata = array_pop($cseries);
            $this->setSeriesMetadata($current_seriesdata);
            $this->setSeriesID($current_seriesdata['identifier']);
            $this->setEpisodePostions(OCModel::getCoursePositions($this->course_id));
        } else {
            $this->setSeriesID(false);
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

        if($this->getSeriesID()) {
            $series = $this->getCachedEntries($this->getSeriesID(), $force_reload);
            $stored_episodes = OCModel::getCoursePositions($this->getCourseID());
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
        } else return false;

    }

    private function episodeComparison($stored_episodes, $remote_episodes) {
        $episodes = array();
        $oc_episodes = $this->prepareEpisodes($remote_episodes);
        $lastpos;

        foreach($stored_episodes as $key => $stored_episode){

            if($tmp = $oc_episodes[$stored_episode['episode_id']]){
                $tmp['visibility'] = $stored_episode['visible'];
                $tmp['position'] = $stored_episode['position'];
                $tmp['mkdate']  = $stored_episode['mkdate'];
                $lastpos = $stored_episode['position'];

                OCModel::setCoursePositionForEpisode($stored_episode['episode_id'], $lastpos, $this->getCourseID(), $tmp['visibility'], $stored_episode['mkdate']);
                $episodes[$stored_episode['position']] = $tmp;

                unset($oc_episodes[$stored_episode['episode_id']]);
                unset($stored_episodes[$key]);
            }

        }

        //add new episodes
        if(!empty($oc_episodes)){
            foreach($oc_episodes as $episode){
                $lastpos++;
                $timestamp = time();
                $episode['visibility'] = 'true';
                $episode['position'] = $lastpos;
                $episode['mkdate'] = $timestamp;
                OCModel::setCoursePositionForEpisode($episode['id'], $lastpos, $this->getCourseID(), 'true', $timestamp);
                $episodes[$episode['position']] = $episode;
                NotificationCenter::postNotification('NewEpisodeForCourse',array('episode_id' => $episode['id'],'course_id' => $this->getCourseID(), 'episode_title' => $episode['title']));
            }

        }

        // removed orphaned episodes
        if(!empty($stored_episodes)){
            foreach($stored_episodes as $orphaned_episode) {
                // todo log event for this action
                OCModel::removeStoredEpisode($orphaned_episode['episode_id'],$this->getCourseID());
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

    private function getCachedEntries($series_id, $forced_reload) {

        $cached_series = OCSeriesModel::getCachedSeriesData($series_id);


        if(!$cached_series || $forced_reload){

            $search_client = SearchClient::getInstance();
            $series = $search_client->getEpisodes($series_id);

            if($forced_reload && $cached_series){
                OCSeriesModel::updateCachedSeriesData($series_id, serialize($series));
            } else {
                OCSeriesModel::setCachedSeriesData($series_id, serialize($series));
            }
        }

        return $cached_series;
    }

    /**
     * return number of new episodes since last visit up to 3 month ago
     *
     * @param string $visitdate count all entries newer than this timestamp
     *
     * @return int the number of entries
     */
    public function getCount($visitdate)
    {
        if ($visitdate < time() - OCCourseModel::LAST_VISIT_MAX) {
            $visitdate = time() - OCCourseModel::LAST_VISIT_MAX;
        }


        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM oc_seminar_episodes
            WHERE seminar_id = :seminar_id AND mkdate > :lastvisit");


        $stmt->bindParam(':seminar_id', $this->getCourseID());
        $stmt->bindParam(':lastvisit', $visitdate);

        $stmt->execute();

        return $stmt->fetchColumn();
    }

}