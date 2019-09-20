OC = {
    lti_done: 0,
    formData: {},
    initAdmin: function () {
        jQuery(document).ready(function () {
            jQuery('#admin-accordion').accordion();
        });
    },

    ltiCall: async function(lti_url, lti_data, success_callback) {
        while (OC.lti_done == 1) {
            await new Promise(resolve => setTimeout(resolve, 300));
        }

        if (OC.lti_done == 2) {
            success_callback();
        } else {
            OC.lti_done = 1;
            // send credentials to opencast lti backend, setting session cookie for oc domain
            $.ajax({
                type: "POST",
                url: lti_url,
                data:  lti_data,
                xhrFields: {
                   withCredentials: true
                },
                crossDomain: true,
                complete: function() {
                    OC.lti_done = 2;
                    success_callback();
                }
            });
        }
    },

    initIndexpage: function () {
        jQuery(document).ready(function () {

            var cid = jQuery('#course_id').data('courseid');

            //DownloadDialog (for every perm)
            jQuery('[id^="download_dialog"]').each(function (index, element) {
                jQuery(element).dialog({autoOpen: false, width: 800, dialogClass: 'ocDownload'});
            });
            jQuery(".oc_download_dialog").each(function (index, element) {
                jQuery(element).click(function () {
                    jQuery("#download_dialog-" + jQuery(element).data('episode_id')).dialog('open');
                    return false;
                });
            });

            if (STUDIP.hasperm) {
                // toggle visibility
                OC.toggleVis(cid);

            } else {
                jQuery('.hidden_ocvideodiv').remove();
            }


            if (OC.states && STUDIP.hasperm) {
                OC.getWorkflowProgressForCourse(cid, true, null);
            }
        });

    },

    initUpload: function (maxChunk) {
        jQuery(document).ready(function () {
            $('#btn_accept').click(function () {
                OC.formData.submit();
                return false;
            });

            $('#video_upload').fileupload({
                limitMultiFileUploads: 1,
                autoupload: false,
                maxChunkSize: maxChunk,
                add: function (e, data) {
                    var file = data.files[0];
                    $('#total_file_size').attr('value', file.size);
                    $('#file_name').attr('value', file.name);
                    $('#upload_info').html('<p>Name: '
                        + file.name
                        + ' Gr&ouml;&szlig;e: '
                        + OC.getFileSize(file.size)
                        + '</p>');
                    $('#upload_info').val(file.name);
                    OC.formData = data;
                    return false;
                },
                submit: function (e, data) {
                    $("#btn_accept").attr('disabled', true);
                    $("#progressbar").progressbar({
                        value: 0
                    }).addClass('oc_mediaupload_progressbar').show().css({'background': '#d0d7e4'});
                },
                progressall: function (e, data) {
                    if (data.bitrate / 8 > 1048576) {
                        var speed = parseInt(data.bitrate / 8 / 1024 / 1024) + " Mb/s";
                    } else {
                        var speed = parseInt(data.bitrate / 8 / 1024) + " kb/s";
                    }

                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    jQuery("#progressbar").progressbar("value", progress);
                    jQuery("#progressbar-label").text(progress + " % / " + speed);
                },
                done: function (e, data) {
                    jQuery("#progressbar").progressbar('destroy');
                    jQuery("#upload_dialog").dialog("close");
                    window.open(STUDIP.URLHelper.getURL("plugins.php/opencast/course/index/false/true"), '_self');
                },
                error: function (xhr, data) {
                    console.log('Fehler', data);
                }
            });
        })
    },
    getFileSize: function (input) {
        if (input / 1024 > 1) {
            var inp_kb = Math.round((input / 1024) * 100) / 100
            if (inp_kb / 1024 > 1) {
                var inp_mb = Math.round((inp_kb / 1024) * 100) / 100
                if (inp_mb / 1024 > 1) {
                    var inp_gb = Math.round((inp_mb / 1024) * 100) / 100
                    return inp_gb + 'GB';
                }
                return inp_mb + 'MB';
            }
            return inp_kb + 'KB';
        }
        return input + 'Bytes'
    },

    getWorkflowProgressForCourse: function (course_id, animation, info) {

        var reload = false;
        if (info == null) {
            info = [];
            info[0] = [0, 0, 0];
        }
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/getWorkflowStatusforCourse/" + course_id).done(function (data) {
            var response = jQuery.parseJSON(data);

            if (!jQuery.isEmptyObject(response)) {
                for (var job_id in response) {

                    var job = response[job_id];

                    if (job.state == 'RUNNING' || job.state == 'INSTANTIATED'  || job.state == 'STOPPED') {

                        var counter = 1;
                        var current_description = "";

                        for (var operation in job.operations.operation) {
                            counter++;

                            if (job.operations.operation[operation].state == 'RUNNING'
                                || job.operations.operation[operation].state == 'INSTANTIATED'
                            ) {
                                current_description = job.operations.operation[operation].description;
                                break;
                            }
                        }

                        info[info.length] = [
                            (counter > job.operations.operation.length ? job.operations.operation.length : counter),
                            job.operations.operation.length,
                            counter / job.operations.operation.length
                        ];

                        jQuery('#' + job_id).attr('title', counter + '/' + job.operations.operation.length + ': ' + current_description);
                        jQuery('#' + job_id).attr('alt', counter + '/' + job.operations.operation.length + ': ' + current_description);
                    }
                    else if (jQuery('#' + job_id).length > 0) {
                        reload = true;
                    }
                }

            }
            if ((reload || response == "")) {
                window.open(STUDIP.URLHelper.getURL("plugins.php/opencast/course/index/false/true"), '_self');
            } else {
                window.setTimeout(function () {
                    OC.getWorkflowProgressForCourse(course_id, false, info)
                }, 5000)
            }

        });

    },

    toggleVis: function (cid) {
        jQuery('.oc-togglevis').bind('click', function (e) {
            var episode_id = jQuery(this).data("episode-id");
            var title      = 'Sichtbarkeit - ' + jQuery('#' + episode_id + ' .oce_list_title').text();
            var visibility = jQuery(this).attr('data-visibility');

            e.preventDefault();

            $('#visibility_dialog input[value=' + visibility + ']')
                .attr('checked', true);

            $('#visibility_dialog').attr('data-episode_id', episode_id)

            $('#visibility_dialog').dialog({
                modal: true,
                title: title,
                size: 'auto',
                resize: false
            });
        });

    },

    setVisibility: function(visibility, episode_id) {
        var cid        = jQuery('#course_id').data('courseid');
        var $element   = jQuery('a[data-episode-id=' + episode_id + ']');

        $element.attr('disabled', 'disabled');

        $('#visibility_dialog').dialog('close');

        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP
                + "plugins.php/opencast/course/permission/"
                + episode_id + "/" + visibility + "?cid=" + cid,
            function(response) {
                $element
                    .removeClass('ocinvisible ocvisible ocfree')
                    .addClass('oc' + response.visible)
                    .text(OC.visibility_text[response.visible])
                    .attr('disabled', false);
            }
        ).fail(function(response) {
            alert('Warten Sie mindestens 2 Minuten, bevor Sie die Sichtbarkeit für diese Video erneut ändern! Opencast muss die vorherige Sichtbarkeitsänderung erst anwenden.');
            $element
                .attr('disabled', false);
        });
    },

    // schedule setting
    initScheduler: function () {
        $('.wfselect').change(function () {


            var workflow_id = $("option:selected", this).attr('value');
            var termin_id = $("option:selected", this).data('terminid');
            var resource_id = $("option:selected", this).data('resource');


            jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/setworkflowforscheduledepisode/" + termin_id + "/" + workflow_id + "/" + resource_id).done(function (data) {
                if (!jQuery.isEmptyObject(data)) {
                    //todo trigger success message
                    if (data === 'true') {
                        //console.log('lööpt'); TODO STUD.IP Success Box triggern
                    } else {
                        alert('Der Workflow konnte für die geplante Aufzeichnung nicht gesetzt werden.')
                    }

                }
            });


        })
    }
};
