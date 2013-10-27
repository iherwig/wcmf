generatorBase=E:/Daten/web/olympos/generator/dist
targetDir=E:/Daten/web/wcmf/src

java -Djava.library.path=$generatorBase/lib \
  -jar $generatorBase/ChronosGenerator.jar $generatorBase/cartridge/Wcmf/workflow/wcmf.oaw \
  -basePath=$generatorBase/ \
  -propertyFile=workflow.properties \
  -targetDir=$targetDir
