const OC = {
    lti_done: 0,
    ltiCall: async function(lti_url, lti_data, success_callback, error_callback) {

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
                success: function() {
                    OC.lti_done = 2;
                    success_callback();
                },
                error: function() {
                    if (error_callback !== undefined) {
                        error_callback();
                    } else {
                        STUDIP.Dialog.show('Es konnte keine Verbindung zum LTI ' +
                                            'in Opencast hergestellt werden. ' +
                                            'Laden Sie die Seite neu. Falls das ' +
                                            'nicht hilft, wenden sich an ' +
                                            'eine/n Systemadministrator/in', {
                            title: 'LTI Fehler',
                            size: 'small'
                        });
                    }
                }
            });
        }
    }
}