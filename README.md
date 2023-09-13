# exml
Projeto de exportador de livros em formato xml para crossref<br>
omp-3.3.0-8/plugins/importexport/exml<br>

<br>em desenvolvimento...<br>
Após instalado, acesso em http:seusite.org/index.php/suaeditora/management/importexport/plugin/exml<br>
![image](https://github.com/danielsf93/exml/assets/114300053/53f129b9-c003-43c9-a213-ba2b9427c79a)
<br>
![image](https://github.com/danielsf93/exml/assets/114300053/5d87f342-d62a-4bef-8590-25423f498ce4)
<br>
![image](https://github.com/danielsf93/exml/assets/114300053/ea717890-5073-4a7d-affd-a75dda741dbb)



<br><br>Baseado em Native, Datacite e Crossref plugin.
<br><br> #falta:

<br>Problema em salvar form que funcione em importexport plugin. Apesar de forms funcionarem em plugins genéricos e de bloco da plataforma, exclusivamente em importexport não tem funcionado. Essa etapa é necessária para salvar as informações de nome e email de depositante para formar as tags do arquivo xml:<br><br>
<depositor><br>
  <depositor_name>sibi:sibi</depositor_name><br>
  <email_address>dgcd@abcd.usp.br</email_address><br>
</depositor><br><br>
Por hora, no plugin esta informação está presente via hardcoding. As alternativas são, em primeiro lugar encontrar solução para o form, ou deixar como está, já que a ferramenta é exclusiva do portal USP, ou, também deixando como está, editar manualmente esta informação no produto xml final quando for necessário.<br><br>





