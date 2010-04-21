#This folder contains the deployment scripts for MapLocatot using the source control repository

1: Before executing the script we need the following installed:
	1. Python 2.5
	2. pySVN library

2: Once the above is installed do the following:
	1. Edit config.yaml and set the temp, backup and deployment directory
	2. Run deploy_maplocator.py
	3. For re-deployment:
  		3.1. Modify .htaccess as per need
  		3.2. Copy your settings.php to [deployment_dir]/sites/default
  		3.3. Copy back the files from [deployment_dir]/sites/default/files
