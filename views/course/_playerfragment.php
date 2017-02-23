<script type="text/html" id='playerTemplate'>
    <span id="oc_active_episode" class="hidden" data-activeepisode="<%= episode.id %>"></span>
    <% if (theodul) { %>
        <iframe src="<%= embed %>"
                style="border:0px #FFFFFF none;"
                name="Opencast Matterhorn video player"
                scrolling="no"
                frameborder="0"
                marginheight="0px"
                marginwidth="0px"
                width="640"
                height="360"
                allowfullscreen="true"
                webkitallowfullscreen="true"
                mozallowfullscreen="true">
        </iframe>
    <% } else { %>
        <iframe src="<%= embed %>&hideControls=false"
                style="border: 0px #FFFFFF none;"
                name="Opencast Matterhorn - Media Player" scrolling="no"
                frameborder="0" marginheight="0px" marginwidth="0px"
                width="100%" height="250px">
        </iframe>
    <% } %>

    <br>
    <div class="oce_emetadata">
        <h2 class="oce_title"><%= episode.title %></h2>
        <ul class="oce_contetlist">
            <li><?=_('Aufzeichnungsdatum : ')?> <%= episode.start %> <?=_("Uhr")?></li>
            <li><?=_('Autor : ')?> <%= episode.author %></li>
            <li><?=_('Beschreibung : ')?> <%= episode.description %></li>
        </ul>
        <div class="ocplayerlink">
            <div style="text-align: left; font-style: italic;">Weitere
                Optionen:</div>
            <div class="button-group">
                <? if($extendedPlayerButton) { ?>
                    <a class="ocextern button" href="http://<%=engage_player_url%>" target="_blank" tabindex="0"><?= _('Erweiterter Player') ?></a>
                <? } ?>
                <% if(episode.presenter_download) { %>
                    <a class="download presenter button" href="<%=episode.presenter_download%>" target="_blank" tabindex="0"><?= _('ReferentIn') ?></a>
                <% } %>
                <% if(episode.presentation_download) { %>
                    <a class="download presentation button" href="<%=episode.presentation_download%>" target="_blank" tabindex="0"> <?= _('Bildschirm') ?></a>
                <% } %>
                <% if(episode.audio_download) { %>
                    <a class="download audio button" href="<%=episode.audio_download%>" target="_blank" tabindex="0"> <?= _('Audio') ?></a>
                <% } %>
            </div>
            <form action="<?= PluginEngine::getLink('opencast/course/remove_episode') ?>" id="delete_form" name="delete_form">
                <input type="hidden" name="episode_id" value="<%=episode.id%>">
            </form>
            <% if (dozent) { %>
                <div class="button-group" style="float:right">
                    <% if (episode.visibility == 'true')  {%>
                        <a class="ocvisible ocspecial button" data-episode-id="<%= episode.id%>" data-position="<%=episode.position%>" href="replaceme" id="oc-togglevis" tabindex="0">Aufzeichnung sichtbar</a>
                    <% } else { %>
                        <a class="ocinvisible ocspecial button" data-episode-id="<%=episode.id%>" data-position="<%=episode.position%>" href="replaceme" id="oc-togglevis" tabindex="0">Aufzeichnung unsichtbar</a>
                    <% } %>
                    <button class="button" form="delete_form">Aufzeichnung löschen</button>
                </div>
            <% } %>
        </div>
    </div>
</script>