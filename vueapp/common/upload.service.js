/**
 * This object manages the episode upload via LTI
 *
 * @type {Object}
 */

import axios from "@/common/axios.service";
class UploadService {

    constructor(service_url) {
        this.service_url = service_url;
    }

    /**
     * Provides upload ACL
     *
     * @param mediaPackage
     * @param uploader LTI information of uploader used to permit read and write access
     * @returns {string} upload ACL
     */
    uploadACL(mediaPackage, uploader) {
        const xmlDoc = $.parseXML(mediaPackage);
        let episode_id = xmlDoc.documentElement.id;

        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Policy PolicyId="mediapackage-1"
                RuleCombiningAlgId="urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:permit-overrides"
                Version="2.0"
                xmlns="urn:oasis:names:tc:xacml:2.0:policy:schema:os">
            <Rule RuleId="ROLE_ADMIN_read_Permit" Effect="Permit">
                <Target>
                    <Actions>
                        <Action>
                            <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                                <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                                <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                            </ActionMatch>
                        </Action>
                    </Actions>
                </Target>
                <Condition>
                    <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">STUDIP_${episode_id}_read</AttributeValue>
                        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                    </Apply>
                </Condition>
            </Rule> 
            <Rule RuleId="ROLE_ADMIN_read_write_Permit" Effect="Permit">
                <Target>
                    <Actions>
                        <Action>
                            <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                                <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                                <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                            </ActionMatch>
                        </Action>
                    </Actions>
                </Target>
                <Condition>
                    <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">STUDIP_${episode_id}_write</AttributeValue>
                        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                    </Apply>
                </Condition>
            </Rule>
            <Rule RuleId="ROLE_ADMIN_write_Permit" Effect="Permit">
                <Target>
                    <Actions>
                        <Action>
                            <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                                <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
                                <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                            </ActionMatch>
                        </Action>
                    </Actions>
                </Target>
                <Condition>
                    <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">STUDIP_${episode_id}_write</AttributeValue>
                        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                    </Apply>
                </Condition>
            </Rule>
            <Rule RuleId="user_read_Permit" Effect="Permit">
                <Target>
                    <Actions>
                        <Action>
                            <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                                <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
                                <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                            </ActionMatch>
                        </Action>
                    </Actions>
                </Target>
                <Condition>
                  <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                    <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">${uploader.userRole}</AttributeValue>
                    <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                  </Apply>
                </Condition>
            </Rule>
            <Rule RuleId="user_write_Permit" Effect="Permit">
                <Target>
                    <Actions>
                        <Action>
                            <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
                                <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
                                <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                            </ActionMatch>
                        </Action>
                    </Actions>
                </Target>
                <Condition>
                    <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
                        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">${uploader.userRole}</AttributeValue>
                        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
                    </Apply>
                </Condition>
            </Rule>
        </Policy>`;
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

    async getMediaPackage() {
        return axios({
            method: 'GET',
            url: this.service_url + "/createMediaPackage",
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        });
    }

    createDCCCatalog(terms) {
        let obj = this;

        let escapeString = function (string) {
            return new XMLSerializer().serializeToString(new Text(obj.removeXMLInvalidChars(string, true)));
        };

        return '<?xml version="1.0" encoding="UTF-8"?>' +
            '<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/"' +
            ' xmlns:dcterms="http://purl.org/dc/terms/"' +
            ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' +
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
        let episodeDC = this.createDCCCatalog(terms);

        return axios({
            url: this.service_url + "/addDCCatalog",
            method: "POST",
            data: new URLSearchParams({
                mediaPackage: mediaPackage,
                dublinCore: episodeDC,
                flavor: 'dublincore/episode'
            }),
            crossDomain: true,
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        })
    }

    async addACL(mediaPackage,acl) {
        var acldata = new FormData();
        acldata.append('mediaPackage', mediaPackage);
        acldata.append('flavor', 'security/xacml+episode');
        acldata.append('BODY', new Blob([acl]), 'acl.xml');

        return axios({
            url: this.service_url + "/addAttachment",
            method: "POST",
            data: acldata,
            processData: false,
            contentType: false,
            crossDomain: true,
            withCredentials: true,
        })
    }

    async uploadTracks(mediaPackage, files, onProgress) {
        let obj = this;
        return files.reduce(function(promise, file) {
            return promise.then(function (mediaPackage) {

                var data = new FormData();
                data.append('mediaPackage', mediaPackage);
                data.append('flavor', file.flavor);
                data.append('tags', '');
                data.append('BODY', file.file, file.file.name);

                return obj.addTrack(data, "/addTrack", file, onProgress);
            });
        }, Promise.resolve(mediaPackage))
    }

    async uploadCaptions(files, episode_id, options) {
        this.fixFilenames(files);
        let onProgress = options.uploadProgress;
        let uploadDone = options.uploadDone;
        let onError = options.onError;

        let obj = this;
        return files.reduce(function(promise, file) {
            return promise.then(function () {

                var data = new FormData();
                data.append('flavor', file.flavor);
                data.append('overwriteExisting', file.overwriteExisting);
                data.append('track', file.file);

                return obj.addTrack(data, "/" + episode_id + "/track", file, onProgress, onError);
            });
        }, Promise.resolve())
        .then(() => {
            uploadDone();
        }).catch(function (error) {
            if (error.code === 'ERR_NETWORK') {
                onError();
            }
        });
    }

    addTrack(data, url_path, track, onProgress, onError) {
        var fnOnProgress = function (event) {
            onProgress(track, event.loaded, event.total);
        };

        let obj = this;

        return new Promise(
            function (resolve, reject) {
                obj.request = axios.CancelToken.source();

                return axios({
                    url: obj.service_url + url_path,
                    method: "POST",
                    data: data,
                    processData: false,
                    contentType: false,
                    withCredentials: true,
                    onUploadProgress: function( progressEvent ) {
                        fnOnProgress(progressEvent);
                    },
                    cancelToken: obj.request.token
                }).then(({ data }) => {
                    resolve(data);
                }).catch(function (error) {
                    reject(error);
                });
            }
        );
    }

    finishIngest(mediaPackage, workflowId = "upload") {
        return axios({
            url: this.service_url + "/ingest",
            method: "POST",
            data: new URLSearchParams({
                mediaPackage: mediaPackage,
                workflowDefinitionId: workflowId
            }),
            withCredentials: true,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            }
        })
    }

    fixFilenames(files) {
        const maxLength = 128;

        for (let id in files) {
            if (files[id].length > maxLength) {
                let file = files[id].file;
                const extension = file.name.split('.').pop();

                // The file name is a readonly property, hence a new
                // file object must be created, instead of simply
                // assigning the new file name.
                const newName = file.name.substring(0, maxLength - extension.length - 1) + '.' + extension;
                files[id].file =  new File(
                  [files[id].file],
                  newName,
                  {
                    type: file.type,
                    lastModified: file.lastModified
                  }
                );
            }
        }

        return files;
    }

    /**
     * Upload video to Opencast
     *
     * @param files files to be uploaded
     * @param terms DCC terms
     * @param workflowId workflow for upload
     * @param uploader LTI information of uploader
     * @param options handler
     * @returns {Promise<T | void>}
     */
    upload(files, terms, workflowId, uploader, options) {
        this.fixFilenames(files);
        let obj = this;
        let onProgress = options.uploadProgress;
        let uploadDone = options.uploadDone;
        let onError = options.onError;

        return this.getMediaPackage()
            .then(function ({ data }) {
                return obj.addDCCCatalog(data, terms)
            })
            .then(function ({ data }) {
                let acl = obj.uploadACL(data, uploader);
                return obj.addACL(data, acl)
            })
            .then(function ({ data }) {
                return obj.uploadTracks(data, files, onProgress)
            })
            .then(function (mediaPackage) {
                let ingest = obj.finishIngest(mediaPackage, workflowId);

                try {
                    let episode_id;
                    // Nothing waiting for this XHR to finish, making the
                    // log-entry a nice-to-have
                    const xmlDoc = $.parseXML(mediaPackage);
                    episode_id = xmlDoc.documentElement.id;
                    if (episode_id) {
                        uploadDone(episode_id, terms, workflowId);
                    }
                } catch (ex) {
                    console.log(ex);
                    /* Catch XML parse error. On Error Resume Next ;-) */
                }
                return ingest;
            }).catch(function (error) {
                if (error.code === 'ERR_NETWORK') {
                    onError();
                }
            });
    }

    cancel() {
        this.request.cancel();
    }
}

export default UploadService;
