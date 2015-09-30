<script type="text/html" id='episodeList'>
    <% var counter = 0;%>
    <% for(var episode in episodes) { %>

    <li id="<%= episodes[episode]['id']%>"
        class="<%= episodes[episode]['visibility']!== 'false' ? 'oce_item' : ' hidden_ocvideodiv oce_item' %>
        <%= episodes[episode]['id']!== active ? '' : ' oce_active_li' %>"
        data-courseId="<?=$course_id?>"
        data-visibility="<%= episodes[episode]['visibility'] %>"
        data-mkdate="<%= episodes[episode]['mkdate'] %>"
        data-pos="<%= counter %>">
        <a
            href="">
            <div>
                <img
                    class="oce_preview <%= episodes[episode]['visibility']!== 'false' ? '' : 'hidden_ocvideo' %>"
                    src="<%= episodes[episode]['preview'] %> ">
            </div>
            <div class="oce_metadatacontainer">
                <h3 class="oce_metadata oce_list_title"><%= episodes[episode]['title'] %> <%= episodes[episode]['visibility']!== 'false' ? '' : '(Unsichtbar)' %></h3>
                <span class="oce_list_date">
                    <% var date = new Date(episodes[episode]['start'])%>
                    Vom <%= ('0' + date.getDate()).slice(-2)%>.<%= ('0' + (date.getMonth()+1)).slice(-2)%>.<%= date.getFullYear()%>  <%= ('0' + date.getHours()).slice(-2)%>:<%= date.getMinutes()%>
                    <?//=sprintf(_("Vom %s"),date("d.m.Y H:m",strtotime($item['start'])))?></span>
            </div>
        </a>
    </li>
        <% counter++;%>

    <% } %>
</script>
