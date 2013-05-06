
(function() {
  var $, dialog, dialog_template, openLinkDialog;

  $ = jQuery;

  dialog_template = _.template("<div title='OpenCast Video einf&uuml;gen'>\n <button class=submit>Einf&uuml;gen</button>\n</div>");

  

  dialog = false;

  openLinkDialog = function(selection, textarea, button) {
    if (dialog) {
      dialog.dialog("open");
    } else {
      //$(button).showAjaxNotification();
      dialog = $(dialog_template()).dialog();
      return dialog.on("click", "button.submit", function() {
        var selected_page;
        episode_id = "videoid"
        dialog.dialog("close");
        return textarea.replaceSelection("{{oc:" + episode_id + "}}");
      });
      
      
   
    }
    return false;
  };

  embed = {
    label: "Opencast Video",
    evaluate: openLinkDialog
  };

  STUDIP.Toolbar.buttonSet.right = _.extend({
    embed: embed
  }, STUDIP.Toolbar.buttonSet.right);

}).call(this);
