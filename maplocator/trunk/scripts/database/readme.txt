


#This folder contains the sql scripts required to set up the database for MapLocator. There are two sql script. 


maplocator.sql
	- Contains minimal sql commands requried to set up the database with a sandbox layer


maplocator_with_layers.sql
	- Contains additional layers along with maplocator.sql

#Following steps are required to create maplocator compatible database using the above sql scripts.

NOTE: The following steps are mentioned in the installation guide(maplocator_installation_configuration_guide.pdf) as well. Please ignore if you have already followed these steps
•	Create database (say for ex. maplocator) using the following commands 
$ psql -U postgres 
# CREATE DATABASE maplocator WITH template= template_postgis ENCODING = ‘UTF8’;
# \q
Database maplocator is created with two tables geometry_columns and spatial_ref_sys
•	Run the  “maplocator.sql” script located in the maplocator source under “source\trunk\scripts\database” for the maplocator database using the following command
$ psql -U postgres maplocator < maplocator.sql

