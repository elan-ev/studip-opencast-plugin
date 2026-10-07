<?php

namespace Opencast\Models;

/**
 * Acceptance of the Opencast terms of service (OPENCAST_TOS).
 *
 * If OPENCAST_SHOW_TOS is enabled, users with edit permissions in a course
 * have to accept the terms of service once before they can use the plugin.
 * Course members without edit permissions get access to a course as soon as
 * at least one of its lecturers (or tutors, depending on
 * OPENCAST_TUTOR_EPISODE_PERM) has accepted them.
 */
class Tos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_tos';

        parent::configure($config);
    }

    /**
     * @return bool true, if users have to accept the terms of service
     */
    public static function isRequired()
    {
        return (bool) \Config::get()->OPENCAST_SHOW_TOS;
    }

    /**
     * @param string $user_id
     *
     * @return bool true, if the user has accepted the terms of service
     */
    public static function hasAccepted($user_id)
    {
        return self::exists($user_id);
    }

    /**
     * Stores the acceptance of the terms of service for the passed user.
     *
     * @param string $user_id
     */
    public static function accept($user_id)
    {
        // Not using store(): SimpleORMap would write a unix timestamp into the
        // TIMESTAMP column mkdate, which is filled by the database instead.
        $stmt = \DBManager::get()->prepare('INSERT IGNORE INTO oc_tos (user_id) VALUES (?)');
        $stmt->execute([$user_id]);
    }

    /**
     * Checks if at least one member of the course, who is allowed to edit the
     * videos of the course, has accepted the terms of service.
     *
     * @param string $course_id
     *
     * @return bool
     */
    public static function isAcceptedForCourse($course_id)
    {
        $status = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? ['dozent', 'tutor'] : ['dozent'];

        $stmt = \DBManager::get()->prepare(
            'SELECT 1 FROM seminar_user
             INNER JOIN oc_tos USING (user_id)
             WHERE seminar_user.Seminar_id = ? AND seminar_user.status IN (?)
             LIMIT 1'
        );
        $stmt->execute([$course_id, $status]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Returns the terms of service in the language of the passed user. Falls
     * back to the first non-empty translation.
     *
     * @param string $user_id
     *
     * @return string the terms of service (Stud.IP formatted / HTML text)
     */
    public static function getText($user_id)
    {
        $value = (string) \Config::get()->OPENCAST_TOS;
        $texts = json_decode($value, true);

        // Texts from before the i18n migration are stored as plain string.
        if (!is_array($texts)) {
            return $value;
        }

        $language = getUserLanguage($user_id);
        if (!empty($texts[$language])) {
            return $texts[$language];
        }

        foreach ($texts as $text) {
            if (!empty($text)) {
                return $text;
            }
        }

        return '';
    }
}
