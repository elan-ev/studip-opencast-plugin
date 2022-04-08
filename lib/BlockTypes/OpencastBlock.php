<?php

use Courseware\BlockTypes\BlockType;
use Opis\JsonSchema\Schema;
use Opencast\LTI\OpencastLTI;

/**
 * This class represents the content of a Courseware test block.
 *
 * @author  Ron Lucke <lucke@elan-ev.de>
 * @license GPL2 or any later version
 */
class OpencastBlock extends BlockType
{
    public static function getType(): string
    {
        return 'plugin-opencast-video';
    }

    public static function getTitle(): string
    {
        return dcgettext(Opencast::GETTEXT_DOMAIN, 'Opencast', LC_MESSAGES);
    }

    public static function getDescription(): string
    {
        return dcgettext(Opencast::GETTEXT_DOMAIN, 'Stellt eine Aufzeichnung aus dem Opencast-Plugin bereit', LC_MESSAGES);
    }

    public function initialPayload(): array
    {
        return [
            'id'  => '',
            'url' => ''
        ];
    }

    public static function getJsonSchema(): Schema
    {
        $schemaFile = __DIR__ . '/OpencastBlock.json';

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
        if ($rangeId && $payload) {
            OpencastLTI::setCoursewareEpisodeAcls($rangeId, $payload['episode_id'], $payload['visible']);
        }
        return $payload;
    }
}
