# Erledigte Klassen

[✓] OCAccessControl.php  
[✓] OCConfig.php  
[✓] OCConfigPrecise.php  
[ ] OCCourseModel.class.php  
[✓] OCEndpointModel.php  
[✓] OCEndpoints.php  
[ ] OCModel.php  
[ ] OCResources.php  
[ ] OCScheduledRecordings.php  
[ ] OCSeminarEpisodes.php  
[ ] OCSeminarSeries.php  
[ ] OCSeminarWorkflowConfiguration.php  
[ ] OCSeminarWorkflows.php  
[ ] OCSeriesCache.php  
[ ] OCSeriesModel.php  

[ ] Alle Instaziierungen via ::create anstatt von new ... durchführen

[ ] Namespacing für die REST Klassen

[ ] OCConfig: getConfigIdForCourse etc. - inkorrekt: es gibt pro Kurs mehrere Config-ids
[ ] Alle Aufrufe müssen von der course_id befreit werden und nur noch die series_id verwenden. Das Mapping zwischen Kurs und Serie muss dann nur noch einmalig ausgelesen (und überprüft) werden. 
