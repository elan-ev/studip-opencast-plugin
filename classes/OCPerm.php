<?

class OCPerm
{

    static function check($requiredPerm, $context_id = null)
    {
        if (is_null($context_id)) {
            $context_id = Context::getId();
        }

        if (!$GLOBALS['perm']->have_studip_perm($requiredPerm, $context_id)) {
            throw new AccessDeniedException();
        }
    }
}
