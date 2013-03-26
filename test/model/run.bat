set generatorBase=C:/Users/ingo/Daten/web/htdocs/olympos/generator/dist
set targetDir=C:/Users/ingo/Daten/web/htdocs/wcmf

java -Djava.library.path=%generatorBase%/lib ^
  -jar %generatorBase%/ChronosGenerator.jar %generatorBase%/cartridge/Wcmf/workflow/wcmf.oaw ^
  -basePath=%generatorBase%/ ^
  -propertyFile=workflow.properties ^
  -targetDir=%targetDir%
