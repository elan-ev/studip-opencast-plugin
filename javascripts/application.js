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

                // Workflow Config Dialog
                jQuery("#workflow_dialog").dialog({ autoOpen: false, width: 800, dialogClass: 'ocWorkflow'});
                jQuery("#oc_workflow_dialog").click(
                    function () {
                        jQuery("#workflow_dialog").dialog('open');
                        return false;
                    }
                );

                jQuery( "#oce_sortablelist" ).sortable({
                    items: '> li:not(.uploaded)',
                    stop: function( event, ui ) {
                        var items = [];
                        jQuery( "ul#oce_sortablelist li" ).each(function(index){
                            items.push({
                                'episode_id' : jQuery( this ).attr('id'),
                                'position' :  index,
                                'course_id' : jQuery( this ).data('courseid'),
                                'visibility' : jQuery( this ).data('visibility'),
                                'mkdate' : jQuery( this).data('mkdate'),
                                'oldpos' : jQuery(this).data('pos')
                            });
                            if(jQuery("#oc-togglevis").data('episode-id') === jQuery( this ).attr('id')) {
                                var new_url =  STUDIP.URLHelper.getURL("plugins.php/opencast/course/toggle_visibility/" + jQuery( this ).attr('id') + "/" + index);
                                jQuery("#oc-togglevis").attr('href', new_url);
                            }
                        });

                        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/setEpisodeOrdersForCourse/",
                            { "positions": items });
                    }
                });
                jQuery( "#oce_sortablelist" ).disableSelection();

                // toggle visibility
                OC.toggleVis(cid);

            } else {
                jQuery('.hidden_ocvideodiv').remove();
            }


            if(OC.states && STUDIP.hasperm){
                OC.getWorkflowProgressForCourse(cid, true);
            }

            // take care of episodelist
            OC.searchEpisodeList();
            OC.episodeListener(cid);

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
                                                + ' Größe: '
                                                + OC.getFileSize(file.size) 
                                                + '</p>');
                    $('#upload_info').val(file.name);
                    OC.formData = data;
                    return false;
                },
                submit: function (e, data) {
                    $("#btn_accept").attr('disabled', true);
                    $( "#progressbar" ).progressbar({
                        value: 0
                    }).addClass('oc_mediaupload_progressbar').show().css({'background': '#d0d7e4'});;
                    
                },
                progressall: function(e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    jQuery( "#progressbar" ).progressbar( "value", progress);
                    jQuery("#progressbar-label").text(progress + " %");
                },
                done: function(e, data) {
                    jQuery( "#progressbar" ).progressbar('destroy');
                    jQuery("#upload_dialog").dialog("close");
                    window.open(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/index/false/true", '_self');
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

    getWorkflowProgressForCourse: function(course_id, animation) {
        var reload = false;
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/getWorkflowStatusforCourse/" +  course_id).done(function(data) {
            var response = jQuery.parseJSON(data);
            if(!jQuery.isEmptyObject(response)){
                for (var job_id in response) {

                    var job = response[job_id];

                    if(job.state == 'RUNNING' ||job.state == 'INSTANTIATED' ) {

                        var counter = 1;
                        var current_c = counter;
                        var current_description = "";

                        for( var operation in job.operations.operation){
                            if(job.operations.operation[operation].state != 'SKIPPED' || 'FAILED'){
                                counter++;
                            }
                            if(job.operations.operation[operation].state == 'RUNNING'){
                                current_description = job.operations.operation[operation].description;
                                current_c = counter;
                            }
                        }

                        if(animation){
                            jQuery('#'+job_id).circleProgress({
                                value: current_c / counter,
                                size: 80,
                                fill: { color: "#899ab9"}
                            });
                        } else {
                            jQuery('#'+job_id).circleProgress({
                                value: current_c / counter,
                                size: 80,
                                animation: false,
                                fill: { color: "#899ab9"}
                            });
                        }


                        jQuery('#'+job_id).find('strong').html( current_c +' / ' + counter + ' Schritten');
                        jQuery('#'+job_id).attr('title', current_description);
                        jQuery('#'+job_id).attr('alt', current_description);
                    }
                    else {
                        reload = true;
                    }
                }

            } if(reload || response == ""){
                window.open(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/course/index/false", '_self');
            } else window.setTimeout(function(){OC.getWorkflowProgressForCourse(course_id, false)}, 25000)

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
        var oce_list = _.template(episodes_template,{episodes:episodes, active:active_id});


        jQuery('.oce_list').empty();
        jQuery('.oce_list').html(oce_list);


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
                var player_template = jQuery('#playerTemplate').html();
                var player = _.template(player_template,{episode:episode, theodul:data.theodul, embed:data.embed,dozent:dozent,engage_player_url:data.engage_player_url});

                jQuery('.oce_playercontainer').empty();
                jQuery('.oce_playercontainer').html(player);
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
    }
};

