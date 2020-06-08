<?php

namespace Opencast\Models\LTI;

use \Config;
use \User;
use \Course;
use \Avatar;

/**
 * LtiLink.php - LTI 1.x link representation for Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 */

/**
 * The LtiLink class represents an LTI 1.x link inside the LMS. It stores the
 * launch URL and the corresponding credentials as well as a list of launch
 * parameters and custom parameter substitution variables.
 *
 * Use LtiLink::getLaunchSignature() to fetch the OAuth signature to use for
 * the launch request (or some other request).
 */
class LtiLink
{
    // launch URL and credentials
    protected $launch_url;
    protected $consumer_key;
    protected $consumer_secret;

    // launch parameters and variables
    protected $parameters = [];
    protected $variables = [];

    /**
     * Iniialize a new LtiLink instance with the given URL and credentials.
     *
     * @param string $launch_url       launch URL of external LTI tool
     * @param string $consumer_key     consumer key of the LTI link
     * @param string $consumer_secret  consumer secret of the LTI link
     */
    public function __construct($launch_url, $consumer_key, $consumer_secret)
    {
        $this->launch_url = $launch_url;
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        // Basic LTI uses OAuth to sign requests
        // OAuth Core 1.0 spec: http://oauth.net/core/1.0/
        $this->addLaunchParameters([
            'lti_version' => 'LTI-1p0',
            'lti_message_type' => 'basic-lti-launch-request',
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_version' => '1.0',
            'oauth_nonce' => uniqid('lti', true),
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'tool_consumer_info_product_family_code' => 'studip',
            'tool_consumer_info_version' => $GLOBALS['SOFTWARE_VERSION'],
            'tool_consumer_instance_guid' => Config::get()->STUDIP_INSTALLATION_ID,
            'tool_consumer_instance_name' => Config::get()->UNI_NAME_CLEAN,
            'tool_consumer_instance_description' => $GLOBALS['UNI_INFO'],
            'tool_consumer_instance_url' => $GLOBALS['ABSOLUTE_URI_STUDIP'],
            'tool_consumer_instance_contact_email' => $GLOBALS['UNI_CONTACT']
        ]);
    }

    /**
     * Set the LMS resource associated with this LTI link. This is required
     * for an LTI launch request.
     *
     * @param string $resource_id      id of associated resource
     * @param string $resource_title   title of associated resource
     * @param string $resource_description description of associated resource
     */
    public function setResource($resource_id, $resource_title, $resource_description = null)
    {
        $this->addVariables([
            'ResourceLink.id' => $resource_id,
            'ResourceLink.title' => $resource_title,
            'ResourceLink.description' => $resource_description,
        ]);

        $this->addLaunchParameters([
            'resource_link_id' => $this->variables['ResourceLink.id'],
            'resource_link_title' => $this->variables['ResourceLink.title'],
            'resource_link_description' => $this->variables['ResourceLink.description'],
        ]);
    }

    /**
     * Set the Stud.IP course associated with this LTI link. The course data
     * is used to set up the context and course parameters and variables.
     *
     * @param string $course_id        id of associated course
     */
    public function setCourse($course_id)
    {
        $course = Course::find($course_id);

        $this->addVariable('Context.id', $course_id);
        $this->addLaunchParameter('context_id', $course_id);

        if ($course) {
            $this->addVariables([
                'Context.type' => 'CourseSection',
                'Context.label' => $course->veranstaltungsnummer,
                'Context.title' => $course->name,
                'CourseSection.sourcedId' => $course->id,
                'CourseSection.label' => $course->veranstaltungsnummer,
                'CourseSection.title' => $course->name,
                'CourseSection.shortDescription' => $course->untertitel,
                'CourseSection.longDescription' => $course->beschreibung,
                'CourseSection.courseNumber' => $course->veranstaltungsnummer,
                'CourseSection.credits' => $course->ects,
                'CourseSection.maxNumberofStudents' => $course->admission_turnout,
                'CourseSection.numberofStudents' => $course->getNumParticipants(),
                'CourseSection.dept' => $course->home_institut->name,
            ]);

            $this->addLaunchParameters([
                'context_type' => $this->variables['Context.type'],
                'context_label' => $this->variables['Context.label'],
                'context_title' => $this->variables['Context.title'],
            ]);
        }
    }

    /**
     * Set the Stud.IP user associated with this LTI launch. The user data
     * is used to set up the user and LIS person parameters and variables.
     * If send_lis_person is true, the user's name and e-mail is included.
     *
     * @param string $user_id          id of associated course
     * @param string $roles            roles of this user (defaults to 'Learner')
     * @param bool   $send_lis_person  include additional user information
     */
    public function setUser($user_id, $roles = 'Learner', $send_lis_person = false)
    {
        $user = User::find($user_id);
        $avatar = Avatar::getAvatar($user_id);

        $this->addVariable('User.id', $user_id);
        $this->addLaunchParameter('user_id', $user_id);
        $this->addLaunchParameter('roles', $roles);

        if ($user && $send_lis_person) {
            $this->addVariables([
                'User.image' => $avatar->getURL(Avatar::NORMAL),
                'User.username' => $user->username,
                'Person.sourcedId' => $user->username,
                'Person.name.full' => $user->getFullName(),
                'Person.name.family' => $user->nachname,
                'Person.name.given' => $user->vorname,
                'Person.name.prefix' => $user->title_front,
                'Person.name.suffix' => $user->title_rear,
                'Person.email.primary' => $user->email,
                'Person.webaddress' => $user->home,
            ]);

            $this->addLaunchParameters([
                'lis_person_name_full' => $this->variables['Person.name.full'],
                'lis_person_name_family' => $this->variables['Person.name.family'],
                'lis_person_name_given' => $this->variables['Person.name.given'],
                'lis_person_contact_email_primary' => $this->variables['Person.email.primary'],
                'lis_person_sourcedid' => $this->variables['Person.sourcedId'],
            ]);
        }
    }

    /**
     * Add an additional launch parameter to this LTI launch request.
     *
     * @param string $name      parameter name
     * @param string $value     value (use NULL to unset)
     */
    public function addLaunchParameter($name, $value)
    {
        if (isset($value)) {
            $this->parameters[$name] = (string) $value;
        } else {
            unset($this->parameters[$name]);
        }
    }

    /**
     * Add a list of additional launch parameters to this LTI launch request.
     *
     * @param string $params    list of launch parameters
     */
    public function addLaunchParameters($params)
    {
        foreach ($params as $key => $value) {
            $this->addLaunchParameter($key, $value);
        }
    }

    /**
     * Add a custom launch parameter to this LTI launch request. All custom
     * parameter names are prefixed with 'custom_' and variable substitution
     * is applied.
     *
     * @param string $name      parameter name
     * @param string $value     value (may contain variables)
     */
    public function addCustomParameter($name, $value)
    {
        $name = strtolower(preg_replace('/\W/', '_', $name));
        $value = preg_replace_callback('/\$([\w\.]*\w)/', function($matches) {
            return $this->variables[$matches[1]] ?: $matches[0];
        }, $value);

        $this->addLaunchParameter('custom_' . $name, $value);
    }

    /**
     * Add a list of custom launch parameters to this LTI launch request.
     *
     * @param string $params    list of custom parameters
     */
    public function addCustomParameters($params)
    {
        foreach ($params as $key => $value) {
            $this->addCustomParameter($key, $value);
        }
    }

    /**
     * Add a substitution variable to this LTI launch request.
     *
     * @param string $name      variable name
     * @param string $value     value (use NULL to unset)
     */
    public function addVariable($name, $value)
    {
        if (isset($value)) {
            $this->variables[$name] = $value;
        } else {
            unset($this->variables[$name]);
        }
    }

    /**
     * Add a list of substitution variables to this LTI launch request.
     *
     * @param string $variables list of substitution variables
     */
    public function addVariables($variables)
    {
        foreach ($variables as $key => $value) {
            $this->addVariable($key, $value);
        }
    }

    /**
     * Get the substitution variables defined for this LTI link.
     *
     * @return array   list of substitution variables
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Get the launch URL for this LTI link.
     *
     * @return string  launch URL of external LTI tool
     */
    public function getLaunchURL()
    {
        return $this->launch_url;
    }

    /**
     * Get the launch parameters for the LTI basic launch request.
     *
     * @return array   launch parameters (UTF-8 encoded)
     */
    public function getBasicLaunchData()
    {
        return $this->parameters;
    }

    /**
     * Sign a launch request including the given launch parameters.
     *
     * @param array $launch_params      array of launch parameters
     *
     * @return string   launch signature
     */
    public function getLaunchSignature($launch_params)
    {
        list($launch_url, $fragment) = explode('#', $this->launch_url);
        list($launch_url, $query)    = explode('?', $launch_url);

        if (isset($query)) {
            parse_str($query, $query_params);
            $launch_params += $query_params;
        }

        // In OAuth, request parameters must be sorted by name
        ksort($launch_params);
        $launch_params = http_build_query($launch_params, '', '&', PHP_QUERY_RFC3986);
        $base_string = 'POST&' . rawurlencode($launch_url) . '&' . rawurlencode($launch_params);
        $secret = rawurlencode($this->consumer_secret) . '&';

        return base64_encode(hash_hmac('sha1', $base_string, $secret, true));
    }
}
