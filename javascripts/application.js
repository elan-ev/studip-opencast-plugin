OC = {
    formData : {},
    initAdmin : function(){
        jQuery(document).ready(function(){
            jQuery('#admin-accordion').accordion();
        });
    },
    initIndexpage: function(){
        jQuery( document ).ready(function() {
  
            var items = jQuery(".oce_list li");
            var numItems = items.size();
            var perPage = 20;
            if(numItems > perPage) {
                items.slice(perPage).hide();
                jQuery('#oce_pagination').pagination({
                        items: numItems,
                        itemsOnPage: perPage,
                        cssStyle: 'light-theme',
                        prevText: 'Vorherige',
                        nextText: 'Nächste',
                        onPageClick: function(pageNumber) {
                            var showFrom = perPage * (pageNumber - 1);
                            var showTo = showFrom + perPage;
                            items.hide().slice(showFrom, showTo).show();
                        }
                });
             }
             // Upload Dialog
            jQuery("#upload_dialog").dialog({ autoOpen: false, width: 800, dialogClass: 'ocUpload'});
            jQuery("#oc_upload_dialog").click(
                function () {
                    jQuery("#upload_dialog").dialog('open');
                    return false;
                }
            );
            // Config Dialog
            jQuery("#config_dialog").dialog({ autoOpen: false, width: 800, dialogClass: 'ocConfig'});
            jQuery("#oc_config_dialog").click(
                function () {
                    jQuery("#config_dialog").dialog('open');
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
                            'visibility' : jQuery( this ).data('visibility')
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
    }
};

