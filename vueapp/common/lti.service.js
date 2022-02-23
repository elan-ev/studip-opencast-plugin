import Vue from "vue";
import axios from "axios";
import VueAxios from "vue-axios";
import ApiService from "@/common/api.service";

function LtiException() {};

/**
 * This object manages the connection and calls to the lti-service
 *
 * LTI generation is done on server side to prevent leaking of credentials
 *
 * @type {Object}
 */
class LtiService {

    setLaunchData(lti) {
        this.lti = lti;
    }

    async getLaunchUrl() {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return this.lti.launch_url;
    }

    constructor(config_id, endpoints) {
        this.config_id     = config_id;
        this.endpoints     = endpoints;
        this.authenticated = false;
        this.lti           = null;
    }

    belongsTo(config_id, endpoint) {
        return (
            this.config_id == config_id
            && this.endpoints.includes(endpoint)
        )
    }

    async checkConnection() {
        let obj = this;

        return Vue.axios({
            method: 'GET',
            url: this.lti.launch_url,
            crossDomain: true,
            withCredentials: true
        }).then(({ data }) => {
            if (!data.roles) {
                obj.authenticated = false;
            }
        }).catch(function (error) {
            obj.authenticated = false;
        });
    }

    isAuthenticated() {
        return (
            this.lti !== null && this.authenticated
        );
    }

    async authenticate()
    {
        try {
            if (this.lti === null) {
                throw new LtiException('no lti launch data set!');
            }
        } catch (e) {
            return e;
        }

        let obj = this;

        return await Vue.axios({
            method: 'POST',
            url: this.lti.launch_url,
            data: new URLSearchParams(this.lti.launch_data),
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        }).then(() => {
            obj.authenticated = true;
            return true;
        }).catch(function (error) {
            return error;
        });
    }

    async get(resource) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return Vue.axios({
            url: resource,
            baseURL: this.lti.launch_url
        });
    }

    async post(resource, params) {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return Vue.axios({
            method: 'POST',
            url: resource,
            baseURL: this.lti.launch_url,
            data: params
        });
    }

    /**
     * Removes invalid XML characters from a string
     * source: https://gist.github.com/john-doherty/b9195065884cdbfd2017a4756e6409cc
     * author: John Doherty, license: MIT
     * @param {string} str - a string containing potentially invalid XML characters (non-UTF8 characters, STX, EOX etc)
     * @param {boolean} removeDiscouragedChars - should it remove discouraged but valid XML characters
     * @return {string} a sanitized string stripped of invalid XML characters
     */
    removeXMLInvalidChars(str, removeDiscouragedChars) {

        // remove everything forbidden by XML 1.0 specifications, plus the unicode replacement character U+FFFD
        var regex = /((?:[\0-\x08\x0B\f\x0E-\x1F\uFFFD\uFFFE\uFFFF]|[\uD800-\uDBFF](?![\uDC00-\uDFFF])|(?:[^\uD800-\uDBFF]|^)[\uDC00-\uDFFF]))/g;

        // ensure we have a string
        str = String(str || '').replace(regex, '');

        if (removeDiscouragedChars) {

            // remove everything discouraged by XML 1.0 specifications
            regex = new RegExp(
                '([\\x7F-\\x84]|[\\x86-\\x9F]|[\\uFDD0-\\uFDEF]|(?:\\uD83F[\\uDFFE\\uDFFF])|(?:\\uD87F[\\uDF' +
                'FE\\uDFFF])|(?:\\uD8BF[\\uDFFE\\uDFFF])|(?:\\uD8FF[\\uDFFE\\uDFFF])|(?:\\uD93F[\\uDFFE\\uD' +
                'FFF])|(?:\\uD97F[\\uDFFE\\uDFFF])|(?:\\uD9BF[\\uDFFE\\uDFFF])|(?:\\uD9FF[\\uDFFE\\uDFFF])' +
                '|(?:\\uDA3F[\\uDFFE\\uDFFF])|(?:\\uDA7F[\\uDFFE\\uDFFF])|(?:\\uDABF[\\uDFFE\\uDFFF])|(?:\\' +
                'uDAFF[\\uDFFE\\uDFFF])|(?:\\uDB3F[\\uDFFE\\uDFFF])|(?:\\uDB7F[\\uDFFE\\uDFFF])|(?:\\uDBBF' +
                '[\\uDFFE\\uDFFF])|(?:\\uDBFF[\\uDFFE\\uDFFF])(?:[\\0-\\t\\x0B\\f\\x0E-\\u2027\\u202A-\\uD7FF\\' +
                'uE000-\\uFFFF]|[\\uD800-\\uDBFF][\\uDC00-\\uDFFF]|[\\uD800-\\uDBFF](?![\\uDC00-\\uDFFF])|' +
                '(?:[^\\uD800-\\uDBFF]|^)[\\uDC00-\\uDFFF]))', 'g');

            str = str.replace(regex, '');
        }

        return str;
    }

    async getNewMediaPackage() {
        if (!this.isAuthenticated()) {
            await this.authenticate();
        }

        return Vue.axios({
            method: 'GET',
            url: this.lti.service_url + "/ingest/createMediaPackage",
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        });
    }

    async createDCCCatalog(terms) {
        var escapeString = function (string) {
            return new XMLSerializer().serializeToString(new Text(this.removeXMLInvalidChars(string, true)));
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

    async addDCCCatalog(mediaPackage, terms) {
        // Prepare meta data
        var episodeDC = createDCCCatalog(terms);

        return Vue.axios({
            url: this.lti.service_url + "/ingest/addDCCatalog",
            method: "POST",
            data: {
                mediaPackage: mediaPackage,
                dublinCore: episodeDC,
                flavor: 'dublincore/episode'
            },
            crossDomain: true,
            withCredentials: true,
        })
    }

    async addACL(mediaPackage,acl) {
        var acldata = new FormData();
        acldata.append('mediaPackage', mediaPackage);
        acldata.append('flavor', 'security/xacml+episode');
        acldata.append('BODY', new Blob([acl]), 'acl.xml');

        return $.ajax({
            url: this.lti.service_url + "/ingest/addAttachment",
            method: "POST",
            data: acldata,
            processData: false,
            contentType: false,
            xhrFields: { withCredentials: true },
        })
    }

    async uploadTracks(mediaPackage, files, onProgress) {
        return files.reduce(function(promise, file) {
            return promise.then(function (mediaPackage) {
                return addTrack(mediaPackage, file, onProgress);
            });
        }, Promise.resolve(mediaPackage))
    }

     addTrack(mediaPackage, track, onProgress) {
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
                this.uploadPercentage = 0;
                var xhr = Vue.axios({
                    url: this.lti.service_url + "/ingest/addTrack",
                    method: "POST",
                    data: data,
                    processData: false,
                    contentType: false,
                    withCredentials: true,
                    onUploadProgress: function( progressEvent ) {
                        this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ) );
                    }
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

    finishIngest(mediaPackage, workflowId = "upload") {
        console.log(mediaPackage);
        return Vue.axios({
            url: this.lti.service_url + "/ingest/ingest",
            method: "POST",
            data: {
                mediaPackage: mediaPackage,
                workflowDefinitionId: workflowId
            },
            withCredentials: true,
        })
    }

    logUpload(episode_id, workflowId = "upload") {
    }

    upload(files, terms, workflowId, onProgress) {
        return this.getMediaPackage()
            .then(function (_mediaPackage, _status, resp) {
                return this.addDCCCatalog(resp.responseText, terms)
            })
            .then(function (_mediaPackage, _status, resp) {
                let acl = terms.oc_acl;
                return this.addACL(resp.responseText, acl)
            })
            .then(function (_mediaPackage, _status, resp) {
                return this.uploadTracks(resp.responseText, files, onProgress)
            })
            .then(function (mediaPackage) {
                const jqXHR = this.finishIngest(mediaPackage, workflowId);
                try {
                    let episode_id;
                    // Nothing waiting for this XHR to finish, making the
                    // log-entry a nice-to-have
                    /*
                    const xmlDoc = $.parseXML(mediaPackage);
                    episode_id = xmlDoc.documentElement.id;
                    if (episode_id) {
                        this.logUpload(episode_id, workflowId);
                    }
                    */
                } catch (ex) {
                    console.log(ex);
                    /* Catch XML parse error. On Error Resume Next ;-) */
                }
                return jqXHR;
            })
    }

    getUploadPercentage() {
        return this.uploadPercentage;
    }
}

export { LtiService, LtiException };
