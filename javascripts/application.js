const OC = {
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

    initUpload: function (serviceUrl) {
        function getMediaPackage() {
            return $.ajax({
                url: serviceUrl + "/ingest/createMediaPackage",
                xhrFields: { withCredentials: true },
            })
        }

        function createDCCCatalog(terms) {
            var escapeString = function (string) {
                return new XMLSerializer().serializeToString(new Text(string));
            };

            return '<?xml version="1.0" encoding="UTF-8"?>' +
                '<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/"' +
                '            xmlns:dcterms="http://purl.org/dc/terms/"' +
                '            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' +

            '<dcterms:creator>' + escapeString(terms.creator) + '</dcterms:creator>' +
                '<dcterms:contributor>' + escapeString(terms.contributor) + ' </dcterms:contributor>' +
                '<dcterms:subject>' + escapeString(terms.subject) + '</dcterms:subject>' +
                '<dcterms:created xsi:type="dcterms:W3CDTF">' + escapeString(terms.created) + '</dcterms:created>' +
                '<dcterms:description>' + escapeString(terms.description) + '</dcterms:description>' +
                '<dcterms:language><![CDATA[' + escapeString(terms.language) + ']]></dcterms:language>' +
                '<dcterms:title>'+ escapeString(terms.title) + '</dcterms:title>' +
                '<dcterms:isPartOf>'+ escapeString(terms.seriesId) + '</dcterms:isPartOf>' +
                '</dublincore>';
        }

        function addDCCCatalog(mediaPackage, terms) {
            // Prepare meta data
            var episodeDC = createDCCCatalog(terms);

            return $.ajax({
                url: serviceUrl + "/ingest/addDCCatalog",
                method: "POST",
                data: {
                    mediaPackage: mediaPackage,
                    dublinCore: episodeDC,
                    flavor: 'dublincore/episode'
                },
                xhrFields: { withCredentials: true },
            })
        }

        function uploadMedia(mediaPackage, media, onProgress) {
            var data = new FormData();
            data.append('mediaPackage', mediaPackage);
            data.append('flavor', "presentation/source");
            data.append('tags', '');
            data.append('BODY', media, media.name);

            return $.ajax({
                xhr: createProgressAwareXhr(onProgress),
                url: serviceUrl + "/ingest/addTrack",
                method: "POST",
                data: data,
                processData: false,
                contentType: false,
                xhrFields: { withCredentials: true },
            })
        }

        function createProgressAwareXhr(onProgress) {
            return function () {
                var xhr = new window.XMLHttpRequest();
                // TODO: xhr.upload.addEventListener("progress", onProgress, false);
                xhr.withCredentials = true;
                return xhr;
            }
        }

        function finishIngest(mediaPackage, workflowId = "upload") {
            return $.ajax({
                url: serviceUrl + "/ingest/ingest",
                method: "POST",
                data: {
                    mediaPackage: mediaPackage,
                    workflowDefinitionId: workflowId
                },
                xhrFields: { withCredentials: true },
            })
        }

        function upload(media, terms, workflowId, onProgress) {
            return getMediaPackage()
                .then(function (_mediaPackage, _status, resp) {
                    return addDCCCatalog(resp.responseText, terms)
                })
                .then(function (_mediaPackage, _status, resp) {
                    return uploadMedia(resp.responseText, media, onProgress)
                })
                .then(function (_mediaPackage, _status, resp) {
                    return finishIngest(resp.responseText, workflowId)
                })
        }

        function onFileChange(event) {
            var target = event.target
            if (!target.files || target.files.length !== 1) {
                return;
            }
            var file = target.files[0];

            $('#upload_info').html('<p>Name: '
                                   + file.name
                                   + ' Gr&ouml;&szlig;e: '
                                   + OC.getFileSize(file.size)
                                   + '</p>');
            $('#upload_info').val(file.name);
        }

        function redirectAfterUpload(status) {
            window.open(STUDIP.URLHelper.getURL("plugins.php/opencast/course/index/false/"+(status ? 'true' : 'false')), '_self');
        }

        function showUploadProgress(file) {
            var force = false;
            var uploadDialog = $("#oc-media-upload-dialog").html()
            var origin = $("<div></div>")
            origin.on("dialog-open", function(_event, options) {

                // prevent accidentally closing the dialog
                var dialog = options.dialog;
                $(dialog).on("dialogbeforeclose", function () {
                    return force || confirm("Wollen Sie den Medien-Upload wirklich abbrechen?")
                });

                // fill the filename line
                var fileSpan = _.template('<%= name %> (<%= size %>)')
                $("span.file", dialog).html(fileSpan({ name: file.name, size: OC.getFileSize(file.size) }))

                // start the progress bar
                $(".oc-media-upload-progress", dialog).progressbar({ value: false });
            });

            var options = {
                buttons: false,
                origin: origin,
                size: 'auto',
                title: $("#oc-media-upload-dialog h1").text()
            };
            STUDIP.Dialog.show(uploadDialog, options)

            return function () {
                force = true;
                STUDIP.Dialog.close(options)
            };
        }

        jQuery(document).ready(function ($) {
            var seriesId = window.OC.parameters.seriesId;
            var workflowId = window.OC.parameters.uploadWorkflowId;

            $(document).on('change', $('#video_upload'), onFileChange)

            $('#upload_form').submit(function () {
                if (this.dataset.isUploading && this.dataset.isUploading) {
                    return false;
                }
                this.dataset.isUploading = true

                var $form = $(this)
                var formFields = $form.serializeArray().reduce(
                    function (fields, field) {
                        fields[field.name] = field.value;
                        return fields;
                    },
                    {}
                );

                // workaround: if datetimepicker field was not
                // focused, it may not have been initialized yet
                STUDIP.UI.DateTimepicker.init();

                var terms = {
                    creator: formFields.creator,
                    contributor: formFields.contributor,
                    subject: formFields.subject,
                    created: $(this.recordDate).datepicker("getDate").toISOString(),
                    description: formFields.description,
                    language: formFields.language,
                    title: formFields.title,
                    seriesId: seriesId
                }

                var file = this.video.files[0];

                var closeProgressDialog = showUploadProgress(file);

                var onProgress = function () { console.log("upload progress", arguments); };
                var onSuccess = (function () {
                    this.dataset.isUploading = false
                    redirectAfterUpload(true);
                    // TODO: redirecting takes that much time that closing the dialog feels wrong
                    // closeProgressDialog();
                }).bind(this);
                var onError = function (error) {
                    console.error(error);
                    redirectAfterUpload(false);
                };

                upload(file, terms, workflowId, onProgress)
                    .then(onSuccess)
                    .catch(onError)

                return false;
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

window.OC = OC
