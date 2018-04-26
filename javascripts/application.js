OC = {
    formData : {},
    initAdmin : function(){
        jQuery(document).ready(function(){
            jQuery('#admin-accordion').accordion();
        });
    },
    initIndexpage: function(){
        jQuery( document ).ready(function() {

            var cid = jQuery('#course_id').data('courseid');

            if(STUDIP.hasperm) {
                 // Upload Dialog
                jQuery("#upload_dialog").dialog({ autoOpen: false, width: 800, dialogClass: 'ocUpload'});
                jQuery("#oc_upload_dialog").click(
                    function () {
                        jQuery("#upload_dialog").dialog('open');
                        return false;
                    }
                );
                // Config Dialog
                jQuery("#config_dialog").dialog({ autoOpen: false, width: 800, dialogClass: 'ocConfig', height: 350});
                jQuery("#oc_config_dialog").click(
                    function () {
                        jQuery("#config_dialog").dialog('open');
                        return false;
                    }
                );
                jQuery(".chosen-select").chosen({
                    disable_search_threshold: 10,
                    max_selected_options: 1,
                    no_results_text: "Oops, nothing found!",
                    width: "350px"
                });

                // toggle visibility
                OC.toggleVis(cid);

            } else {
                jQuery('.hidden_ocvideodiv').remove();
            }


            if(OC.states && STUDIP.hasperm){
                OC.getWorkflowProgressForCourse(cid, true, null);
            }

            // take care of episodelist
            OC.searchEpisodeList();
            //OC.episodeListener(cid);

        });

    },

    initUpload : function(maxChunk){
        jQuery(document).ready(function(){
            $('#btn_accept').click(function() {
                OC.formData.submit();
                return false;
            });

            $('#video_upload').fileupload({
                limitMultiFileUploads: 1,
                autoupload: false,
                maxChunkSize: maxChunk,
                add: function(e, data) {
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
                progressall: function(e, data) {
                    if (data.bitrate / 8 > 1048576) {
                        var speed = parseInt(data.bitrate / 8 / 1024 / 1024) + " Mb/s";
                    } else {
                        var speed = parseInt(data.bitrate / 8 / 1024) + " kb/s";
                    }

                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    jQuery( "#progressbar" ).progressbar( "value", progress);
                    jQuery("#progressbar-label").text(progress + " % / " + speed);
                },
                done: function(e, data) {
                    jQuery( "#progressbar" ).progressbar('destroy');
                    jQuery("#upload_dialog").dialog("close");
                    window.open(STUDIP.URLHelper.getURL("plugins.php/opencast/course/index/false/true"), '_self');
                },
                error: function(xhr, data) {
                    console.log('Fehler', data);
                }
            });
            $('#recordDate').datepicker({
                dateFormat: "yy-mm-dd"
            });
        })
    },
    getFileSize: function(input) {
        if(input/1024 > 1) {
            var inp_kb = Math.round((input/1024)*100)/100
            if(inp_kb/1024 > 1) {
                var inp_mb = Math.round((inp_kb/1024)*100)/100
                if(inp_mb/1024 > 1) {
                    var inp_gb = Math.round((inp_mb/1024)*100)/100
                    return inp_gb + 'GB';
                }
                return inp_mb + 'MB';
            }
            return inp_kb + 'KB';
        }
        return input + 'Bytes'
    },

    getWorkflowProgressForCourse: function(course_id, animation, info) {
        var reload = false;
        if(info == null){
            info = [];
            info[0] = [0,0,0];
        }
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/getWorkflowStatusforCourse/" +  course_id).done(function(data) {
            var response = jQuery.parseJSON(data);

            if (!jQuery.isEmptyObject(response)) {
                for (var job_id in response) {

                    var job = response[job_id];

                    if (job.state == 'RUNNING' || job.state == 'INSTANTIATED' ) {

                        var counter = 1;
                        var current_description = "";

                        for (var operation in job.operations.operation) {
                            counter++;

                            if (job.operations.operation[operation].state == 'RUNNING'
                                || job.operations.operation[operation].state == 'INSTANTIATED'
                            ){
                                current_description = job.operations.operation[operation].description;
                                break;
                            }
                        }

                        info[info.length] = [
                            counter,
                            job.operations.operation.length,
                            counter/job.operations.operation.length
                        ];

                        var base_value = 0;
                        var representational_value = 0;

                        for(var i = 0; i < info.length; i++) {
                            var step_data = info[i];
                            var next_index = i + 1;
                            var step_value = (1-base_value) * step_data[2];
                            representational_value = base_value + step_value;

                            if (next_index < info.length && step_data[1] < info[next_index][1]) {
                                base_value += step_value;
                            }
                        }

                        jQuery('#'+job_id).circleProgress({
                            value: representational_value,
                            size: 100,
                            animation: animation,
                            fill: { color: "#899ab9"}
                        });

                        var percent = representational_value * 100;
                        if(percent > 100){
                            percent = 100;
                        }

                        jQuery('#'+job_id).find('strong').html( percent.toPrecision(7) + "%" );
                        jQuery('#'+job_id).attr('title', current_description);
                        jQuery('#'+job_id).attr('alt', current_description);
                    }
                    else {
                        reload = true;
                    }
                }

            } if(reload || response == ""){
                window.open(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/index/false", '_self');
            } else window.setTimeout(function(){OC.getWorkflowProgressForCourse(course_id, false, info)}, 5000)

        });

    },

    toggleVis: function(cid){
        jQuery('#oc-togglevis').click(function(e) {
            e.preventDefault();
            var  episode_id = jQuery('#oc-togglevis').data("episode-id");
            var position =  jQuery('#oc-togglevis').data("position");
            jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/toggle_visibility/" +  episode_id + "/" + position + "?cid=" + cid ).done(function(data) {
                if(!jQuery.isEmptyObject(data)){
                    OC.renderEpisodeList(data);
                    OC.episodeListener(cid);
                }
            });
            if (jQuery('#oc-togglevis').hasClass('ocvisible')) {

                jQuery('#oc-togglevis').removeClass('ocvisible').addClass('ocinvisible').text('Aufzeichnung unsichtbar');


            } else {
                jQuery('#oc-togglevis').removeClass('ocinvisible').addClass('ocvisible').text('Aufzeichnung sichtbar');
            }
        });

    },

    renderEpisodeList: function(episodes) {

        var episodes_template = jQuery('#episodeList').html();
        var active_id = jQuery('#oc_active_episode').data('activeepisode');
        var oce_list = _.template(episodes_template);

        jQuery('.oce_list').empty();
        jQuery('.oce_list').html(oce_list({
            episodes: episodes,
            active : active_id
        }));
    },

    episodeListener: function(cid) {
        // open episode item
        jQuery('.oce_item').click(function(e){
            e.preventDefault();

            var episode_id = jQuery(this).attr('id');

            //todo animation / progessindication
            /*
            jQuery('html, body').animate({
                scrollTop: jQuery('#barTopFont').offset().top
            }, 1000); */
            jQuery('#oc_balls').show();
            jQuery('.oce_playercontainer').addClass('oc_opaque');

            jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/get_player/" +  episode_id + "/" +  cid).done(function(data) {

                var episode = data.episode_data;
                var dozent = data.perm;
                var player_template = _.template(jQuery('#playerTemplate').html());
                var player_template_data = {
                    episode:episode,
                    theodul:data.theodul,
                    dozent:dozent,
                    engage_player_url:data.engage_player_url,
                    video:data.video
                };

                jQuery('.oce_playercontainer').empty();
                jQuery('.oce_playercontainer').html(player_template(player_template_data));
                jQuery('#oc-togglevis').attr('href', STUDIP.URLHelper.getURL('plugins.php/opencast/course/toggle_visibility/' + episode_id  + '/' + episode.position));
                jQuery('.oce_playercontainer').removeClass('oc_opaque');
                jQuery('#oc_balls').hide();
                OC.toggleVis(cid);

            });

        });
    },

    searchEpisodeList: function(){
        var options = {
            valueNames: [ 'oce_list_title', 'oce_list_date' ]
        };

        var episodeList = new List('episodes', options);
    },

    // schedule setting
    initScheduler: function() {
        $('.wfselect').change(function(){


            var workflow_id = $("option:selected", this).attr('value');
            var termin_id   = $("option:selected", this).data('terminid');
            var resource_id = $("option:selected", this).data('resource');


            jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/setworkflowforscheduledepisode/" +  termin_id + "/" + workflow_id + "/" + resource_id).done(function(data) {
                if(!jQuery.isEmptyObject(data)){
                    console.log(data);
                    //todo trigger success message
                    if(data === 'true'){
                        //console.log('lööpt'); TODO STUD.IP Success Box triggern
                    } else {
                        alert('Der Workflow konnte für die geplante Aufzeichnung nicht gesetzt werden.')
                    }

                }
            });


        })
    }
};
