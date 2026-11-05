<?php

use Courseware\BlockTypes\BlockType;
use JsonApi\Errors\AuthorizationFailedException;
use Opis\JsonSchema\Schema;
use Opencast\Helpers\PlaylistMigration;
use Opencast\Models\Helpers;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\PlaylistSeminarVideos;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\Videos;
use Opencast\Providers\Perm;
use Opencast\Routes\Playlist\Authority as PlaylistAuthority;

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

    /**
     * Copies the payload of the block. If the block is copied into a course, the current
     * user has to be allowed to use the video there, otherwise the video is removed from
     * the copy (see linkVideoToCourse()).
     *
     * @param string $rangeId range id of the copy target
     *
     * @return array
     */
    public function copyPayload(string $rangeId = ''): array
    {
        $payload = $this->getPayload();

        // outside of courses nobody gets access to the video via the block, so there is nothing to check
        $course = !empty($payload['token']) && $rangeId ? \Course::find($rangeId) : null;
        if (!$course) {
            return $payload;
        }

        $video = Videos::findByToken($payload['token']);

        // A newly built block (e.g. inserted from the clipboard) is not the source of the copy.
        $source_course_id = $this->block->isNew() ? null : $this->getBlockCourseId();

        if (!$video || !self::linkVideoToCourse($video, $course, $source_course_id)) {
            error_log('Opencast: Video ' . $payload['token'] . ' wurde beim Kopieren in die Veranstaltung '
                . $course->id . ' entfernt, da es nicht existiert oder die Berechtigung fehlt.');
            $payload['token'] = '';
        }

        return $payload;
    }

    /**
     * Sets the payload of the block. If a new video is set in a block of a course (e.g. by
     * selecting it in the block or by importing a courseware), the current user has to be
     * allowed to use the video in the course (see linkVideoToCourse()).
     *
     * @param mixed $payload
     *
     * @throws AuthorizationFailedException if the current user is not allowed to use the video in the course
     */
    public function setPayload($payload): void
    {
        $token = self::getTokenOfPayload($payload);

        if ($token !== '' && $token !== self::getTokenOfPayload($this->block['payload'] ?? null)) {
            $course_id = $this->getBlockCourseId();
            $course    = $course_id ? \Course::find($course_id) : null;

            if ($course) {
                $video = Videos::findByToken($token);

                if (!$video || !self::linkVideoToCourse($video, $course)) {
                    error_log('Opencast: Video ' . $token . ' darf in der Veranstaltung ' . $course->id
                        . ' nicht verwendet werden, da es nicht existiert oder die Berechtigung fehlt.');
                    throw new AuthorizationFailedException();
                }
            }
        }

        parent::setPayload($payload);
    }

    /**
     * Checks whether the current user is allowed to use the video in a courseware block of the
     * course and, if so, integrates the video into the course.
     *
     * Course members get access to all videos of the courseware blocks of the course (see
     * UserRoles), so the current user has to be allowed to pass on the video (write permission),
     * unless the video is already part of the course or copied within the course.
     *
     * If the current user is allowed to manage Opencast content in the course, the video is
     * integrated into the course as well: the Opencast tool is activated (hidden for students)
     * and the video is added to the default playlist of the course, taking over the visibility
     * of the video in the source course. Errors are only logged.
     *
     * @param Videos      $video
     * @param \Course     $course           the course the video is used in
     * @param string|null $source_course_id the course the video has been copied from, if any
     *
     * @return bool whether the current user is allowed to use the video in the course
     */
    private static function linkVideoToCourse(Videos $video, \Course $course, ?string $source_course_id = null): bool
    {
        try {
            $video_in_course = in_array($course->id, PlaylistSeminars::getCoursesOfVideo($video));
            $copied_in_course = $source_course_id === $course->id;

            if (!$video_in_course && !$copied_in_course && !$video->havePerm('write')) {
                return false;
            }
        } catch (\Throwable $e) {
            error_log('Opencast: Fehler beim Prüfen der Berechtigung für das Video: ' . $e->getMessage());
            return false;
        }

        try {
            if (
                !$video->trashed
                && Perm::editAllowed($course->id)
                && self::ensureOpencastActivated($course)
                && !$video_in_course
            ) {
                self::addToDefaultPlaylist($video, $course->id, $source_course_id);
            }
        } catch (\Throwable $e) {
            error_log('Opencast: Fehler beim Verknüpfen des Videos mit der Veranstaltung: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Adds the video to the default playlist of the course, same as PlaylistAddVideos. Errors are
     * only logged, the video is still accessible in the courseware in that case.
     *
     * @param Videos      $video
     * @param string      $course_id
     * @param string|null $source_course_id the course to take over the visibility of the video from
     */
    private static function addToDefaultPlaylist(Videos $video, string $course_id, ?string $source_course_id): void
    {
        global $user;

        try {
            $defaultPlaylist = self::getDefaultPlaylistForCopy($course_id);

            if (
                !$defaultPlaylist
                || !PlaylistAuthority::canAddAndRemoveVideoInPlaylist($user, $defaultPlaylist, $video, $course_id)
            ) {
                return;
            }

            // has to happen before the video is added, so the ACL respects a scheduled visibility
            $visibility_copied = self::copyCourseVisibility($video, $source_course_id, $course_id, $defaultPlaylist);

            if (PlaylistMigration::isConverted()) {
                // playlists are managed by Opencast, otherwise the playlist sync would remove the video again
                $defaultPlaylist->addEntries([$video]);

                // addEntries only updates the ACL for videos which are new in the playlist
                if ($visibility_copied) {
                    Videos::checkEventACL(null, null, $video);
                }
            } else {
                $plvideo = new PlaylistVideos([$defaultPlaylist->id, $video->id]);
                $plvideo->store();

                Videos::checkEventACL(null, null, $video);
            }
        } catch (\Exception $e) {
            error_log('Opencast: Fehler beim Hinzufügen des Videos zur Playlist: ' . $e->getMessage());
        }
    }

    /**
     * Returns the video token of the passed payload
     *
     * @param mixed $payload payload as array, object or JSON string
     *
     * @return string
     */
    private static function getTokenOfPayload($payload): string
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (is_object($payload)) {
            $payload = (array) $payload;
        }

        return is_array($payload) && is_string($payload['token'] ?? null) ? $payload['token'] : '';
    }

    /**
     * Returns the id of the course the block belongs to, if any
     *
     * @return string|null
     */
    private function getBlockCourseId(): ?string
    {
        $element = $this->block->container->structural_element ?? null;

        if ($element && $element->range_type === 'course') {
            return $element->range_id;
        }

        return null;
    }

    /**
     * Makes sure the Opencast tool is activated in the passed course. The tool is only
     * activated if the current user is allowed to manage Opencast content in the course
     * and the tool may be used in the course at all. A newly activated tool is hidden
     * for students.
     *
     * @param \Course $course
     *
     * @return bool whether the tool is activated in the course
     */
    private static function ensureOpencastActivated(\Course $course): bool
    {
        $plugin = \PluginEngine::getPlugin('OpencastV3');
        if (!$plugin) {
            return false;
        }

        $plugin_manager = \PluginManager::getInstance();
        if ($plugin_manager->isPluginActivated($plugin->getPluginId(), $course->id)) {
            return true;
        }

        if (
            !Perm::editAllowed($course->id)
            || !$plugin->isActivatableForContext($course)
            || !$course->getSemClass()->isModuleAllowed(get_class($plugin))
        ) {
            return false;
        }

        try {
            $plugin_manager->setPluginActivated($plugin->getPluginId(), $course->id, true);

            // The tool has only been activated for the videos of the courseware, so it is hidden
            // for students. Lecturers can make it visible in the tool settings of the course.
            // Hiding the tool does not affect the access to the videos in the courseware.
            $tool = \ToolActivation::find([$course->id, $plugin->getPluginId()]);
            if ($tool) {
                $metadata = $tool->metadata ? $tool->metadata->getArrayCopy() : [];
                $metadata['visibility'] = 'tutor';
                $tool->metadata = $metadata;
                $tool->store();
            }
        } catch (\Exception $e) {
            error_log('Opencast: Fehler beim Aktivieren des Werkzeugs: ' . $e->getMessage());
        }

        return (bool) $plugin_manager->isPluginActivated($plugin->getPluginId(), $course->id);
    }

    /**
     * Takes over the visibility of the video for students from the source course to
     * the default playlist of the target course. An existing visibility in the target
     * course is never changed.
     *
     * @param Videos      $video
     * @param string|null $source_course_id
     * @param string      $target_course_id
     * @param Playlists   $target_playlist
     *
     * @return bool whether a visibility has been stored
     */
    private static function copyCourseVisibility(
        Videos $video,
        ?string $source_course_id,
        string $target_course_id,
        Playlists $target_playlist
    ): bool
    {
        if (!$source_course_id || $source_course_id === $target_course_id) {
            return false;
        }

        $visibility = Videos::getCourseVisibility($video, $source_course_id);
        if (!$visibility) {
            return false;
        }

        $playlist_seminar = PlaylistSeminars::findOneBySQL(
            'playlist_id = ? AND seminar_id = ?',
            [$target_playlist->id, $target_course_id]
        );

        if (!$playlist_seminar || PlaylistSeminarVideos::find([$playlist_seminar->id, $video->id])) {
            return false;
        }

        $psv = new PlaylistSeminarVideos();
        $psv->setValue('playlist_seminar_id', $playlist_seminar->id);
        $psv->setValue('video_id', $video->id);
        $psv->setValue('visibility', $visibility['visibility']);
        $psv->setValue('visible_timestamp', $visibility['visible_timestamp']);

        return (bool) $psv->store();
    }

    /**
     * Returns the default playlist of the passed course.
     *
     * If the course has no default playlist yet (e.g. the Opencast tab has never
     * been used), the default playlist is created, provided the current user is
     * allowed to manage Opencast content in that course.
     *
     * @param string $course_id
     *
     * @return Playlists|null
     */
    private static function getDefaultPlaylistForCopy(string $course_id): ?Playlists
    {
        // promotes an already linked playlist to default, if necessary
        if (!Helpers::checkCourseDefaultPlaylist($course_id)) {
            if (!Perm::editAllowed($course_id)) {
                return null;
            }

            try {
                Helpers::checkCoursePlaylist($course_id);
            } catch (\Exception $e) {
                error_log('Opencast: Fehler beim Anlegen der Standard-Kurswiedergabeliste: ' . $e->getMessage());
                return null;
            }
        }

        $defaultPlaylistSeminar = PlaylistSeminars::getDefaultPlaylistSeminar($course_id);

        return $defaultPlaylistSeminar ? Playlists::findOneById($defaultPlaylistSeminar->playlist_id) : null;
    }

    public static function getJsonSchema(): Schema
    {
        $schemaFile = __DIR__ . '/../../BlockTypes/OpencastBlockV3.json';

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
}
