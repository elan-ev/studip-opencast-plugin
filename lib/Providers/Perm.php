<?

namespace Opencast\Providers;

class Perm
{
    /**
     * checks, if the current user has lecturer rights in the oc plugin
     *
     * @param  string $context_id course or institute id
     * @param  string $user_id    user id
     *
     * @return boolean            true if allowed, false otherwise
     */
    public static function editAllowed($context_id = null, $user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }

        if (is_null($context_id)) {
            $context_id = \Context::getId();
        }

        //get permission level for editing episodes
        $requiredPerm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';

        if (\Config::get()->OPENCAST_MEDIA_ROLES) {
            // media tutors are allowed to upload videos in any seminar where they are tutor
            if (self::hasRole('Medientutor', $user_id)) {
                $requiredPerm = 'tutor';
            }

            // if the user is a media admin, check if the current course is under an institute the user has role perms on
            if (self::hasRole('Medienadmin', $user_id)) {
                $institutes = self::getRoleInstitutes('Medienadmin', $user_id);

                foreach ($institutes as $inst_id) {
                    if ($inst_id && self::courseBelongsToInstitute($context_id, $inst_id)) {
                        return true;
                    }
                }
            }
        }

        return $GLOBALS['perm']->have_studip_perm($requiredPerm, $context_id, $user_id);
    }

    /**
     * checks, if the current user has lecturer rights in the oc plugin
     *
     * @param  string $context_id course or institute id
     * @param  string $user_id    user id
     *
     * @return boolean            true if allowed, false otherwise
     */
    public static function uploadAllowed($context_id = null, $user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }

        if (is_null($context_id)) {
            $context_id = \Context::getId();
        }

        if (self::editAllowed($context_id, $user_id)) {
            return true;
        }

        // check if additional upload permissions for this course have been granted
        return \CourseConfig::get($context_id)->OPENCAST_ALLOW_STUDENT_UPLOAD ? true : false;
    }

    /**
     * checks, if the current user has lecturer rights in the oc plugin and
     * throws an Exception if not
     *
     * @throws AccessDeniedException
     *
     * @param  string $context_id course or institute id
     * @param  string $user_id    user id
     *
     * @return void              throws an Exception, if editing is not allowed
     */
    public static function checkEdit($context_id = null, $user_id = null)
    {
        if (!self::editAllowed($context_id, $user_id)) {
            throw new AccessDeniedException('Sie haben keine Berechtigung zum Zugriff auf diese Funktion.');
        }
    }

    /**
     * Check if the user has the passed role
     *
     * @param string $check_role
     * @param string $user_id
     *
     * @return boolean
     */
    public static function hasRole($check_role, $user_id = null)
    {
        global $user;

        if (is_null($user_id)) {
            $user_id = $user->id;
        }

        // check, if current user has role "Medientutor"
        $check_user = \User::find($user_id);

        if ($check_user) {
            foreach ($check_user->getRoles() as $role) {
                if ($role->rolename == $check_role) {
                    return true;
                }
            }
    }

        return false;
    }

    /**
     * Get institutes to assigned to the passed role (if any)
     *
     * @param string $check_role
     * @param string $user_id
     *
     * @return boolean
     */
    public static function getRoleInstitutes($check_role, $user_id)
    {
        global $user;

        if (is_null($user_id)) {
            $user_id = $user->id;
        }

        // check, if current user has role "Medientutor"
        $check_user = \User::find($user_id);

        if ($check_user) {
            foreach ($check_user->getRoles() as $role) {
                if ($role->rolename == $check_role) {
                    // array_filter is used to remove empty institute entries
                    return array_filter(\RolePersistence::getAssignedRoleInstitutes($user_id, $role->roleid));
                }
            }
        }

        return false;
    }

    /**
     * Check if the passed course belongs to the passed institute
     *
     * @param string $course_id
     * @param string $inst_id
     *
     * @return boolean
     */
    public static function courseBelongsToInstitute($course_id, $inst_id)
    {
        static $course;

        if (!$course[$course_id]) {
            $course[$course_id] = \Course::find($course_id);
        }

        // still no course found?
        if (!$course[$course_id]) {
            return false;
        }

        if ($course[$course_id]->home_institut->institut_id == $inst_id) {
            return true;
        }

        foreach ($course[$course_id]->institutes as $inst) {
            if ($inst->institut_id == $inst_id) {
                return true;
            }
        }

        return false;
    }
}
