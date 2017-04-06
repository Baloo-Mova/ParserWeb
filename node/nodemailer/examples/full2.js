

'use strict';

/*
This example demonstrates how to use a socks4/5 proxy
*/

// create SOCKS5 proxy with
//     ssh -N -D 0.0.0.0:1080 username@remote.host

var nodemailer = require('../lib/nodemailer');
var sock = require('socks');
var options = {
    proxy: {
        ipaddress: "94.27.22.250",
        port: 32550,
        type: 5  // (4 or 5)
    },

    target: {
        host: "216.58.209.174", // (google.com)
        port: 80
    }
};

sock.createConnection(options, function (err, socket, info) {
    if (err)
        console.log(err);
    else {
        console.log("Connected");

        socket.on('data', function (data) {
            // do something with incoming data
        });

        // Please remember that sockets need to be resumed before any data will come in.
        socket.resume();

        // We can do whatever we want with the socket now.
    }
});
/*
// Create a SMTP transporter object
var transporter = nodemailer.createTransport({
    host: 'smtp.mail.ru',
    
    auth: {
        user: 'vogak_forever_yo@mail.ru',
        pass: '1994-vogak'
    },
	port: 465,
    secure: true, // use TLS
    logger: true, // log to console
    debug: true, // include SMTP traffic in the logs

    // define proxy configuration
    proxy: 'socks5://95.215.1.94:61879/'
});
transporter.set('proxy_socks_module', require('socks'));
console.log("\x1b[32m",'SMTP Configured');

var message = {
    from: '<vogak_forever_yo@mail.ru>',
    to: 'vladislav.tern@gmail.com,ternovoy.vladislav@yandex.ua',
    subject: 'Работа на дипломом', //
    //text: 'Сообщения прийди',
    html: '<p><b>Нужно пахать над дипломом</b></p>',
	attachments: [
       
        {   // file on disk as an attachment 
            //filename: 'unnamed.png',
            path: 'D:\workZNU/unnamed.png' // stream this file 
        }
		]
};

console.log('Sending Mail');
transporter.sendMail(message, function (error, info) {
    if (error) {
        console.log('Error occurred');
        console.log(error.message);
        return;
    }
    console.log('Message sent successfully!');
    console.log('Server responded with "%s"', info.response);
});
*/