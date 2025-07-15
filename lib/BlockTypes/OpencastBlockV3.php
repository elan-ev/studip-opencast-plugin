<?php

use Courseware\BlockTypes\BlockType;
use Opis\JsonSchema\Schema;
use Opencast\Models\Videos;
/**
 * This class represents the content of a Courseware test block.
 *
 * @author  Ron Lucke <lucke@elan-ev.de>
 * @license GPL2 or any later version
 */
class OpencastBlockV3 extends BlockType
{
    public static function getType(): string
    {
        return 'plugin-opencast-video';
    }

    public static function getTitle(): string
    {
        return dcgettext(OpencastV3::GETTEXT_DOMAIN, 'Opencast', LC_MESSAGES);
    }

    public static function getDescription(): string
    {
        return dcgettext(OpencastV3::GETTEXT_DOMAIN, 'Stellt eine Aufzeichnung aus dem Opencast-Plugin bereit', LC_MESSAGES);
    }

    public function initialPayload(): array
    {
        return [
            'token'  => ''
        ];
    }

    public function copyPayload(string $rangeId = ''): array
    {
        $payload = $this->getPayload();
        $defaultPlaylistSeminar = PlaylistSeminars::getDefaultPlaylistSeminar($rangeId);
        if ($payload['token'] && $defaultPlaylistSeminar) {
            $video = Videos::findByToken($payload['token']);

            if (!empty($video)) {
                $defaultPlaylist = Playlists::findOneById($defaultPlaylistSeminar->playlist_id);
                $plvideo = new PlaylistVideos;
                $plvideo->setData([
                    'playlist_id' => $defaultPlaylist->id,
                    'video_id'    => $video->id
                ]);

                try {
                    $defaultPlaylist->videos[] = $plvideo;
                } catch (\InvalidArgumentException $e) {
                }
                $defaultPlaylist->videos->store();
            } else {
                $payload['token'] = '';
            }
        }

        return $payload;
    }

    public static function getJsonSchema(): string
    {
        $schemaFile = __DIR__ . '/OpencastBlockV3.json';

        return Schema::fromJsonString(file_get_contents($schemaFile));
    }

    public static function getCategories(): array
    {
        return ['multimedia'];
    }

    public static function getContentTypes(): array
    {
        return ['rich'];
    }

    public static function getFileTypes(): array
    {
        return [];
    }

    public function setPayload($payload): void
    {
        parent::setPayload($payload);
    }
}
