set generatorBase=E:/Daten/web/olympos/generator/dist
set targetDir=E:/Daten/web/wcmf_new_roles

java -Djava.library.path=%generatorBase%/lib ^
  -jar %generatorBase%/ChronosGenerator.jar %generatorBase%/cartridge/Wcmf/workflow/wcmf.oaw ^
  -basePath=%generatorBase%/ ^
  -propertyFile=workflow.properties ^
  -targetDir=%targetDir%
