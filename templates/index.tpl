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
	<li><a href="#exmlSettingsForm">{translate key="Credenciais"}</a></li>

	<li><a href="#export-tab">{translate key="plugins.importexport.exml.exportSubmissions"}</a></li>
	</ul>





	<div id="exmlSettingsForm">
			<script type="text/javascript">
				$(function () {ldelim}
					$('#exmlSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
			</script>

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
			<form class="pkp_form" id="exmlSettingsForm" method="post"
				  action="{plugin_url path="settings" verb="save" save=true}">
				{csrf}
				
				{fbvFormArea id="exmlSettingsFormArea"}
					
				
				{fbvFormSection}
				{fbvElement type="text" id="email" value=$email label="Email" maxlength="100" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" id="username" value=$username label="Nome de Usuário" maxlength="50" size=$fbvStyles.size.MEDIUM}
				
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
