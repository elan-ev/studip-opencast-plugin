<?

class OCPerm
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
        if (is_null($context_id)) {
            $context_id = Context::getId();
        }

        //get permission level for editing episodes
        $requiredPerm = Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';

        return $GLOBALS['perm']->have_studip_perm($requiredPerm, $context_id, $user_id);
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
}
