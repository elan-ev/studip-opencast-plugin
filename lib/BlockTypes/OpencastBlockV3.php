<?php

use Courseware\BlockTypes\BlockType;
use Opis\JsonSchema\Schema;
use Opencast\Models\VideoCoursewareBlocks;
use Opencast\Models\CoursewareBlockMappings;
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

    public static function getJsonSchema(): Schema
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

    public function copyPayload(string $rangeId = ''): array
    {
        $payload = $this->getPayload();

        $token = md5($this->block['id'] . time());
        $payload['copied_token'] = $token;

        if (!empty($payload['token'])) {
            $video = Videos::findByToken($payload['token']);
            CoursewareBlockMappings::setRecord($token, $video->id, $rangeId);
        }

        return $payload;
    }

    public function setPayload($payload): void
    {
        if (!empty($payload['token'])) {
            $rangeId = $this->block->container->structural_element->range_id;
            VideoCoursewareBlocks::setRecord($rangeId, $payload['token'], $this->block['id']);
        }

        parent::setPayload($payload);
    }
}
