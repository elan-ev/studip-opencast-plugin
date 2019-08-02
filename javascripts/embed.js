(function() {
    var $, dialog, dialog_template, openLinkDialog, iconurl;
    $ = jQuery;



    var area;
    openLinkDialog = function(selection, textarea, button) {
        area = function() {
            return textarea
        };

        iconurl = STUDIP.ASSETS_URL + 'images/icons/blue/refresh.svg';

        dialog_template = _.template('<div class="matterhorn"><div id="dialog" title="Opencast Video"><div class="ui-widget"><label for="tags"></label><input id="tags" placeholder="Veranstaltung suchen ..." title="Suche hier eine Veranstaltung und dannach eine Aufzeichnung. Reset mit ESC"><img id="reset_series" src="' + iconurl +'"></div><div id="oc_embed_content"><div id="series_container"><h2>Veranstaltung</h2><br/></div><div id="vertical_border_wrapper"><div id="vertical_border"></div></div><div id="episodes_container"><h2>Aufzeichnung</h2><br/></div></div></div></div>');
        dialog = $(dialog_template()).dialog({
            buttons: {
                "Close": function() {
                    emptyContainer();
                    $(this).dialog("close");
                },
            },
            height: ($(window).height() * 0.8),
            width: '70%',
            close: function() {
                emptyContainer();
            },
        });
        loadSeries();
    };


    var loadSeries = function() {
        emptyEpisodes();
        $.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/getseries").done(function(data) {
            var series = data;

            var series_array = [];
            var series_id_array = [];

            $('input#tags').focus();
            // place a instructionale note
            if($.isEmptyObject(series)){
                $('#oc_embed_content').empty().append('<div class="messagebox messagebox_info "><div class="messagebox_buttons"><a class="close" href="#" title="Nachrichtenbox schliessen"><span>Nachrichtenbox schliessen</span></a></div>Es gibt momentan leider keine Inhalte in Opencast Matterhorn, die Sie einbetten d&uuml;rfen.</div>');
                $('#tags').attr('disabled', 'disabled');
            } else {
                $('div#episodes_container').append('<div class="messagebox messagebox_info "><div class="messagebox_buttons"><a class="close" href="#" title="Nachrichtenbox schliessen"><span>Nachrichtenbox schliessen</span></a></div>Bitte w&auml;hlen Sie zuerst auf der linken Seite eine Veranstaltung, aus der Sie Inhalte einbetten m&ouml;chten.</div>');
            }

            $("div#series_container").append("<ul id='loaded_series'>");
            // Die Serien an die Liste haengen und Arrays befuellen
            for (var i = 0; i < series.length; i++) {
                var contributor = series[i].contributor;
                var publisher = series[i].publisher;
                if (publisher == null) {
                    publisher = "";
                } else {
                    publisher += "<br/>";
                };
                if (contributor == null) {
                    contributor = "";
                } else {
                    contributor += "<br/>";
                };

                $("ul#loaded_series").append("<li title='" + series[i].title + "' id=" + series[i].identifier + "><b>" + series[i].title + "</b><br/>" + contributor + publisher + "</li>");
                series_array[i] = series[i].title;
                series_id_array[i] = series[i].identifier;
            };

            var border_height = $('div#series_container li').outerHeight(true) * series.length;
            $('div#vertical_border_wrapper').css("height", border_height);
            $("div#series_container").append("</ul>");

            $('#reset_series').click(function() {
                emptyEpisodes();
                setSearch(series_array, series_id_array);
                setInfoText("Veranstaltung w&auml;hlen ...");
                setTag("Veranstaltung suchen ...");
                $('input#tags').focus();
            });

            $(document).keyup(function(e) {
                if (e.keyCode == 27) {
                    emptyEpisodes();
                    setSearch(series_array, series_id_array);
                    setInfoText("Veranstaltung w&auml;hlen ...");
                    setTag("Aufzeichnung suchen ...");
                    $('input#tags').focus();
                }

            });

            setSearch(series_array, series_id_array);

            $('div#series_container li').click(function() {
                emptyEpisodes();
                loadEpisodes($(this).attr('id'), $(this).attr("title"));
            });

        });
    };



    var loadEpisodes = function(ser_id, ser_title) {
        $.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/opencast/ajax/getepisodes/" + ser_id).done(function(data) {
            var episode = data;

            $('div#episodes_container').append("<p class='embed_episode_title'>Veranstaltung: " + ser_title + "</p>");
            if ($.isEmptyObject(episode) || (Array.isArray(episode) && !episode.length)) {
                setInfoText("Diese Veranstaltung enth&auml;lt keine Aufzeichnungen, bitte w&auml;hlen Sie eine andere.");
                $('div#dialog').animate({
                    scrollTop: 0
                }, 0);
                $('html body').animate({
                    scrollTop: 0
                }, 0);
                $('input#tags').focus();
                return;
            };

            setTag("Aufzeichnung suchen");
            var episodes_list = [];
            var episodes_id_list = [];
            var series_title = "";

            $('input#tags').focus();

            $('div#episodes_container').append("<ul id='loaded_episodes'>");

            for (var i = 0; i < episode.length; i++) {

                //foreach($episode->mediapackage->attachments->attachment as $attachment) {
                //    if($attachment->type === 'presenter/search+preview') $preview = $attachment->url;
                //}

                var img_url = "";

                if ('attachments' in episode[i].mediapackage) {
                    var attachments = episode[i].mediapackage.attachments;
                    $(attachments.attachment).each(function(index, val) {
                        if (val.type == 'presenter/search+preview' && val.mimetype == 'image/jpeg') {
                            img_url = val.url;
                        }

                    });
                }


                // Versuche Duration zu holen und umrechnen
                try {
                    var ep_dur = episode[i].mediapackage.duration;
                    ep_dur /= 1000;
                    var time = parseInt(ep_dur, 10);
                    time = time < 0 ? 0 : time;

                    var minutes = Math.floor(time / 60);
                    var seconds = time % 60;

                    minutes = minutes <= 9 ? "0" + minutes : minutes;
                    seconds = seconds <= 9 ? "0" + seconds : seconds;

                    var duration = "<i> L&auml;nge: </i>" + minutes + ":" + seconds + " Minuten";
                } catch (err) {
                    duration = "";
                }

                var ep_title = episode[i].dcTitle;
                var ep_cont = episode[i].dcContributor;
                if (ep_title == null) {
                    ep_title = "";
                } else {
                    ep_title += "<br/>";
                };
                if (ep_cont == null) {
                    ep_cont = "";
                } else {
                    ep_cont += "";
                };



                $('ul#loaded_episodes').append("<li id='" + episode[i].id + "'><img height='45px' width='80px' align=middle style='float:left; margin-right: 5px;' src=" + img_url + "><b>" + ep_title + "</b>" + ep_cont + "" + duration + "</li>");
                episodes_list[i] = episode[i].dcTitle;
                episodes_id_list[i] = episode[i].id;
            }

            $('input#tags').autocomplete({
                source: episodes_list,
                select: function(event, ui) {
                    matchEpisode(episodes_list, episodes_id_list, ui.item.value);
                    $(this).val('');
                    return false;
                },
            });

            $('div#episodes_container').append("</ul>");
            // An den Anfang der Dialogbox springen
            $('div#dialog').animate({
                scrollTop: 0
            }, 0);
            $('html body').animate({
                scrollTop: 0
            }, 0);

            $('div#episodes_container li').click(function() {
                appendPlayer($(this).attr('id'));
            });
        });
    };


    var appendPlayer = function(id) {
        area().replaceSelection('[opencast]' + id + '[/opencast]');
        dialog.dialog("close");
    };
    var matchEpisode = function(ep_array, ep_id_array, match_ep) {
        $.each(ep_array, function(index, value) {
            if (match_ep == value) {
                var id = ep_id_array[index];
                appendPlayer(id);
            };
            return;
        });
    };
    var matchSeries = function(series_array, series_id_array, match_series) {
        $.each(series_array, function(index, value) {
            if (match_series == value) {
                var id = series_id_array[index];
                loadEpisodes(id, match_series);
            };
            return;
        });
    };
    var setTag = function(text) {
        $('input#tags').attr('placeholder', text);
    };

    var emptyEpisodes = function() {
        $('div#episodes_container').empty();
        $('div#episodes_container').append('<h2 align=center> Aufzeichnungen </h2> <br/>');
    };

    var emptyContainer = function() {
        $('div#series_container').empty();;
        $("div#episodes_container").empty();
        $('div#series_container').append('<h2 align=center> Veranstaltungen </h2> <br/>');
        $('div#episodes_container').append('<h2 align=center> Aufzeichnungen </h2> <br/>');

    };

    var setSearch = function(series_array, series_id_array) {
        $('input#tags').autocomplete({
            source: series_array,
            autoFocus: true,
            select: function(event, ui) {
                matchSeries(series_array, series_id_array, ui.item.value);
                $(this).val('');
                return false;
            },
        });
    };


    var setInfoText = function(text) {
        $('#episodes_container').append('<i>' + text + '</i>');
    };




    embed = {
        label: "Opencast Video",
        evaluate: openLinkDialog
    };

    STUDIP.Toolbar.buttonSet.right = _.extend({
        embed: embed
    }, STUDIP.Toolbar.buttonSet.right);

}).call(this);
