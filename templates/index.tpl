{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{$pageTitle|escape}
	</h1>

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
		$('#importExportTabs').tabs('option', 'cache', true);
	{rdelim});
</script>
<div id="importExportTabs" class="pkp_controllers_tab">

	<ul>
	<li><a href="#config-tab">{translate key="configuraçao"}</a></li>

	<li><a href="#export-tab">{translate key="plugins.importexport.exml.exportSubmissions"}</a></li>
	</ul>





	<div id="config-tab">
	<script>
    $(function () {ldelim}
        $('#exmlSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>

<form class="pkp_form" id="exmlSettingsForm" method="POST" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="importexport" plugin="exml" verb="settings" save=true}">
    <!-- Always add the csrf token to secure your form -->
    {csrf}

    {fbvFormArea}
        <div class="pkp_notification">
            <div class="notifyWarning">
               <b> {translate key="Plugin de exportação eXML."}</b><br>
			   -Adicione as informações como login e email de depositant crossref.<br>
			   -Selecione a publicação a ser baixada e clique em exportar.<br>
			   -Acesse a <a href="https://doi.crossref.org/servlet/useragent" target="_blank">ferramenta de deposito manual da Crossref </a>para depositar o arquivo xml.
			   <br><br>Se possuir dúvidas sobre a ferramenta, acesse o 
			   <a href="https://www.crossref.org/documentation/register-maintain-records/direct-deposit-xml/admin-tool/" target="_blank"> manual da Crossref </a>
            </div>
        </div>
		{fbvFormSection title="depositor_name:"}
			{fbvElement type="text" id="campo01" value=$campo01}
		{/fbvFormSection}
		{fbvFormSection title="email_address:"}
			{fbvElement type="text" id="campo02" value=$campo02}
            
		
		{/fbvFormSection}
    {/fbvFormArea}
    {fbvFormButtons submitText="common.save"}
</form>


	
	</div>






	<div id="export-tab">
		
		<form id="exportXmlForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
			{csrf}
			{fbvFormArea id="exportForm"}
				<submissions-list-panel
					v-bind="components.submissions"
					@set="set"
				>

					<template v-slot:item="{ldelim}item{rdelim}">
						<div class="listPanel__itemSummary">
							<label>
								<input
									type="radio"
									name="selectedSubmissions[]"
									:value="item.id"
									v-model="selectedSubmissions"
								/>
								<span class="listPanel__itemSubTitle">
									{{ localize(item.publications.find(p => p.id == item.currentPublicationId).fullTitle) }}
								</span>
							</label>
							<pkp-button element="a" :href="item.urlWorkflow" style="margin-left: auto;">
								{{ __('common.view') }}
							</pkp-button>
						</div>
					</template>
				</submissions-list-panel>
				{fbvFormSection}
					
					<pkp-button @click="submit('#exportXmlForm')">
						{translate key="plugins.importexport.exml.exportSubmissions"}
					</pkp-button>
				{/fbvFormSection}
			{/fbvFormArea}
		</form>
	</div>
</div>

{/block}
