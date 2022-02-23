<?php

namespace Opencast\Models\LTI;

use Opencast\Models\SeminarSeries;
use Opencast\Models\REST\ApiSeriesClient;
use Opencast\Providers\Perm;

class ACL
{
    public static function getDefaultVisibility($course_id)
    {
        $vis_conf = !is_null(\CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            ? boolval(\CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;

        return $vis_conf
            ? 'invisible'
            : 'visible';
    }

    private static function getDefaultACL($course_id)
    {
        return [
            [
                'allow'  => true,
                'role'   => $course_id .'_Instructor',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => $course_id .'_Instructor',
                'action' => 'write'
            ]
        ];
    }

    private static function filterForCourse($course_id, $acl)
    {
        $possible_roles = [
            $course_id . '_Instructor',
            $course_id . '_Learner',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) !== false) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private static function addCourseAcl($course_id, $add_acl, $acl)
    {
        $possible_roles = [
            $course_id . '_Instructor',
            $course_id . '_Learner',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) === false) {
                $result[] = $entry;
            }
        }

        return array_merge($result, $add_acl);
    }

    public static function setForSeries($course_id, $config_id, $series_id)
    {
        $vis = self::getDefaultVisibility($course_id);

        // a series can be linked in multiple courses
        // the acls of all the other courses need to be preserved

        // get configured visibility for series
        $series = SeminarSeries::findBySql('seminar_id = ? AND series_id = ?', [
            $course_id,
            $series_id
        ])[0];

        $acl = self::getDefaultACL($course_id);

        if ($series['visibility'] == 'visible') {
            $acl[] = [
               'allow'  => true,
               'role'   => $course_id .'_Learner',
               'action' => 'read'
           ];
        }

        // get visibility from opencast
        $sclient = new ApiSeriesClient($config_id);
        $oc_acl  = $sclient->getACL($series_id);

        if (!empty(array_diff($acl, self::filterForCourse($course_id, $oc_acl)))) {
            // update needed!
            $new_acl = self::addCourseAcl($course_id, $acl, $oc_acl);
            $sclient->setAcl($series_id, $new_acl);
        }
    }

    public static function setForEpisode($course_id, $series_id, $episode_id)
    {
        $vis = self::getDefaultVisibility($course_id);

        // an episode can be in series that are linked in multiple courses
        // the acls of all the other courses need to be preserved

        // get configured visibility for episode
        // get visibility from opencast
    }

    public static function updateForCourse($course_id)
    {

    }

    public static function updateEpisodeVisibility($course_id)
    {
        // check currently set ACLs to update status in Stud.IP if necessary
        $series_list = SeminarSeries::getSeries($course_id);

        foreach ($series_list as $series) {
            $search_client = SearchClient::getInstance($series['config_id']);
            $api_client    = ApiEventsClient::getInstance($series['config_id']);

            // check the opencast visibility for episodes and update Stud.IP settings
            foreach ($search_client->getEpisodes($series['series_id']) as $episode) {
                $vis = $api_client->getVisibilityForEpisode($series['series_id'], $episode->id, $course_id);

                $entry = SeminarEpisodes::findOneBySQL(
                    'series_id = ? AND episode_id = ? AND seminar_id = ?',
                    [$series['series_id'], $episode->id, $course_id]
                );

                if ($entry && $entry->visible != $vis) {
                    $entry->visible = $vis;
                    $entry->store();
                }
            }
        }
    }

    public static function getUploadXML($course_id)
    {
        $vis = !is_null(\CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            ? boolval(\CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;

        $oc_acl = '';
        if (Perm::editAllowed($course_id)) {
            $oc_acl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Policy PolicyId="mediapackage-1"
                RuleCombiningAlgId="urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:permit-overrides"
                Version="2.0"
                xmlns="urn:oasis:names:tc:xacml:2.0:policy:schema:os">
                <Rule RuleId="user_read_Permit" Effect="Permit">
                <Target>
                  <Actions>
                    <Action>
                      <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                        <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
                          DataType="http://www.w3.org/2001/XMLSchema#string"/>
                      </ActionMatch>
                    </Action>
                  </Actions>
                </Target>
                <Condition>
                  <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                    <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Instructor</AttributeValue>
                    <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
                      DataType="http://www.w3.org/2001/XMLSchema#string"/>
                  </Apply>
                </Condition>
                </Rule>';

            if($vis == false) {
                $oc_acl .='<Rule RuleId="user_read_Permit" Effect="Permit">
                    <Target>
                      <Actions>
                        <Action>
                          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
                              DataType="http://www.w3.org/2001/XMLSchema#string"/>
                          </ActionMatch>
                        </Action>
                      </Actions>
                    </Target>
                    <Condition>
                      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Learner</AttributeValue>
                        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
                          DataType="http://www.w3.org/2001/XMLSchema#string"/>
                      </Apply>
                    </Condition>
                    </Rule>';
            }

            $oc_acl.='
                <Rule RuleId="user_write_Permit" Effect="Permit">
                <Target>
                  <Actions>
                    <Action>
                      <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
                        <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
                          DataType="http://www.w3.org/2001/XMLSchema#string"/>
                      </ActionMatch>
                    </Action>
                  </Actions>
                </Target>
                <Condition>
                  <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                    <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Instructor</AttributeValue>
                    <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
                      DataType="http://www.w3.org/2001/XMLSchema#string"/>
                  </Apply>
                </Condition>
                </Rule>
                <Rule RuleId="ROLE_ADMIN_read_Permit" Effect="Permit">
                <Target>
                  <Actions>
                    <Action>
                      <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                        <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                      </ActionMatch>
                    </Action>
                  </Actions>
                </Target>
                <Condition>
                  <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                    <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_ADMIN</AttributeValue>
                    <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                  </Apply>
                </Condition>
                </Rule>
                <Rule RuleId="ROLE_ADMIN_write_Permit" Effect="Permit">
                <Target>
                  <Actions>
                    <Action>
                      <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
                        <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                      </ActionMatch>
                    </Action>
                  </Actions>
                </Target>
                <Condition>
                  <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                    <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_ADMIN</AttributeValue>
                    <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                  </Apply>
                </Condition>
                </Rule>
                </Policy>
                ';

            $instructor_role = $course_id . '_Instructor';
            $learner_role    = $course_id . '_Learner';
            $oc_acl          = str_replace('ROLE_USER_LTI_Instructor', $instructor_role, $oc_acl);

            if ($vis == false) {
                $oc_acl      = str_replace('ROLE_USER_LTI_Learner', $learner_role, $oc_acl);
            }

            $oc_acl          = str_replace(["\r", "\n"], '', $oc_acl);
            $oc_acl          = urlencode($oc_acl);
        }

        return $oc_acl;
    }
}
