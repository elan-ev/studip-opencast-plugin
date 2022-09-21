<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

/**
 * Find the playlists for the passed course
 */
class MyCourseList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        // get id of opencast plugin to check if it is activated for selected courses
        $plugin_id = \DBManager::get()->query("SELECT pluginid
            FROM plugins WHERE pluginname = 'OpenCast'")->fetchColumn();

        if (!$perm->have_perm('admin')) {
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminar_id FROM seminar_user
                JOIN tools_activated ON (
                    tools_activated.range_id = seminar_id
                    AND tools_activated.plugin_id = :plugin_id
                )
                WHERE user_id = :user_id
                    AND (seminar_user.status = 'dozent' OR seminar_user.status = 'tutor')

            ");

            $stmt->execute([
                ':user_id'   => $user->id,
                ':plugin_id' => $plugin_id
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else if (!$perm->have_perm('root')) {
            $params = $request->getQueryParams();

            if (!$params['search']) {
                $params['search'] = '%%%';
                //return $this->createResponse([], $response);
            }

            $institute_ids = [];
            $institutes = new \SimpleCollection(\Institute::getMyInstitutes($user->id));
            $institutes->filter(function ($a) use (&$institute_ids) {
                $institute_ids[] = $a->Institut_id;
            });

            // get courses for admins
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminare.Seminar_id FROM seminare
                JOIN tools_activated ON (
                    tools_activated.range_id = seminar_id
                    AND tools_activated.plugin_id = :plugin_id
                )
                INNER JOIN seminar_inst ON (seminare.Seminar_id = seminar_inst.seminar_id)
                INNER JOIN Institute ON (seminar_inst.institut_id = Institute.Institut_id)
                LEFT JOIN sem_types ON (sem_types.id = seminare.status)
                LEFT JOIN sem_classes ON (sem_classes.id = sem_types.class)
                INNER JOIN seminar_user AS dozenten ON (dozenten.Seminar_id = seminare.Seminar_id AND dozenten.status = 'dozent')
                INNER JOIN auth_user_md5 AS dozentendata ON (dozenten.user_id = dozentendata.user_id)
                WHERE sem_classes.studygroup_mode = '0'
                    AND (
                        CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.name, seminare.Untertitel, dozentendata.Nachname) LIKE :search
                        OR CONCAT(dozentendata.Nachname, ', ', dozentendata.Vorname) LIKE :search
                        OR CONCAT_WS(' ', dozentendata.Vorname, dozentendata.Nachname) LIKE :search
                        OR dozentendata.Vorname LIKE :search OR dozentendata.Nachname LIKE :search
                    )
                    AND seminar_inst.institut_id IN (:institute_ids)
            ");

            $stmt->execute([
                ':search'        => $params['search'],
                ':institute_ids' => $institute_ids,
                ':plugin_id'     => $plugin_id
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminar_id FROM seminar_user
                 JOIN tools_activated ON (
                    tools_activated.range_id = seminar_id
                    AND tools_activated.plugin_id = :plugin_id
                )
                WHERE 1
            ");

            $stmt->execute([
                ':plugin_id' => $plugin_id
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }


        $results = [];

        // NOTE: This class is also used in VideoAddToSeminar.vue, in order for dozents to select the courses.
        // Any changes applied to the result's formation of this class must be also implemented in that component!
        foreach ($courses as $course_id) {
            $course = \Course::find($course_id);
            $results['S'. $course->end_semester->beginn ?: '0'][$course->getFullname('sem-duration-name')][] = [
                'id'       => $course->id,
                'name'     => $course->getFullname()
            ];

        }

        krsort($results);

        return $this->createResponse($results, $response);
    }
}