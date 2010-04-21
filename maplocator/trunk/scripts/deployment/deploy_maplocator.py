"""
All MapLocator code is Copyright 2010 by the original authors.

This work is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of the License, or any
later version.

This work is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See version 3 of
the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
Version 3 along with this program as the file LICENSE.txt; if not,
please see http://www.gnu.org/licenses/gpl-3.0.html.

"""



import yaml, sys, os, datetime, shutil
import pysvn
import getpass
from time import localtime, strftime
from optparse import OptionParser

global svn_usr, config, tmp_dir, deploy_dir
svn_usr = ""
tmp_dir = ""
deploy_dir = ""


def get_login( realm, username, may_save ):
    retcode = True
    username = raw_input('Enter SVN username: ')
    global svn_usr
    svn_usr = username
    password = getpass.getpass()
    save = False
    return retcode, username, password, save

def ssl_server_trust_prompt( trust_dict ):
    retcode = True
    accepted_failures = trust_dict['failures']
    save = True
    return retcode, accepted_failures, save

def tmp_to_deploy(src_path, dst_path):

    global tmp_dir, deploy_dir
    src = os.path.join(tmp_dir, src_path)
    dst = os.path.join(deploy_dir, dst_path)
    
    shutil.move(src, dst)

def move_modules(pth, module_type): # move modules to appropriate directories
    global tmp_dir, deploy_dir, config
    drupal_modules_path = "sites/all/modules"
    src = os.path.join(tmp_dir, pth)
    dst = os.path.join(deploy_dir, drupal_modules_path)

    if (not os.path.isdir(src)):
        return

    names = os.listdir(src)
    for name in names:
        flag = -1
        if module_type == 'lib':
            try:
                flag = config['drupal_modules']['lib'].index(name)
            except:
                pass
        elif module_type == 'custom':
            try:
                flag = config['drupal_modules']['custom'].index(name)
            except:
                pass
        if flag != -1:
            tmp_to_deploy(os.path.join(pth, name), os.path.join(drupal_modules_path, name))

def move_themes(pth):
    global tmp_dir, deploy_dir
    drupal_themes_path = "sites/all/themes"
    
    src = os.path.join(tmp_dir, pth)
    dst = os.path.join(deploy_dir, drupal_themes_path)
    
    pth_sp = pth.split("/")
    theme_name = str(pth_sp[len(pth_sp)-1])

    tmp_to_deploy(src, os.path.join(drupal_themes_path, theme_name))
 
# function to move files and folders inside the given directory to the deployment root folder
def move_to_root(pth,flag=0):
    global tmp_dir, deploy_dir
    src = os.path.join(tmp_dir, pth)
    
  
    if (not os.path.isdir(src)):
        return
    names = os.listdir(src)
    if (flag==1) :
        dir=pth.split("/");
        dir_full_pth=deploy_dir+'/'+dir[len(dir)-1]
        if(not os.path.exists(dir_full_pth)):
            deploy_dir_path=os.mkdir(dir_full_pth)

        for name in names:
            tmp_to_deploy(os.path.join(pth,name), os.path.join(dir_full_pth,name))
    else:      
        for name in names:
            tmp_to_deploy(os.path.join(pth, name), os.path.join(deploy_dir, name))
                    
def deploy(svnClient,deploy_flag):
    global tmp_dir, deploy_dir, config
    
    svn_url = config['svn_config']['url']
    tmp_dir = config['path']['tmp']
    deploy_dir = config['path']['deploy']
    backup_dir = config['path']['backup']
    
    print "Using:"
    print "  SVN url: " + svn_url
    print "  Temp dir: " + tmp_dir
    print "  Deployment dir: " + deploy_dir
    print "  Backup dir: " + backup_dir
  
  # export code from SVN
    if(os.path.isdir(tmp_dir)):
        shutil.rmtree(tmp_dir)
    print "\nExporting the code from SVN..."
    rev = svnClient.export(svn_url, tmp_dir) # Create an unversioned copy of the src_path at revision in dest_path.
    print "Revision number: ", rev.number, "\n"
    
    dst = ''
  # If the destination directory exists, move it to backup only if deploy_flag = 0 # make dis a diff. fn.
    if (deploy_flag == 0):
        if(os.path.isdir(deploy_dir)):
            print deploy_dir + ' exists'
            today = datetime.date.today()
            dst = os.path.join(backup_dir, str(today))
            i = 1
            dst1 = dst
            while(os.path.isdir(dst1)):
                dst1 = dst + "_" + str(i) + "_maplocator"
                i = i + 1
                
            dst = dst1
            print "Copying " + deploy_dir + " to " + dst
            shutil.copytree(deploy_dir, dst)
            shutil.rmtree(deploy_dir)
         
        else:
            pass

    # copy drupal to deploy folder
    print "Moving drupal to deploy folder."
    svn_drupal_path = config['svn_path']['drupal']
    print svn_drupal_path
    src = os.path.join(tmp_dir, svn_drupal_path)
    if (os.path.isdir(src)):
        shutil.move(src, deploy_dir)
    print "Done."
  
    # copy external drupal modules
    print "Moving external drupal modules."
    svn_external_drupal_modules_path = config['svn_path']['drupal_modules']
    print svn_external_drupal_modules_path
    move_modules(svn_external_drupal_modules_path, 'external')
    print "Done."

  # copy custom drupal modules
    print "Moving custom drupal modules."
    svn_custom_drupal_modules_path = config['svn_path']['custom_modules']
    print svn_custom_drupal_modules_path
    move_modules(svn_custom_drupal_modules_path, 'custom')
    print "Done."

  # copy config_path to root
    config_p=config['svn_path']['config_path']
    move_to_root(config_p)
    
    # copy custom ajax
    custom_ajax=config['svn_path']['custom_ajax']
    move_to_root(custom_ajax)

    # copy custom flash
    custom_flash=config['svn_path']['custom_flash']
    move_to_root(custom_flash)

    # copy custom theme
    custom_theme=config['svn_path']['custom_theme']
    move_themes(custom_theme)

    # copy custom standalone pages
    custom_standalone_pages=config['svn_path']['custom_standalone_pages']
    move_to_root(custom_standalone_pages)

    # copy custom flash
    thirdparty_javascript_code=config['svn_path']['thirdparty_javascript_code']
    move_to_root(thirdparty_javascript_code)

    # copy treeview to root
    treeview=config['svn_path']['treeview']
    move_to_root(treeview,flag=1)
    

def parse_options(args):
    parser=OptionParser(usage="%prog -c config file -d deploy_type [-s|-u|-p]")
    parser.add_option("-c", "--config",
                      help="base config file for deployment")
    parser.add_option("-d", "--deploy_type",help="type of deployment", 
                      choices=['uap','ibp','maplocator'])
    parser.add_option("-s", "--sql_dump",help="name of the file of sql dump")
    parser.add_option("-u","--user", help="database user name")
    parser.add_option("-p","--paasword", help="database user password")
    (options,args)=parser.parse_args()
    
def patch_db(db_dump,db_user,db_pass):
    if not(os.path.isfile(db_dump)):
        print "The specified dump file '" + db_dump + "' does not exist. Please specify a valid dump file"
        sys.exit(1)
    os.environ['PGPASSWORD'] = db_pass
    new_db = override_by + strftime("_%d_%m_%Y_%H_%M_%S")
    create_db_command = "psql -U " + db_user + " -h localhost -c 'create database " + new_db + ";' postgres"
    print "Creating a new datbase: " + new_db + ". Executing: " + create_db_command
    cmd_op = str(os.system(create_db_command + " | grep fatal"))
    if ("FATAL" in cmd_op):
        print "Error connecting to datbase with: \n " + cmd_op
        print "Exiting"
        sys.exit(1)
    print "Done"
    patch_db_command = "psql -U " + db_user + " -h localhost " + new_db + " < " + db_dump
    print "Dumping the database: " + db_dump + ". Executing: " + patch_db_command
    cmd_op = str(os.system(patch_db_command + " | grep error"))
    if ("ERROR" in cmd_op):
        print "Errors found in applying patch: \n " + cmd_op
        print "Please drop and create the database manually using the following commands: "
        print "1: " + create_db_command
        print "2: " + patch_db_command
        print "Done"

  
def main(argv):
    global config
    arg_len =len(argv)
    args = parse_options(argv)
    if(arg_len <5 or arg_len > 11):
        print "Invalid number of arguments. "
        print "Try -h or --help option for more information"
        sys.exit(1)
    else:
        base_configfile = sys.argv[2]
        print base_configfile
        override_by = sys.argv[4]
        print override_by
        config = yaml.load(file(base_configfile, 'rb').read())
    
    # init the SVN client
        svn_cfg_dir = os.path.join( os.getcwd(), config['svn_config']['cfg_dir'])
        try:
            client = pysvn.Client(svn_cfg_dir) 
            client.callback_ssl_server_trust_prompt = ssl_server_trust_prompt #called each time an HTTPS server presents a certificate and subversion is not sure if it should be trusted
            client.callback_get_login = get_login # called to get a username and password to access a repository. 
        except Exception,e:
            print e
        
        deploy(client,0)
        deploy_dir1 = config['path']['deploy']
        
        if (override_by != "mlocate"):
            config = yaml.load(file(base_configfile, 'rb').read().replace("mlocate",override_by))
            tempvar = deploy_dir1.split("/")
            config['path']['deploy'] = config['path']['deploy']+"/" + str(tempvar[len(tempvar)-1])+"_tmp"
            
            deploy(client,1)
            deploy_dir2 = config['path']['deploy']
          
            if os.name == 'nt':
                str1 = ("xcopy "+deploy_dir2+" "+deploy_dir1).replace("/","\\")
                os.system(str1+" /Y /H /E")      
            else:
                os.system("cp -r "+deploy_dir2+"/* "+deploy_dir1+"/")
            
            shutil.rmtree(deploy_dir2)
            
        if len(sys.argv) == 11:
            db_dump = sys.argv[6]
            db_user = sys.argv[8]
            db_pass = sys.argv[10]
            patch_db(db_dump,db_user,db_pass)
  
    print "\nDeploy Process complete."


if __name__ == '__main__':
    main(sys.argv)

  

