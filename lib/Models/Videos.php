<?php

namespace Opencast\Models;

use Opencast\Models\Filter;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;
use Opencast\Models\REST\SearchClient;

class Videos extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\VideosUserPerms',
            'assoc_foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    public function findByFilter($filters)
    {

        global $user;

        $params = [
            ':user_id'=> $user->id
        ];

        $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = id) ';

        $where = ' WHERE 1 ';
        $tag_ids      = [];
        $playlist_ids = [];

        foreach ($filters->getFilters() as $filter) {
            switch ($filter['type']) {
                case 'text':
                    $pname = ':text' . sizeof($params);
                    $where .= " AND (title LIKE $pname OR description LIKE $pname)";
                    $params[$pname] = '%' . $filter['value'] .'%';
                    break;

                case 'tag':
                    // get id of this tag (if any)
                    if (!empty($filter['value'])) {
                        $tags = Tags::findBySQL($sq = 'tag LIKE ?',  $pr = ['%'. $filter['value'] .'%']);

                        if (!empty($tags)) {
                            foreach ($tags as $tag) {
                                $tag_ids[] = $tag->id;
                            }
                        } else {
                            $tag_ids[] = '-1';
                        }
                    }
                    break;

                case 'playlist':
                    $playlists = Playlists::findByToken($filter['value']);

                    if (!empty($playlists)) {
                        foreach ($playlists as $playlist) {
                            $playlist_ids[] = $playlist->id;
                        }
                    } else {
                        $playlist_ids[] = '-1';
                    }

                    break;
            }
        }

        if (!empty($tag_ids)) {
            $sql .= ' INNER JOIN oc_video_tags AS t ON (t.tag_id IN('. implode(',', $tag_ids) .'))';
        }

        if (!empty($playlist_ids)) {
            $sql .= ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id IN('. implode(',', $playlist_ids) .'))';
            $where .= ' AND opv.video_id = id';
        }

        $sql .= $where;

        $sql .= ' GROUP BY oc_video.id';

        $stmt = \DBManager::get()->prepare($s = "SELECT COUNT(*) FROM (SELECT oc_video.* FROM oc_video $sql) t");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        $sql   .= ' LIMIT '. $filters->getOffset() .', '. $filters->getLimit();

        return [
            'videos' => self::findBySQL($sql, $params),
            'count'  => $count
        ];
    }

    public function toSanitizedArray()
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        if ($data['episode']) {
            $search_client = SearchClient::getInstance($data['config_id']);
            $data['paella'] = $search_client->getBaseURL() . "/paella/ui/watch.html?id=" . $data['episode'];
        }

        $data['preview']     = json_decode($data['preview'], true);
        $data['publication'] = json_decode($data['publication'], true);

        return $data;
    }

    public static function parseEvent($eventType, $episode, $video)
    {
        if (!empty($episode->publications[0]->attachments)) {
            $presentation_preview  = false;
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $annotation_tool       = false;
            $duration              = 0;

            foreach ((array) $episode->publications[0]->attachments as $attachment) {
                if ($attachment->flavor === "presenter/search+preview" || $attachment->type === "presenter/search+preview") {
                    $preview = $attachment->url;
                }
                if ($attachment->flavor === "presentation/player+preview" || $attachment->type === "presentation/player+preview") {
                    $presentation_preview = $attachment->url;
                }
            }

            foreach ($episode->publications[0]->media as $track) {
                $parsed_url = parse_url($track->url);

                if ($track->flavor === 'presenter/delivery') {
                    if (($track->mediatype === 'video/mp4' || $track->mediatype === 'video/avi')
                        && ((in_array('atom', $track->tags) || in_array('engage-download', $track->tags))
                            && $parsed_url['scheme'] != 'rtmp' && $parsed_url['scheme'] != 'rtmps')
                        && !empty($track->has_video)
                    ) {
                        $quality = self::calculate_size(
                            $track->bitrate,
                            $track->duration
                        );
                        $presenter_download[$quality] = [
                            'url'  => $track->url,
                            'info' => self::getResolutionString($track->width, $track->height)
                        ];

                        $duration = $track->duration;
                    }

                    if (
                        in_array($track->mediatype, ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
                        && !empty($track->has_audio)
                    ) {
                        $quality = self::calculate_size(
                            $track->bitrate,
                            $track->duration
                        );
                        $audio_download[$quality] = [
                            'url'  => $track->url,
                            'info' => round($track->audio->bitrate / 1000, 1) . 'kb/s, ' . explode('/', $track->mediatype)[1]
                        ];

                        $duration = $track->duration;
                    }
                }

                if ($track->flavor === 'presentation/delivery' && (
                    ($track->mediatype === 'video/mp4'
                        || $track->mediatype === 'video/avi'
                    ) && (
                        (in_array('atom', $track->tags)
                            || in_array('engage-download', $track->tags)
                        )
                        && $parsed_url['scheme'] != 'rtmp'
                        && $parsed_url['scheme'] != 'rtmps'
                    )
                    && !empty($track->has_video)
                )) {
                    $quality = self::calculate_size(
                        $track->bitrate,
                        $track->duration
                    );

                    $presentation_download[$quality] = [
                        'url'  => $track->url,
                        'info' => self::getResolutionString($track->width, $track->height)
                    ];
                }
            }

            foreach ($episode->publications as $publication) {
                if ($publication->channel == 'annotation-tool') {
                    $annotation_tool = $publication->url;
                }
            }

            ksort($presenter_download);
            ksort($presentation_download);
            ksort($audio_download);

            $video->duration = $duration;

            $video->preview = json_encode([
                'search' => $preview,
                'player' => $presentation_preview
            ]);

            $video->publication = json_encode([
                'downloads' => [
                    'presenter'    => $presenter_download,
                    'presentation' => $presentation_download,
                    'audio'        => $audio_download
                ],
                'annotation_tool'  => $annotation_tool
            ]);

            $video->created = date('Y-m-d H:i:s', strtotime($episode->created));

            $video->author = $episode->creator;
            $video->contributors = implode(', ', $episode->contributor);

            return $video->store();
        }

        return false;
    }

    private static function calculate_size($bitrate, $duration)
    {
        return ($bitrate / 8) * ($duration / 1000);
    }

    private static function getResolutionString($width, $height)
    {
        return $width . ' * ' . $height . ' px';
    }
}
