'use strict';
// берём Express
var express = require('express');
var bodyParser = require('body-parser');
var nodemailer = require('../lib/nodemailer');
var Socks = require('socks');
//BD
var mysql = require('mysql');

var client =mysql.createConnection({
  host     : '127.0.0.1',
  user     : 'root',
  password : '',
  database :'parserweb'
});

// создаём Express-приложение
var app = express();

app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
// создаём маршрут для главной страницы
// http://localhost:8080/

console.log('\x1b[33m%s\x1b[0m: ', 'ff');  //yellow
app.get('/', function(req, res) {
  res.end('OK');
 
});

 app.post('/sendmail', function(req, res){
 
  console.log("\x1b[41m",req.body.id,"\x1b[0m");
 /* var proxy = {
    ipaddress: req.body.proxy.host,
    port: req.body.proxy.port,
	user: req.body.proxy.user,
	pass: req.body.proxy.password
    //type: 5
};*/
 
//console.log("\x1b[41m",proxy,"\x1b[0m");
  req.body.proxy.user
  var transporter = nodemailer.createTransport({
//debug: true,   
   host: req.body.hostname,
    
    auth: {
        user: req.body.login,
        pass: req.body.password
    },
	port: req.body.port,
    secure: true, // use TLS
    logger: true, // log to console
    //debug: true, // include SMTP traffic in the logs
	connectionTimeout: 20000,
    greetingTimeout: 15000,
    // define proxy configuration
    proxy: req.body.proxy,//"socks5://7zxShe:FhB871@185.147.124.33:8000",//proxy, 
	//req.body.proxy,//'socks5://94.27.22.250:32550/'
	
		//req.body.proxy//'socks5://94.27.22.250:32550/'
	

});


//transporter.set('proxy_socks_module', require('socks'));
console.log("\x1b[32m",'SMTP Configured',"\x1b[0m");
//console.log("\x1b[32m",req.body.login,"\x1b[0m");
  //set messages
  
//console.log(req.body.attach[0].path);
 var message = {
    from: '<'+req.body.login+'>',
    to: req.body.to,
    subject: req.body.subject, //
    //text: 'Сообщения прийди',
    html: req.body.html,
	attachments: req.body.attach
};
console.log('create message');
  
  console.log('Sending Mail');
transporter.sendMail(message, function (error, info) {
    if (error) {
        console.log('Error occurred');
        console.log(error.message);
		//console.log("\x1b[33m%s\x1b[0m: ",'Server responded with "%s"', info.response,"\x1b[0m");
		res.send(error.message);
        return;
    }
    console.log("\x1b[32m",'Message sent successfully!',"\x1b[0m");
    console.log("\x1b[33m%s\x1b[0m: ",'Server responded with "%s"', info.response,"\x1b[0m");
	res.end("success");
});
  
  
  
  
  
  //res.end("NOT");
 
 
 
 
 
 
});

// запускаем сервер на порту 8080
app.listen(25555);
// отправляем сообщение
console.log('Сервер стартовал!');
