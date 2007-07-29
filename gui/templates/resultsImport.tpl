{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: tcImport.tpl,v 1.12 2007/01/02 13:42:06 franciscom Exp $
Purpose: smarty template - manage import of test cases and test suites
*}
{include file="inc_head.tpl"}

<body>
{config_load file="input_dimensions.conf" section="tcImport"} {* Constant definitions *}

<h1>{$container_description}{$smarty.const.TITLE_SEP}{$container_name|escape}</h1>

<div class="workBack">
<h1>{$import_title}</h1>

{if $resultMap eq null}
<form method="post" enctype="multipart/form-data" action="{$SCRIPT_NAME}">
	<h2>{lang_get s='title_choose_file_type'}</h2>
	<p>{lang_get s='req_import_type'}
	<select name="importType">
		{html_options options=$importTypes}
	</select>
	</p>
	
	<p>
		{lang_get s='results_import_format'}<BR>
		&#60;results&#62; <BR>
			&#60;testcase id="100"&#62; <BR>
				&#60;result&#62;p&#60;/result&#62; <BR>
				&#60;notes&#62;functionality works great &#60;/notes&#62; <BR>
			&#60;/testcase&#62; <BR>
						&#60;testcase id="200"&#62; <BR>
				&#60;result&#62;f&#60;/result&#62; <BR>
				&#60;notes&#62;this case failed due to error &#60;/notes&#62; <BR>
			&#60;/testcase&#62; <BR>
						&#60;testcase id="150"&#62; <BR>
				&#60;result&#62;b&#60;/result&#62; <BR>
				&#60;notes&#62;this test case is blocked&#60;/notes&#62; <BR>
			&#60;/testcase&#62; <BR>
		&#60;/results&#62; <BR>
	</p>
	<!--
	<p>	{lang_get s='required_cvs_format'}<br />
		{foreach key=k item=i from=$tcFormatStrings}
			{$k} : {$i}<br />
		{/foreach}
	</p>
	-->

	<h2>{lang_get s='title_choose_local_file'}</h2>
	<p>{lang_get s='local_file'} <input type="file" name="uploadedFile" 
	                                    size="{#FILENAME_SIZE#}" maxlength="{#FILENAME_MAXLEN#}"/></p>
	<p>{lang_get s='max_size_cvs_file1'} {$importLimitKB} {lang_get s='max_size_cvs_file2'}</p>
	
	<div class="groupBtn">
		<input type="hidden" name="bRecursive" value="{$bRecursive}" />
		<input type="hidden" name="build" value="{$buildID}" />
		<input type="hidden" name="bIntoProject" value="{$bIntoProject}" />
		<input type="hidden" name="containerID" value="{$containerID}" />
		<input type="hidden" name="MAX_FILE_SIZE" value="{$importLimit}" /> {* restrict file size *}
		<input type="submit" name="UploadFile" value="{lang_get s='btn_upload_file'}" />
		<input type="button" name="cancel" value="{lang_get s='btn_cancel'}" 
			onclick="javascript: location.href=fRoot+'lib/results/resultsImport.php';" />
	</div>
</form>
{else}
	{foreach item=result from=$resultMap}
		{lang_get s='title_imp_tc_data'} : <b>{$result[0]|escape}</b> : {$result[1]|escape}<br />
	{/foreach}
	{include file="inc_refreshTree.tpl"}
{/if}

{if $bImport > 0}
	{include file="inc_refreshTree.tpl"}
{/if}

{* 20061114 - franciscom *}
{if $file_check.status_ok eq 0}
    <script>
    alert("{$file_check.msg}");
    </script>
{/if}  


</div>

</body>
</html>