<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'DashboardController@index');
Route::get('login', 'LoginController@index');
Route::post('login', 'LoginController@check_password');

Route::get('newHire', 'newHireController@index');
Route::post('add', 'newHireController@add');
Route::post('chkeml', 'newHireController@checkEmail');
Route::get('autocomplete', 'LdapController@autocomplete');


Route::get('report/{reportType}/{name}', 'ReportsController@getReport')->where('name', '[a-zA-Z0-9 -]+\.pdf');

Route::get('separation', 'SeparationController@index');
Route::post('separation_search', 'SeparationController@separation_search');
Route::post('separation_add', 'SeparationController@add');


Route::get('change_org', 'Change_OrgController@index');
Route::get('lookup_chng_org', 'ActiveDirectory@lookup_chng_org');
//Route::get('lookup/{uname}', 'Change_OrgController@lookup')->where('name', '[a-zA-Z0-9 -]');
Route::post('lookup', 'Change_OrgController@lookup');












/*
 *
 donetodo newhires revisar employee number and nickname estan saliendo vacios en el reporte
donetodo- en new hires detectar location y predefinir los grupos a los que pertenece
donetodo- para los nombres eliminar espacios en los titulos, nombres, etc
donetodo- si tiene en el nombre un acento, no incluirlo en el nombre de usuario


// TOTODO
todo para los newhires ponerlos en canada, verificar si hay un grupo en AD para ellos
todo para separation el grupo de illyusa Sales no está funcionando para salir autodetectado, problemas con el javascript
todo newhire el form esta saliendo con el campo de comentarios vacios

// STYLES
todo- poner todos los label en negrita y pasar todo a labels
todo- aplicar el estilo de newhire a separation (el required que no funciona en separation)
todo- en los emails templates poner stylo para los nombres en negrita



// ORGANIZATION CHANGE
todo- organizacion change, al cambiar la compania mandar un correo
todo automatizado para cmabiar la signature y actualizar info en AD


// SEPARATION
todo- para el separation detectar si el campo de cellphone tiene algún valor is most likely the user has a cellphone and marcarlo en el listado


{"count":1,"0":{"sn":{"count":1,"0":"Gil"},"0":"sn","title":{"count":1,"0":"IT Infrastructure Engineer & Support"},"1":"title","givenname":{"count":1,"0":"Rafael"},"2":"givenname","memberof":{"count":15,"0":"CN=SlideShow_SecurityGrp_NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","1":"CN=HR-Tool,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","2":"CN=Wordpress-editor,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","3":"CN=si_infra_all,OU=Distribution,OU=Groups,OU=HeadQuarter,OU=Italy,DC=ILLY-DOMAIN,DC=COM","4":"CN=RoomUsersUSA,OU=Rooms,OU=New York City,OU=North America,DC=ILLY-DOMAIN,DC=COM","5":"CN=VNC Admin,OU=Service Groups,DC=ILLY-DOMAIN,DC=COM","6":"CN=PC Admins,OU=Service Groups,DC=ILLY-DOMAIN,DC=COM","7":"CN=illyusa Rye Brook Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","8":"CN=Report ServiceDesk IC Nord America,CN=Users,DC=ILLY-DOMAIN,DC=COM","9":"CN=Finance NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","10":"CN=VPN illy,OU=Security,OU=Groups,OU=HeadQuarter,OU=Italy,DC=ILLY-DOMAIN,DC=COM","11":"CN=Marketing NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","12":"CN=Information Technology NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","13":"CN=illyusaTeam Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM","14":"CN=Wifi Employees,OU=Security,OU=Groups,OU=HeadQuarter,OU=Italy,DC=ILLY-DOMAIN,DC=COM"},"3":"memberof","department":{"count":1,"0":"IT"},"4":"department","company":{"count":1,"0":"illy caff\u00e8 North America, Inc."},"5":"company","samaccountname":{"count":1,"0":"gilra"},"6":"samaccountname","mail":{"count":1,"0":"Rafael.Gil@illy.com"},"7":"mail","manager":{"count":1,"0":"CN=Roy Forster,OU=Users,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM"},"8":"manager","count":9,"dn":"CN=Rafael Gil,OU=Users,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM"}}




 *
 */