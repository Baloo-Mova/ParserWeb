/* eslint no-console: 0 */

'use strict';


const nodemailer = require('../lib/nodemailer');
//BD
var mysql = require('mysql');

var client =mysql.createConnection({
  host     : '127.0.0.1',
  user     : 'root',
  password : '',
  database :'parserweb'
});

var query = client.query('SELECT * FROM proxy_temp', function(error, result, fields){
   for (var i in result) {
      //  console.log('Post Titles: ', result[i].proxy);
		
		}
		
 
		
});
var tmp = "fg" ;	
client.query('SELECT * FROM proxy_temp where mail=? LIMIT 1', [1], function(error, result) {
 tmp = result[0].proxy;
 tmp = tmp.replace("\r",'/');
	return tmp;
  
});
client.end();
console.log(tmp);

