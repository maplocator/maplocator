#contains scripts required to generate map files required by mapserver to generate layers for client side rendering

Usage: php generateMap.php -u DBUser -d DBName -p password -l layer_tablename

Where DBName is the database for MapLocator created during installation of MapLocator using installation and configuration guide(maplocator_installation_configuration_guide.pdf) and layer_tablename is the tablname of the layer in the database.