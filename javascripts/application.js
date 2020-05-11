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

	function addACL(mediaPackage,acl) {



	    var acldata = new FormData();
	    acldata.append('mediaPackage', mediaPackage);
            acldata.append('flavor', 'security/xacml+episode');
            acldata.append('BODY', new Blob([acl]), 'acl.xml');

            return $.ajax({
                url: serviceUrl + "/ingest/addAttachment",
                method: "POST",
                data: acldata,
                processData: false,
                contentType: false,
                xhrFields: { withCredentials: true },
            })


        }

        function uploadTracks(mediaPackage, files, onProgress) {
            return files.reduce(function(promise, file) {
                return promise.then(function (mediaPackage) {
                    return addTrack(mediaPackage, file, onProgress);
                });
            }, Promise.resolve(mediaPackage))
        }

        function addTrack(mediaPackage, track, onProgress) {
            var media = track.file;
            var data = new FormData();
            data.append('mediaPackage', mediaPackage);
            data.append('flavor', track.flavor);
            data.append('tags', '');
            data.append('BODY', media, media.name);

            var fnOnProgress = function (event) {
                onProgress(track, event.loaded, event.total);
            };

            return new Promise(
                function (resolve, reject) {
                    var xhr = $.ajax({
                        xhr: createProgressAwareXhr(fnOnProgress),
                        url: serviceUrl + "/ingest/addTrack",
                        method: "POST",
                        data: data,
                        processData: false,
                        contentType: false,
                        xhrFields: { withCredentials: true },
                    });
                    xhr.done(function (_data, _status, xhr) {
                        resolve(xhr.responseText);
                    })
                    xhr.fail(function (xhr, status, error) {
                        reject([xhr, status, error]);
                    });
                }
            );
        }

        function createProgressAwareXhr(onProgress) {
            return function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", onProgress, false);
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

        function upload(files, terms, workflowId, onProgress) {
            return getMediaPackage()
                .then(function (_mediaPackage, _status, resp) {
                    return addDCCCatalog(resp.responseText, terms)
                })
		.then(function (_mediaPackage, _status, resp) {
		    var acl = terms.oc_acl;
		    return addACL(resp.responseText, acl)
                })
                .then(function (_mediaPackage, _status, resp) {
                    return uploadTracks(resp.responseText, files, onProgress)
                })
                .then(function (mediaPackage) {
                    return finishIngest(mediaPackage, workflowId)
                })
        }

        var uploadMedia = []

        var fileTemplate = _.template(
            "<span><b>Name:</b> <%- name %></span>"+
            "<span><b>Gr&ouml;&szlig;e:</b> <%- size %></span>"+
            "<span><select disabled>"+
              "<option value='presenter/source'>Vortragende*r</option>"+
              "<option value='presentation/source'>Folien</option>"+
            "</select></span>"+
            "<span><button class='button cancel' type=button>Entfernen</button></span>"
        );

        function renderFiles() {
            $(".oc-media-upload-info").empty()

            uploadMedia.forEach(function (item, index) {
                var li = $("<li></li>").html(
                    fileTemplate({ name: item.file.name, size: OC.getFileSize(item.file.size) })
                );
                li.appendTo(".oc-media-upload-info").find("select").val(item.flavor);

                $(".oc-media-upload-add[data-flavor='"+item.flavor+"']").hide();

                li.on("change", "select", function () {
                    item.flavor = $(this).val()
                })
                li.on("click", "button", function () {
                    uploadMedia = [...uploadMedia.slice(0, index), ...uploadMedia.slice(index + 1)];
                    renderFiles();
                    $(".video_upload[data-flavor='"+item.flavor+"']").trigger("reset-button");
                })
            })
        }

        function onFileChange(event) {
            var target = event.target
            if (!target.files || target.files.length !== 1) {
                return;
            }
            var file = target.files[0];
            var flavor  = $(target).data("flavor");

            uploadMedia.push({ file: file, flavor: flavor, progress: { loaded: 0, total: file.size }});
            renderFiles();
        }

        function redirectAfterUpload(status) {
            window.open(STUDIP.URLHelper.getURL("plugins.php/opencast/course/index/false/"+(status ? 'true' : 'false')), '_self');
        }

        function showUploadProgress(media) {
            var force = false;
            var uploadDialog = $("#oc-media-upload-dialog").html()
            var origin = $("<div></div>")
            origin.on("dialog-open", function(_event, options) {
                var dialog = options.dialog;
                var throttledRender = _.throttle(renderProgress, 200);
                $(dialog).on("dialogbeforeclose", function () {
                    return force || confirm("Wollen Sie den Medien-Upload wirklich abbrechen?")
                });
                throttledRender();
                origin.on("upload-progress", throttledRender);

                function renderProgress() {
                    const liTemplate = _.template(`
                      <span><%- name %>: </span>
                      <progress title="<%- loaded %>/<%- total %>"
                                value="<%- loaded %>" max="<%- total %>">
                      </progress>
                      <span><%= percent %>%</span`);

                    const $ul = $("ul.files", dialog);
                    $ul.empty();
                    media.forEach(function (item) {
                        const fileLi = $("<li></li>").html(liTemplate(
                            {
                                name: item.file.name,
                                size: OC.getFileSize(item.file.size),
                                ...item.progress,
                                percent: Math.round(100 * item.progress.loaded / item.progress.total)
                            }));
                        fileLi.appendTo($ul);
                    });
                }
            });

            var options = {
                buttons: false,
                origin,
                size: '500x350',
                title: $("#oc-media-upload-dialog h1").text()
            };
            STUDIP.Dialog.show(uploadDialog, options);

            return origin
        }

        jQuery(document).ready(function ($) {
            var seriesId = window.OC.parameters.seriesId;
            var workflowId = window.OC.parameters.uploadWorkflowId;

            $(document).on('change', '.video_upload', onFileChange)

            $(document).on('click', '.oc-media-upload-add', function (event) {
                event.preventDefault();
                var $button = $(this);
                var $input = $(this).next('input');
                $input.trigger('click');
                $input.one("reset-button", function () {
                    $button.show();
                });
            });

            $('#upload_form').submit(function () {
                if (!uploadMedia.length) {
                    STUDIP.Dialog.show("Sie müssen mindestens ein Video auswählen.", {
                        title: 'Fehler',
                        size: 'small'
                    });
                    return false;
                }

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
                    seriesId: seriesId,
                    oc_acl: decodeURIComponent(formFields.oc_acl).replace(/\+/g," ")
                }

                const dialog = showUploadProgress(uploadMedia);

                var onProgress = function (file, loaded, total) {
                    file.progress = { loaded, total };
                    dialog.trigger("upload-progress");
                };
                var onSuccess = () => {
                    this.dataset.isUploading = false
                    redirectAfterUpload(true);
                };
                var onError = function (error) {
                    console.error(error);
                    redirectAfterUpload(false);
                };

                upload(uploadMedia, terms, workflowId, onProgress)
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
                      alert('Die Sichtbarkeit kann momentan nicht geändert werden! Opencast arbeitet momentan an diesem Video.');
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
    },

    askForConfirmation: function(text) {
        if (!confirm((text))) {
            event.preventDefault();
            return false;
        }

        return true;
    }
};

window.OC = OC
